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
                $stmt = $pdo->prepare("UPDATE attendees SET fullname=?, category=?, contact=?, email=?, status=? WHERE id=?");
                $stmt->execute([$fullname, $category, $contact, $email, $status, $id]);
                $message = 'Member updated successfully';
                logActivity($_SESSION['user_id'], 'MEMBER_UPDATE', "Updated member ID: {$id}");
            } else {
                // Insert
                $qrToken = generateQRToken(rand(1000, 9999));
                $stmt = $pdo->prepare("INSERT INTO attendees (fullname, category, contact, email, qr_token, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fullname, $category, $contact, $email, $qrToken, $status]);
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

$sql = "SELECT * FROM attendees WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (fullname LIKE ? OR qr_token LIKE ? OR contact LIKE ?)";
    $searchParam = "%{$search}%";
    $params = [$searchParam, $searchParam, $searchParam];
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
$categories = ['Youth', 'Adult', 'Senior', 'Child'];

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
            
            <a href="?action=add" class="btn btn-success">
                <i class="ph ph-plus"></i> Add Member
            </a>
        </form>
    </div>
</div>

<?php if ($addMode || $editMode): ?>
    <!-- Add/Edit Form -->
    <div class="card animate__animated animate__fadeIn" style="margin-top: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="ph ph-user<?php echo $editMode ? '' : '-plus'; ?>"></i>
                <?php echo $editMode ? 'Edit Member' : 'Add New Member'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $editMode ? 'edit' : 'add'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo $editMember['id']; ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="fullname" class="form-control" required 
                               value="<?php echo $editMode ? htmlspecialchars($editMember['fullname']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-control form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($editMode && $editMember['category'] === $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact" class="form-control" 
                               value="<?php echo $editMode ? htmlspecialchars($editMember['contact']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo $editMode ? htmlspecialchars($editMember['email']) : ''; ?>">
                    </div>
                </div>
                
                <?php if ($editMode): ?>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control form-select">
                            <option value="Active" <?php echo $editMember['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Archived" <?php echo $editMember['status'] === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">QR Token</label>
                        <input type="text" class="form-control" value="<?php echo $editMember['qr_token']; ?>" readonly>
                        <small style="color: var(--text-muted);">QR Token is auto-generated</small>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk"></i> Save Member
                    </button>
                    <a href="members.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

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
        <div class="table-container">
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
                            <td><?php echo $member['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($member['fullname']); ?></strong>
                                <?php if ($member['email']): ?>
                                    <br><small><?php echo htmlspecialchars($member['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $member['category']; ?></td>
                            <td><?php echo htmlspecialchars($member['contact']); ?></td>
                            <td><code><?php echo $member['qr_token']; ?></code></td>
                            <td>
                                <span class="badge badge-<?php echo $member['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $member['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="?edit=<?php echo $member['id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="ph ph-pencil"></i>
                                    </a>
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
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
