<?php
/**
 * Webhook API Test Page
 * AFB Mangaan Attendance System
 * 
 * Browser-based testing interface for the webhook endpoint.
 * Access this page to manually test the Google Sheets integration.
 */

$pageTitle = 'Webhook API Test';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/webhook.php';
require_once __DIR__ . '/functions/make_sync.php';

$testResult = null;
$error = null;

// Handle test form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testType = $_POST['test_type'] ?? '';
    
    if ($testType === 'webhook') {
        // Test incoming webhook
        $entity = $_POST['entity'] ?? 'attendee';
        $action = $_POST['action'] ?? 'create';
        
        $payload = [
            'entity' => $entity,
            'action' => $action,
            'data' => []
        ];
        
        if ($entity === 'attendee') {
            $payload['data'] = [
                'fullname' => $_POST['fullname'] ?? 'Test Member',
                'category' => $_POST['category'] ?? 'Adult',
                'contact' => $_POST['contact'] ?? null,
                'email' => $_POST['email'] ?? null,
                'status' => 'Active'
            ];
            if ($action !== 'create' && !empty($_POST['id'])) {
                $payload['data']['id'] = (int)$_POST['id'];
            }
        } elseif ($entity === 'event') {
            $payload['data'] = [
                'event_name' => $_POST['event_name'] ?? 'Test Event',
                'event_date' => $_POST['event_date'] ?? date('Y-m-d'),
                'event_time' => $_POST['event_time'] ?? '09:00:00',
                'location' => $_POST['location'] ?? null,
                'type' => $_POST['event_type'] ?? 'Sunday Service',
                'status' => 'Upcoming',
                'description' => 'Created via API test page'
            ];
            if ($action !== 'create' && !empty($_POST['id'])) {
                $payload['data']['id'] = (int)$_POST['id'];
            }
        }
        
        // Send to local webhook
        $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . '/afb_mangaan/api/webhook_import.php');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Secret: ' . getWebhookConfig('secret', 'change-this-to-a-random-secret-key')
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $testResult = json_decode($response, true);
        if (!$testResult) {
            $error = 'Invalid response from webhook';
        }
        
    } elseif ($testType === 'make_connection') {
        // Test make.com connection
        $testResult = testMakeConnection();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-plugs-connected"></i>
            Webhook & make.com Integration Test
        </h3>
    </div>
    <div class="card-body">
        
        <!-- Configuration Status -->
        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 0.5rem;"><i class="ph ph-info"></i> Configuration Status</h4>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <li><strong>Webhook Secret:</strong> <?php echo empty(getWebhookConfig('secret')) || getWebhookConfig('secret') === 'change-this-to-a-random-secret-key' ? '<span style="color: #dc2626;">⚠ Not configured (using default)</span>' : '<span style="color: #16a34a;">✓ Configured</span>'; ?></li>
                <li><strong>make.com Webhook URL:</strong> <?php echo empty(getWebhookConfig('make_webhook_url')) ? '<span style="color: #f59e0b;">⚠ Not configured (export sync disabled)</span>' : '<span style="color: #16a34a;">✓ Configured</span>'; ?></li>
                <li><strong>Logging:</strong> <?php echo getWebhookConfig('enable_logging', true) ? 'Enabled' : 'Disabled'; ?></li>
            </ul>
        </div>
        
        <?php if ($testResult): ?>
        <div class="alert <?php echo $testResult['success'] ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 0.5rem;">
                <i class="ph <?php echo $testResult['success'] ? 'ph-check-circle' : 'ph-warning-circle'; ?>"></i>
                Test Result: <?php echo $testResult['success'] ? 'Success' : 'Failed'; ?>
            </h4>
            <p style="margin: 0;"><strong>Message:</strong> <?php echo htmlspecialchars($testResult['message']); ?></p>
            <?php if (isset($testResult['id'])): ?>
            <p style="margin: 0.25rem 0 0 0;"><strong>ID:</strong> <?php echo $testResult['id']; ?></p>
            <?php endif; ?>
            <?php if (isset($testResult['response'])): ?>
            <p style="margin: 0.25rem 0 0 0;"><strong>Response:</strong></p>
            <pre style="margin: 0.5rem 0 0 0; background: rgba(0,0,0,0.05); padding: 0.5rem; border-radius: 4px; font-size: 0.875rem;"><?php echo htmlspecialchars($testResult['response']); ?></pre>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            
            <!-- Incoming Webhook Test (Sheets → PHP) -->
            <div class="card">
                <div class="card-header" style="background: var(--bg-secondary);">
                    <h4 style="margin: 0;"><i class="ph ph-arrow-down"></i> Test Incoming Webhook</h4>
                    <small>Simulate data from Google Sheets → PHP</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="test_type" value="webhook">
                        
                        <div class="form-group">
                            <label class="form-label">Entity Type</label>
                            <select name="entity" id="testEntity" class="form-control form-select" onchange="updateFormFields()">
                                <option value="attendee">Attendee (Member)</option>
                                <option value="event">Event</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Action</label>
                            <select name="action" id="testAction" class="form-control form-select" onchange="updateFormFields()">
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="delete">Delete</option>
                            </select>
                        </div>
                        
                        <!-- Attendee Fields -->
                        <div id="attendeeFields">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="fullname" class="form-control" placeholder="Juan Dela Cruz">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control form-select">
                                    <option value="Adult">Adult</option>
                                    <option value="Youth">Youth</option>
                                    <option value="Senior">Senior</option>
                                    <option value="Child">Child</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Contact</label>
                                <input type="text" name="contact" class="form-control" placeholder="09123456789">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com">
                            </div>
                        </div>
                        
                        <!-- Event Fields -->
                        <div id="eventFields" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Event Name *</label>
                                <input type="text" name="event_name" class="form-control" placeholder="Sunday Worship Service">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Event Date *</label>
                                <input type="date" name="event_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Event Time</label>
                                <input type="time" name="event_time" class="form-control" value="09:00">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" placeholder="Main Sanctuary">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <select name="event_type" class="form-control form-select">
                                    <option value="Sunday Service">Sunday Service</option>
                                    <option value="Midweek Service">Midweek Service</option>
                                    <option value="Special Event">Special Event</option>
                                    <option value="Meeting">Meeting</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- ID field for update/delete -->
                        <div id="idField" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">ID</label>
                                <input type="number" name="id" class="form-control" placeholder="Enter ID for update/delete">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="ph ph-paper-plane-right"></i> Send Test Webhook
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Outgoing Webhook Test (PHP → make.com) -->
            <div class="card">
                <div class="card-header" style="background: var(--bg-secondary);">
                    <h4 style="margin: 0;"><i class="ph ph-arrow-up"></i> Test make.com Connection</h4>
                    <small>Test connection to make.com for exporting to Sheets</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="test_type" value="make_connection">
                        
                        <div class="alert alert-info" style="margin-bottom: 1rem;">
                            <p style="margin: 0;">This sends a test payload to your configured make.com webhook URL to verify connectivity.</p>
                        </div>
                        
                        <p><strong>make.com Webhook URL:</strong></p>
                        <p style="word-break: break-all; font-size: 0.875rem; color: var(--text-muted);">
                            <?php echo getWebhookConfig('make_webhook_url') ?: 'Not configured'; ?>
                        </p>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;" 
                            <?php echo empty(getWebhookConfig('make_webhook_url')) ? 'disabled' : ''; ?>>
                            <i class="ph ph-plugs"></i> Test make.com Connection
                        </button>
                        
                        <?php if (empty(getWebhookConfig('make_webhook_url'))): ?>
                        <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #dc2626;">
                            <i class="ph ph-warning"></i> Configure make_webhook_url in config/webhook.php first
                        </p>
                        <?php endif; ?>
                    </form>
                    
                    <hr style="margin: 1.5rem 0;">
                    
                    <h5 style="margin-bottom: 1rem;"><i class="ph ph-info"></i> Setup Instructions</h5>
                    
                    <ol style="font-size: 0.875rem; padding-left: 1.25rem; margin: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <strong>Generate a webhook secret:</strong><br>
                            <code style="background: var(--bg-tertiary); padding: 2px 4px; border-radius: 3px;">config/webhook.php</code>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <strong>Set up make.com scenario:</strong><br>
                            Create a webhook trigger in make.com to get a URL
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <strong>Add webhook URL:</strong><br>
                            Add the make.com URL to config/webhook.php
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <strong>Configure Google Sheets module:</strong><br>
                            Add row → select your spreadsheet
                        </li>
                        <li>
                            <strong>Test:</strong> Click "Test make.com Connection"
                        </li>
                    </ol>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<script>
function updateFormFields() {
    const entity = document.getElementById('testEntity').value;
    const action = document.getElementById('testAction').value;
    
    // Show/hide entity fields
    document.getElementById('attendeeFields').style.display = entity === 'attendee' ? 'block' : 'none';
    document.getElementById('eventFields').style.display = entity === 'event' ? 'block' : 'none';
    
    // Show/hide ID field for update/delete
    document.getElementById('idField').style.display = (action === 'update' || action === 'delete') ? 'block' : 'none';
}

// Initialize on load
updateFormFields();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
