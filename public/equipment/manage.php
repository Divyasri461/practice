<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is an owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../auth/login.php');
    exit();
}

$success = $error = '';
$owner_id = $_SESSION['user_id'];

// Handle equipment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = $conn->real_escape_string($_POST['name']);
        $category = $conn->real_escape_string($_POST['category']);
        $description = $conn->real_escape_string($_POST['description']);
        $daily_rate = floatval($_POST['daily_rate']);
        $weekly_rate = floatval($_POST['weekly_rate']);
        $monthly_rate = floatval($_POST['monthly_rate']);

        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../assets/images/equipment/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image_url = $target_dir . time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $image_url);
        }

        $sql = "INSERT INTO equipment (owner_id, name, category, description, daily_rate, weekly_rate, monthly_rate, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssddds', $owner_id, $name, $category, $description, $daily_rate, $weekly_rate, $monthly_rate, $image_url);

        if ($stmt->execute()) {
            $success = 'Equipment added successfully';
        } else {
            $error = 'Failed to add equipment';
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['equipment_id'])) {
        $equipment_id = intval($_POST['equipment_id']);
        $sql = "DELETE FROM equipment WHERE id = ? AND owner_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $equipment_id, $owner_id);

        if ($stmt->execute()) {
            $success = 'Equipment deleted successfully';
        } else {
            $error = 'Failed to delete equipment';
        }
    }
}

// Get owner's equipment
$sql = "SELECT * FROM equipment WHERE owner_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$equipment_list = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - Agricultural Equipment Rental</title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="manage.php">Manage Equipment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../bookings/index.php">Bookings</a>
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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Add New Equipment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label for="name" class="form-label">Equipment Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select category...</option>
                                    <option value="Tractor">Tractor</option>
                                    <option value="Harvester">Harvester</option>
                                    <option value="Plough">Plough</option>
                                    <option value="Seeder">Seeder</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="daily_rate" class="form-label">Daily Rate ($)</label>
                                <input type="number" class="form-control" id="daily_rate" name="daily_rate" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="weekly_rate" class="form-label">Weekly Rate ($)</label>
                                <input type="number" class="form-control" id="weekly_rate" name="weekly_rate" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="monthly_rate" class="form-label">Monthly Rate ($)</label>
                                <input type="number" class="form-control" id="monthly_rate" name="monthly_rate" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Equipment Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Equipment</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h3>Your Equipment</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Daily Rate</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment_list as $equipment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['category']); ?></td>
                                    <td>$<?php echo number_format($equipment['daily_rate'], 2); ?></td>
                                    <td>
                                        <?php echo $equipment['available'] ? 
                                            '<span class="badge bg-success">Available</span>' : 
                                            '<span class="badge bg-warning">Rented</span>'; ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $equipment['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this equipment?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="equipment_id" value="<?php echo $equipment['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($equipment_list)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No equipment listed yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>