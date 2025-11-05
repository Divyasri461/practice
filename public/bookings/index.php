<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = $error = '';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $role === 'owner') {
    $booking_id = intval($_POST['booking_id']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql = "UPDATE bookings b 
            JOIN equipment e ON b.equipment_id = e.id 
            SET b.status = ? 
            WHERE b.id = ? AND e.owner_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $status, $booking_id, $user_id);

    if ($stmt->execute()) {
        $success = 'Booking status updated successfully';
    } else {
        $error = 'Failed to update booking status';
    }
}

// Get bookings based on user role
if ($role === 'owner') {
    $sql = "SELECT b.*, e.name as equipment_name, e.daily_rate, 
                   u.name as renter_name, u.phone as renter_phone 
            FROM bookings b 
            JOIN equipment e ON b.equipment_id = e.id 
            JOIN users u ON b.renter_id = u.id 
            WHERE e.owner_id = ? 
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
} else {
    $sql = "SELECT b.*, e.name as equipment_name, e.daily_rate, 
                   u.name as owner_name, u.phone as owner_phone 
            FROM bookings b 
            JOIN equipment e ON b.equipment_id = e.id 
            JOIN users u ON e.owner_id = u.id 
            WHERE b.renter_id = ? 
            ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Agricultural Equipment Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Agri-Rental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <?php if ($role === 'owner'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../equipment/manage.php">Manage Equipment</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../equipment/browse.php">Browse Equipment</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2><?php echo $role === 'owner' ? 'Rental Requests' : 'My Bookings'; ?></h2>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th><?php echo $role === 'owner' ? 'Renter' : 'Owner'; ?></th>
                        <th>Dates</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <?php if ($role === 'owner'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $start = new DateTime($booking['start_date']);
                        $end = new DateTime($booking['end_date']);
                        $days = $end->diff($start)->days + 1;
                        $total_cost = $booking['daily_rate'] * $days;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['equipment_name']); ?></td>
                            <td>
                                <?php if ($role === 'owner'): ?>
                                    <?php echo htmlspecialchars($booking['renter_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['renter_phone']); ?></small>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($booking['owner_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['owner_phone']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> -<br>
                                <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                <br>
                                <small class="text-muted"><?php echo $days; ?> days</small>
                            </td>
                            <td>$<?php echo number_format($total_cost, 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $booking['status'] === 'approved' ? 'success' : 
                                         ($booking['status'] === 'rejected' ? 'danger' : 
                                         ($booking['status'] === 'completed' ? 'info' : 'warning')); 
                                    ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <?php if ($role === 'owner' && $booking['status'] === 'pending'): ?>
                                <td>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            <?php elseif ($role === 'owner'): ?>
                                <td>
                                    <?php if ($booking['status'] === 'approved'): ?>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-sm btn-info">Mark Complete</button>
                                        </form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="<?php echo $role === 'owner' ? '6' : '5'; ?>" class="text-center">
                                No bookings found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>