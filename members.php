<?php
/**
 * Members Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Members';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';
require_once __DIR__ . '/functions/report_engine.php';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $pdo = getDB();
        $id = $_POST['id'] ?? null;
        $fullname = $_POST['fullname'] ?? '';
        $category = $_POST['category'] ?? 'Adult';
        $contact = $_POST['contact'] ?? '';
        $email = $_POST['email'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        
        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE attendees SET fullname=?, category=?, contact=?, email=?, status=? WHERE id=? AND church=?");
                $stmt->execute([$fullname, $category, $contact, $email, $status, $id, $_SESSION['church'] ?? 'AFB Mangaan']);
                $message = 'Member updated successfully';
                logActivity($_SESSION['user_id'], 'MEMBER_UPDATE', "Updated member ID: {$id}");
            } else {
                // Insert
                $qrToken = generateQRToken(rand(1000, 9999));
                $stmt = $pdo->prepare("INSERT INTO attendees (church, fullname, category, contact, email, qr_token, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['church'] ?? 'AFB Mangaan', $fullname, $category, $contact, $email, $qrToken, $status]);
                $message = 'Member added successfully';
                logActivity($_SESSION['user_id'], 'MEMBER_CREATE', "Created member: {$fullname}");
            }
        } catch (PDOException $e) {
            $error = 'Error saving member: ' . $e->getMessage();
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $pdo = getDB();
        try {
            $stmt = $pdo->prepare("UPDATE attendees SET status='Archived' WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $message = 'Member archived successfully';
            logActivity($_SESSION['user_id'], 'MEMBER_ARCHIVE', "Archived member ID: {$_POST['id']}");
        } catch (PDOException $e) {
            $error = 'Error archiving member';
        }
    }
}

// Get members
$pdo = getDB();
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? 'Active';
$church = $_SESSION['church'] ?? 'AFB Mangaan';

$sql = "SELECT * FROM attendees WHERE church = ?";
$params = [$church];

if ($search) {
    $sql .= " AND (fullname LIKE ? OR qr_token LIKE ? OR contact LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY fullname ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get categories for filter
$categories = ['MCYO', 'WMO', 'CCMO', 'KIDS', 'Visitors', 'Other'];

// Check if adding/editing
$editMode = false;
$editMember = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editMode = true;
    foreach ($members as $m) {
        if ($m['id'] == $_GET['edit']) {
            $editMember = $m;
            break;
        }
    }
}
$addMode = isset($_GET['action']) && $_GET['action'] === 'add';
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php if ($message): ?>
    <div class="alert badge-success" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert badge-danger" style="margin-bottom: 1rem; padding: 1rem;">
        <i class="ph ph-warning-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card animate__animated animate__fadeIn">
    <div class="card-body">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                <label class="form-label">Search</label>
                <div class="search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" name="search" class="form-control" placeholder="Name, QR code, or contact..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Category</label>
                <select name="category" class="form-control form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control form-select">
                    <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Archived" <?php echo $statusFilter === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                    <option value="" <?php echo $statusFilter === '' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-funnel"></i> Filter
            </button>
            
            <button type="button" class="btn btn-success" onclick="openMemberModal()">
                <i class="ph ph-plus"></i> Add Member
            </button>
        </form>
    </div>
</div>


<!-- Members List -->
<div class="card animate__animated animate__fadeInUp" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ph ph-users"></i>
            Members List
            <span style="font-size: 0.875rem; font-weight: normal; color: var(--text-muted);">
                (<?php echo count($members); ?> found)
            </span>
        </h3>
        <a href="api/export_members.php" class="btn btn-sm btn-secondary">
            <i class="ph ph-download"></i> Export
        </a>
    </div>
    <div class="card-body">
        <!-- Desktop Table View -->
        <div class="table-container desktop-only">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Contact</th>
                        <th>QR Token</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td data-label="ID"><?php echo $member['id']; ?></td>
                            <td data-label="Name">
                                <strong><?php echo htmlspecialchars($member['fullname']); ?></strong>
                                <?php if ($member['email']): ?>
                                    <br><small><?php echo htmlspecialchars($member['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Category"><?php echo $member['category']; ?></td>
                            <td data-label="Contact"><?php echo htmlspecialchars($member['contact']); ?></td>
                            <td data-label="QR Token"><code><?php echo $member['qr_token']; ?></code></td>
                            <td data-label="Status">
                                <span class="badge badge-<?php echo $member['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $member['status']; ?>
                                </span>
                            </td>
                            <td data-label="Actions">
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-secondary" title="Edit" 
                                            data-id="<?php echo $member['id']; ?>"
                                            data-fullname="<?php echo htmlspecialchars($member['fullname'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-category="<?php echo $member['category']; ?>"
                                            data-contact="<?php echo htmlspecialchars($member['contact'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-status="<?php echo $member['status']; ?>"
                                            data-qr="<?php echo $member['qr_token']; ?>"
                                            onclick="openEditModalFromButton(this)">
                                        <i class="ph ph-pencil"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this member?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Archive">
                                            <i class="ph ph-archive"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Grid View -->
        <div class="mobile-grid-view mobile-only">
            <?php foreach ($members as $member): 
                $cardBorder = $member['status'] === 'Active' ? 'border-left: 4px solid #22c55e;' : 'border-left: 4px solid #6b7280;';
            ?>
                <div class="member-grid-card" style="<?php echo $cardBorder; ?>">
                    <div class="member-grid-header">
                        <div class="member-avatar-large">
                            <?php echo strtoupper(substr($member['fullname'], 0, 2)); ?>
                        </div>
                        <div class="member-grid-info">
                            <h4><?php echo htmlspecialchars($member['fullname']); ?></h4>
                            <span class="member-code"><?php echo $member['qr_token']; ?></span>
                            <span class="status-badge <?php echo $member['status'] === 'Active' ? 'status-present' : 'status-inactive'; ?>">
                                <?php echo $member['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="member-grid-details">
                        <div class="member-detail-row">
                            <span class="detail-label">Category</span>
                            <span class="detail-value"><?php echo $member['category']; ?></span>
                        </div>
                        <div class="member-detail-row">
                            <span class="detail-label">Contact</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['contact']); ?></span>
                        </div>
                        <?php if ($member['email']): ?>
                        <div class="member-detail-row">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($member['email']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="member-grid-actions">
                        <button type="button" class="btn btn-edit"
                                data-id="<?php echo $member['id']; ?>"
                                data-fullname="<?php echo htmlspecialchars($member['fullname'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-category="<?php echo $member['category']; ?>"
                                data-contact="<?php echo htmlspecialchars($member['contact'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-email="<?php echo htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-status="<?php echo $member['status']; ?>"
                                data-qr="<?php echo $member['qr_token']; ?>"
                                onclick="openEditModalFromButton(this)">
                            <i class="ph ph-pencil"></i> Edit
                        </button>
                        <form method="POST" style="display: contents;" onsubmit="return confirm('Archive this member?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                            <button type="submit" class="btn btn-archive">
                                <i class="ph ph-archive"></i> Archive
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: var(--card-bg); border-radius: var(--radius-lg); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div class="modal-header" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                <i class="ph ph-user-plus" style="color: var(--primary);"></i>
                Add New Member
            </h3>
            <button type="button" onclick="closeMemberModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form method="POST" action="" id="addMemberForm">
            <div class="modal-body" style="padding: 1.5rem;">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-user" style="color: var(--primary);"></i>
                        Full Name *
                    </label>
                    <input type="text" name="fullname" class="form-control" required 
                           placeholder="Enter member's full name"
                           style="padding: 0.75rem; border-radius: var(--radius-md);">
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-users-three" style="color: var(--primary);"></i>
                        Category *
                    </label>
                    <select name="category" class="form-control form-select" required
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-phone" style="color: var(--primary);"></i>
                        Contact Number
                    </label>
                    <input type="text" name="contact" class="form-control" 
                           placeholder="e.g., 09123456789"
                           style="padding: 0.75rem; border-radius: var(--radius-md);">
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-envelope" style="color: var(--primary);"></i>
                        Email
                    </label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="e.g., member@email.com"
                           style="padding: 0.75rem; border-radius: var(--radius-md);">
                </div>
                
                <div style="background: rgba(99, 102, 241, 0.1); padding: 1rem; border-radius: var(--radius-md); margin-top: 1rem;">
                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ph ph-info" style="color: var(--primary);"></i>
                        QR Code will be auto-generated when you save.
                    </p>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeMemberModal()">
                    <i class="ph ph-x"></i> Cancel
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="ph ph-check"></i> Save Member
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editMemberModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: var(--card-bg); border-radius: var(--radius-lg); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div class="modal-header" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;">
                <i class="ph ph-user-gear" style="color: var(--primary);"></i>
                Edit Member
            </h3>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form method="POST" action="" id="editMemberForm">
            <div class="modal-body" style="padding: 1.5rem;">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-user" style="color: var(--primary);"></i>
                        Full Name *
                    </label>
                    <input type="text" name="fullname" id="edit_fullname" class="form-control" required 
                           style="padding: 0.75rem; border-radius: var(--radius-md);">
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-users-three" style="color: var(--primary);"></i>
                        Category *
                    </label>
                    <select name="category" id="edit_category" class="form-control form-select" required
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-phone" style="color: var(--primary);"></i>
                        Contact Number
                    </label>
                    <input type="text" name="contact" id="edit_contact" class="form-control" 
                           style="padding: 0.75rem; border-radius: var(--radius-md);">
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-envelope" style="color: var(--primary);"></i>
                        Email
                    </label>
                    <input type="email" name="email" id="edit_email" class="form-control" 
                           style="padding: 0.75rem; border-radius: var(--radius-md);">
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-toggle-left" style="color: var(--primary);"></i>
                        Status
                    </label>
                    <select name="status" id="edit_status" class="form-control form-select"
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <option value="Active">Active</option>
                        <option value="Archived">Archived</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-qr-code" style="color: var(--primary);"></i>
                        QR Token
                    </label>
                    <input type="text" id="edit_qr_token" class="form-control" readonly
                           style="padding: 0.75rem; border-radius: var(--radius-md); background: var(--bg-secondary);">
                    <small style="color: var(--text-muted);">QR Token is auto-generated and cannot be changed</small>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="ph ph-x"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="ph ph-check"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openMemberModal() {
    const modal = document.getElementById('addMemberModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Focus on first input
    setTimeout(() => {
        document.querySelector('#addMemberForm input[name="fullname"]').focus();
    }, 100);
}

function closeMemberModal() {
    const modal = document.getElementById('addMemberModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('addMemberForm').reset();
}

function closeEditModal() {
    const modal = document.getElementById('editMemberModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('editMemberForm').reset();
}

// Close modals on backdrop click
window.addEventListener('click', function(event) {
    const addModal = document.getElementById('addMemberModal');
    const editModal = document.getElementById('editMemberModal');
    if (event.target === addModal) {
        closeMemberModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
});

// Close modals on Escape key
window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeMemberModal();
        closeEditModal();
    }
});

// Close modals on close button click
document.querySelectorAll('.close-modal').forEach(button => {
    button.addEventListener('click', function() {
        const modalId = this.getAttribute('data-modal');
        const modal = document.getElementById(modalId);
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (modalId === 'addMemberModal') {
            document.getElementById('addMemberForm').reset();
        } else {
            document.getElementById('editMemberForm').reset();
        }
    });
});

function openEditModalFromButton(btn) {
    const id = btn.dataset.id;
    const fullname = btn.dataset.fullname;
    const category = btn.dataset.category;
    const contact = btn.dataset.contact;
    const email = btn.dataset.email;
    const status = btn.dataset.status;
    const qr = btn.dataset.qr;
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_fullname').value = fullname;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_contact').value = contact;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_qr_token').value = qr;
    
    const modal = document.getElementById('editMemberModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    setTimeout(() => {
        document.getElementById('edit_fullname').focus();
    }, 100);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
