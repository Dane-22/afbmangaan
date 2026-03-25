<?php
/**
 * System Logs Page - AFB Mangaan Attendance System
 * Admin only
 */

$pageTitle = 'System Logs';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/activity_logger.php';

// Verify admin access
requireRole('admin');

// Handle clear logs
if (isset($_POST['clear_logs'])) {
    $result = clearOldLogs(30);
    $message = $result['success'] ? "Cleared {$result['deleted']} old log entries" : 'Failed to clear logs';
}

// Get filters
$filters = [];
if (!empty($_GET['action'])) $filters['action'] = $_GET['action'];
if (!empty($_GET['user_id'])) $filters['user_id'] = $_GET['user_id'];
if (!empty($_GET['from_date'])) $filters['from_date'] = $_GET['from_date'];
if (!empty($_GET['to_date'])) $filters['to_date'] = $_GET['to_date'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

$result = getSystemLogs($filters, $page, $perPage);
$logs = $result['logs'];
$totalPages = $result['total_pages'];
$total = $result['total'];

$actions = getLogActions();
$users = getAllUsers();
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Filters -->
<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-funnel"></i>
            Filter Logs
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Action</label>
                <select name="action" class="form-control form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo $action; ?>" <?php echo ($_GET['action'] ?? '') === $action ? 'selected' : ''; ?>><?php echo $action; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($_GET['user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['fullname']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $_GET['from_date'] ?? ''; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $_GET['to_date'] ?? ''; ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search details..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-magnifying-glass"></i> Search
            </button>
            
            <a href="logs.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>
</div>

<!-- Logs List -->
<div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-scroll"></i>
            System Logs
            <span style="font-size: 0.875rem; font-weight: normal; color: var(--text-muted);">
                (<?php echo $total; ?> total)
            </span>
        </h3>
        <form method="POST" style="display: inline;" onsubmit="return confirm('Clear logs older than 30 days?');">
            <button type="submit" name="clear_logs" class="btn btn-sm btn-danger">
                <i class="ph ph-trash"></i> Clear Old Logs
            </button>
        </form>
    </div>
    <div class="card-body">
        <!-- Desktop Table View -->
        <div class="table-container desktop-only">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td data-label="ID"><?php echo $log['id']; ?></td>
                            <td data-label="Timestamp"><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></td>
                            <td data-label="User">
                                <?php if ($log['user_name']): ?>
                                    <?php echo htmlspecialchars($log['user_name']); ?>
                                    <br><small><?php echo $log['username']; ?></small>
                                <?php else: ?>
                                    <span class="badge badge-secondary">System</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Action">
                                <span class="badge badge-info"><?php echo $log['action']; ?></span>
                            </td>
                            <td data-label="Details" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                            </td>
                            <td data-label="IP Address"><code><?php echo $log['ip_address'] ?? '-'; ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Grid View -->
        <div class="mobile-grid-view mobile-only">
            <?php foreach ($logs as $log): 
                $actionColor = match($log['action']) {
                    'LOGIN' => '#22c55e',
                    'LOGOUT' => '#6b7280',
                    'CREATE', 'ADD' => '#3b82f6',
                    'UPDATE', 'EDIT' => '#f59e0b',
                    'DELETE', 'REMOVE', 'ARCHIVE' => '#ef4444',
                    default => '#6b7280'
                };
                $cardBorder = "border-left: 4px solid {$actionColor};";
            ?>
                <div class="log-grid-card" style="<?php echo $cardBorder; ?>">
                    <div class="log-grid-header">
                        <div class="log-icon" style="background: <?php echo $actionColor; ?>">
                            <i class="ph ph-scroll"></i>
                        </div>
                        <div class="log-grid-info">
                            <h4><?php echo htmlspecialchars($log['action']); ?></h4>
                            <span class="log-timestamp"><?php echo date('M d, Y g:i A', strtotime($log['timestamp'])); ?></span>
                        </div>
                    </div>
                    <div class="log-grid-details">
                        <div class="log-detail-row">
                            <span class="detail-label">User</span>
                            <span class="detail-value">
                                <?php if ($log['user_name']): ?>
                                    <?php echo htmlspecialchars($log['user_name']); ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">System</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="log-detail-row">
                            <span class="detail-label">Details</span>
                            <span class="detail-value"><?php echo htmlspecialchars($log['details'] ?? '-'); ?></span>
                        </div>
                        <div class="log-detail-row">
                            <span class="detail-label">IP Address</span>
                            <span class="detail-value"><code><?php echo $log['ip_address'] ?? '-'; ?></code></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;" class="pagination-container">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-sm btn-secondary">Previous</a>
                <?php endif; ?>
                
                <span style="padding: 0.5rem 1rem; background: var(--bg-secondary); border-radius: var(--radius);">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-sm btn-secondary">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
