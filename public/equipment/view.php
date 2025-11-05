<?php
session_start();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header('Location: browse.php');
    exit();
}

$equipment_id = intval($_GET['id']);
$success = $error = '';

// Get equipment details
$sql = "SELECT e.*, u.name as owner_name, u.phone as owner_phone 
        FROM equipment e 
        JOIN users u ON e.owner_id = u.id 
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: browse.php');
    exit();
}

$equipment = $result->fetch_assoc();

// Handle booking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] !== 'renter') {
        $error = 'Only renters can book equipment';
    } else {
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        $renter_id = $_SESSION['user_id'];

        // Check if equipment is available for the selected dates
        $sql = "SELECT id FROM bookings 
                WHERE equipment_id = ? 
                AND status IN ('pending', 'approved') 
                AND ((start_date BETWEEN ? AND ?) 
                OR (end_date BETWEEN ? AND ?))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issss', $equipment_id, $start_date, $end_date, $start_date, $end_date);
        $stmt->execute();
        $conflict_result = $stmt->get_result();

        if ($conflict_result->num_rows > 0) {
            $error = 'Equipment is not available for the selected dates';
        } else {
            $sql = "INSERT INTO bookings (equipment_id, renter_id, start_date, end_date) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiss', $equipment_id, $renter_id, $start_date, $end_date);

            if ($stmt->execute()) {
                $success = 'Booking request submitted successfully';
            } else {
                $error = 'Failed to submit booking request';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($equipment['name']); ?> - Agricultural Equipment Rental</title>
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
                        <a class="nav-link" href="browse.php">Browse Equipment</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../bookings/index.php">My Bookings</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/login.php">Login</a>
                        </li>
                    <?php endif; ?>
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

        <div class="row">
            <div class="col-md-6">
                <img src="<?php echo $equipment['image_url'] ?? '../assets/images/default-equipment.jpg'; ?>" 
                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($equipment['name']); ?>">
            </div>
            <div class="col-md-6">
                <h2><?php echo htmlspecialchars($equipment['name']); ?></h2>
                <p class="text-muted">Category: <?php echo htmlspecialchars($equipment['category']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($equipment['description'])); ?></p>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Rental Rates</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Daily Rate</h6>
                                <p class="h4">$<?php echo number_format($equipment['daily_rate'], 2); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6>Weekly Rate</h6>
                                <p class="h4">$<?php echo number_format($equipment['weekly_rate'], 2); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6>Monthly Rate</h6>
                                <p class="h4">$<?php echo number_format($equipment['monthly_rate'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Owner Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($equipment['owner_name']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($equipment['owner_phone']); ?></p>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'renter'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Book Equipment</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Booking Request</button>
                            </form>
                        </div>
                    </div>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <div class="alert alert-info">
                        Please <a href="../auth/login.php">login</a> to book this equipment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validate date selection
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');

            if (startDate && endDate) {
                startDate.addEventListener('change', function() {
                    endDate.min = this.value;
                });

                endDate.addEventListener('change', function() {
                    if (this.value < startDate.value) {
                        this.value = startDate.value;
                    }
                });
            }
        });
    </script>
</body>
</html>