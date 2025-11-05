<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

// Handle booking status update
if (isset($_POST['update_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE bookings SET status = '$status' WHERE id = $bookingId");
    header('Location: bookings.php?msg=status_updated');
    exit();
}

// Fetch all bookings with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$totalPages = ceil($totalBookings / $perPage);

$bookings = $conn->query("SELECT b.*, e.name as equipment_name, u.name as renter_name, u.email as renter_email, u.phone as renter_phone 
FROM bookings b 
JOIN equipment e ON b.equipment_id = e.id 
JOIN users u ON b.renter_id = u.id 
ORDER BY b.created_at DESC LIMIT $offset, $perPage");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
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
                        <a class="nav-link" href="equipment.php">Equipment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php">Bookings</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="/auth/logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <h1 class="admin-title">Booking Management</h1>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated'): ?>
            <div class="alert alert-success">Booking status has been successfully updated.</div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Equipment</th>
                                <th>Renter</th>
                                <th>Contact</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($booking = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['renter_name']); ?></td>
                                <td>
                                    <small class="d-block"><?php echo htmlspecialchars($booking['renter_email']); ?></small>
                                    <small class="d-block"><?php echo htmlspecialchars($booking['renter_phone']); ?></small>
                                </td>
                                <td>
                                    <small class="d-block">From: <?php echo date('M d, Y', strtotime($booking['start_date'])); ?></small>
                                    <small class="d-block">To: <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></small>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <select name="status" class="admin-select" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $booking['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $booking['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination admin-pagination justify-content-center">
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