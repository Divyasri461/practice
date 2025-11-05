<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

// Handle equipment deletion
if (isset($_POST['delete_equipment'])) {
    $equipmentId = (int)$_POST['delete_equipment'];
    $conn->query("DELETE FROM equipment WHERE id = $equipmentId");
    header('Location: equipment.php?msg=equipment_deleted');
    exit();
}

// Handle equipment availability toggle
if (isset($_POST['toggle_availability'])) {
    $equipmentId = (int)$_POST['equipment_id'];
    $available = (int)$_POST['available'];
    $conn->query("UPDATE equipment SET available = $available WHERE id = $equipmentId");
    header('Location: equipment.php?msg=availability_updated');
    exit();
}

// Fetch all equipment with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalEquipment = $conn->query("SELECT COUNT(*) as count FROM equipment")->fetch_assoc()['count'];
$totalPages = ceil($totalEquipment / $perPage);

$equipment = $conn->query("SELECT e.*, u.name as owner_name FROM equipment e JOIN users u ON e.owner_id = u.id ORDER BY e.created_at DESC LIMIT $offset, $perPage");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/index.php">Admin Dashboard</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="equipment.php">Equipment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">Bookings</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="/auth/logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Equipment Management</h1>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'equipment_deleted'): ?>
                <div class="alert alert-success">Equipment has been successfully deleted.</div>
            <?php elseif ($_GET['msg'] === 'availability_updated'): ?>
                <div class="alert alert-success">Equipment availability has been updated.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Owner</th>
                                <th>Category</th>
                                <th>Daily Rate</th>
                                <th>Status</th>
                                <th>Listed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $equipment->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['owner_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>$<?php echo number_format($item['daily_rate'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="equipment_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="available" value="<?php echo $item['available'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_availability" class="btn btn-sm btn-<?php echo $item['available'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <a href="/equipment/view.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this equipment?');">
                                        <button type="submit" name="delete_equipment" value="<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>