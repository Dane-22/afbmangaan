<?php
/**
 * Members Page - AFB Mangaan Attendance System
 */

$pageTitle = 'Members';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/functions/attendance_logic.php';
require_once __DIR__ . '/functions/report_engine.php';
require_once __DIR__ . '/functions/csrf.php';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            $pdo = getDB();
            $id = $_POST['id'] ?? null;
            $fullname = $_POST['fullname'] ?? '';
            $category = $_POST['category'] ?? 'WMO';
            $ministry = ($category === 'Ministers') ? ($_POST['ministry'] ?? null) : null;
            $contact = $_POST['contact'] ?? '';
            $email = $_POST['email'] ?? '';
            $status = $_POST['status'] ?? 'Active';
            
            try {
                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE attendees SET fullname=?, category=?, ministry=?, contact=?, email=?, status=? WHERE id=? AND church=?");
                    $stmt->execute([$fullname, $category, $ministry, $contact, $email, $status, $id, $_SESSION['church'] ?? 'AFB Mangaan']);
                    $message = 'Member updated successfully';
                    logActivity($_SESSION['user_id'], 'MEMBER_UPDATE', "Updated member ID: {$id}");
                } else {
                    // Insert
                    $qrToken = generateQRToken(rand(1000, 9999));
                    $stmt = $pdo->prepare("INSERT INTO attendees (church, fullname, category, ministry, contact, email, qr_token, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['church'] ?? 'AFB Mangaan', $fullname, $category, $ministry, $contact, $email, $qrToken, $status]);
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

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

// Count total matching members
$countSql = "SELECT COUNT(*) as total FROM attendees WHERE church = ?";
$countParams = [$church];
if ($search) {
    $countSql .= " AND (fullname LIKE ? OR qr_token LIKE ? OR contact LIKE ?)";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
    $countParams[] = "%{$search}%";
}
if ($categoryFilter) {
    $countSql .= " AND category = ?";
    $countParams[] = $categoryFilter;
}
if ($statusFilter) {
    $countSql .= " AND status = ?";
    $countParams[] = $statusFilter;
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalMembers = $countStmt->fetch()['total'] ?? 0;
$totalPages = ceil($totalMembers / $perPage);

$offset = ($page - 1) * $perPage;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Get categories for filter
$categories = ['MCYO', 'WMO', 'CCMO', 'KIDS', 'Visitors', 'Pastors', 'Leaders', 'Ministers', 'Other'];

// Predefined ministries list for Ministers category
$ministries = [
    'Music Ministry',
    'Pastoral Care',
    'Youth and Children\'s Ministry',
    'Usher Ministry',
    'Deacon Ministry',
    'Dance Ministry',
    'Multimedia Ministry',
    'Other'
];

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
$addMode = isset($_GET['action']) && $_GET['action'] === 'add' && empty($message);
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
                (<?php echo $totalMembers; ?> found)
            </span>
        </h3>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" class="btn btn-sm btn-primary" onclick="openMemberModal()">
                <i class="ph ph-plus"></i> Add Member
            </button>
            <a href="api/export_members.php" class="btn btn-sm btn-secondary">
                <i class="ph ph-download"></i> Export
            </a>
        </div>
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
                            <td data-label="Category">
                                <?php echo htmlspecialchars($member['category']); ?>
                                <?php if ($member['category'] === 'Ministers' && !empty($member['ministry'])): ?>
                                    <br><small style="color: var(--text-muted);"><i class="ph ph-briefcase"></i> <?php echo htmlspecialchars($member['ministry']); ?></small>
                                <?php endif; ?>
                            </td>
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
                                            data-category="<?php echo htmlspecialchars($member['category'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-ministry="<?php echo htmlspecialchars($member['ministry'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            data-contact="<?php echo htmlspecialchars($member['contact'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-status="<?php echo $member['status']; ?>"
                                            data-qr="<?php echo $member['qr_token']; ?>"
                                            onclick="openEditModalFromButton(this)">
                                        <i class="ph ph-pencil"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this member?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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
                            <span class="detail-value">
                                <?php echo htmlspecialchars($member['category']); ?>
                                <?php if ($member['category'] === 'Ministers' && !empty($member['ministry'])): ?>
                                    <small style="display: block; color: var(--text-muted);">(<?php echo htmlspecialchars($member['ministry']); ?>)</small>
                                <?php endif; ?>
                            </span>
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
                                data-category="<?php echo htmlspecialchars($member['category'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-ministry="<?php echo htmlspecialchars($member['ministry'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-contact="<?php echo htmlspecialchars($member['contact'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-email="<?php echo htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-status="<?php echo $member['status']; ?>"
                                data-qr="<?php echo $member['qr_token']; ?>"
                                onclick="openEditModalFromButton(this)">
                            <i class="ph ph-pencil"></i> Edit
                        </button>
                        <form method="POST" style="display: contents;" onsubmit="return confirm('Archive this member?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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
        
        <!-- Pagination UI -->
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

<!-- Add Member Modal -->
<div id="addMemberModal" class="modal" style="display: <?php echo $addMode ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--modal-backdrop, rgba(0,0,0,0.75)); background-color: var(--modal-backdrop, rgba(0,0,0,0.75)); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
    <div class="modal-content" style="background: var(--card-bg, #161616); background-color: var(--card-bg, #161616); color: var(--text-primary); border: 1px solid var(--border-primary); border-radius: var(--radius-lg); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
        <div class="modal-header" style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
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
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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
                    <select name="category" id="add_category" class="form-control form-select" required
                            onchange="toggleMinistryField('add')"
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="add_ministry_group" style="margin-bottom: 1rem; display: none;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-briefcase" style="color: var(--primary);"></i>
                        Ministry *
                    </label>
                    <select name="ministry" id="add_ministry" class="form-control form-select"
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <option value="">Select Ministry</option>
                        <?php foreach ($ministries as $min): ?>
                            <option value="<?php echo $min; ?>"><?php echo $min; ?></option>
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
                
                <div style="background: var(--divine-glow); border: 1px solid rgba(201, 162, 39, 0.25); padding: 1rem; border-radius: var(--radius-md); margin-top: 1rem;">
                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ph ph-info" style="color: var(--primary);"></i>
                        QR Code will be auto-generated when you save.
                    </p>
                </div>
            </div>
            <div class="modal-footer" style="padding: 1rem 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
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
<div id="editMemberModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: var(--modal-backdrop, rgba(0,0,0,0.75)); background-color: var(--modal-backdrop, rgba(0,0,0,0.75)); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
    <div class="modal-content" style="background: var(--card-bg, #161616); background-color: var(--card-bg, #161616); color: var(--text-primary); border: 1px solid var(--border-primary); border-radius: var(--radius-lg); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
        <div class="modal-header" style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
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
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
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
                            onchange="toggleMinistryField('edit')"
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="edit_ministry_group" style="margin-bottom: 1rem; display: none;">
                    <label class="form-label" style="display: flex; align-items: center; gap: 0.25rem;">
                        <i class="ph ph-briefcase" style="color: var(--primary);"></i>
                        Ministry *
                    </label>
                    <select name="ministry" id="edit_ministry" class="form-control form-select"
                            style="padding: 0.75rem; border-radius: var(--radius-md);">
                        <option value="">Select Ministry</option>
                        <?php foreach ($ministries as $min): ?>
                            <option value="<?php echo $min; ?>"><?php echo $min; ?></option>
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
            <div class="modal-footer" style="padding: 1rem 1.5rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
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
function toggleMinistryField(type) {
    const categorySelect = document.getElementById(type + '_category');
    const ministryGroup = document.getElementById(type + '_ministry_group');
    const ministrySelect = document.getElementById(type + '_ministry');

    if (categorySelect && categorySelect.value === 'Ministers') {
        if (ministryGroup) ministryGroup.style.display = 'block';
        if (ministrySelect) ministrySelect.required = true;
    } else {
        if (ministryGroup) ministryGroup.style.display = 'none';
        if (ministrySelect) {
            ministrySelect.required = false;
            ministrySelect.value = '';
        }
    }
}

function openMemberModal() {
    const modal = document.getElementById('addMemberModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    toggleMinistryField('add');
    
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
    toggleMinistryField('add');
}

function closeEditModal() {
    const modal = document.getElementById('editMemberModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('editMemberForm').reset();
    toggleMinistryField('edit');
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
            toggleMinistryField('add');
        } else {
            document.getElementById('editMemberForm').reset();
            toggleMinistryField('edit');
        }
    });
});

function openEditModalFromButton(btn) {
    const id = btn.dataset.id;
    const fullname = btn.dataset.fullname;
    const category = btn.dataset.category;
    const ministry = btn.dataset.ministry;
    const contact = btn.dataset.contact;
    const email = btn.dataset.email;
    const status = btn.dataset.status;
    const qr = btn.dataset.qr;
    
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_fullname').value = fullname;
    document.getElementById('edit_category').value = category;
    
    toggleMinistryField('edit');
    if (ministry && document.getElementById('edit_ministry')) {
        document.getElementById('edit_ministry').value = ministry;
    }
    
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
