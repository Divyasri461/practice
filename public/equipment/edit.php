<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../auth/login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];
$equipment_id = intval($_GET['id'] ?? 0);
$success = $error = '';

// Fetch current equipment details
$sql = "SELECT * FROM equipment WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $equipment_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$equipment = $result->fetch_assoc();

if (!$equipment) {
    die("Equipment not found or access denied.");
}

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $daily_rate = floatval($_POST['daily_rate']);
    $weekly_rate = floatval($_POST['weekly_rate']);
    $monthly_rate = floatval($_POST['monthly_rate']);

    // Optional: update image if uploaded
    $image_url = $equipment['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "../assets/images/equipment/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_url = $target_dir . time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_url);
    }

    $sql = "UPDATE equipment SET name=?, category=?, description=?, daily_rate=?, weekly_rate=?, monthly_rate=?, image_url=? WHERE id=? AND owner_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssdddssi', $name, $category, $description, $daily_rate, $weekly_rate, $monthly_rate, $image_url, $equipment_id, $owner_id);

    if ($stmt->execute()) {
        $success = 'Equipment updated successfully.';
        // Refresh equipment data
        $equipment = array_merge($equipment, compact('name', 'category', 'description', 'daily_rate', 'weekly_rate', 'monthly_rate', 'image_url'));
        // Redirect back after successful update
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'manage.php'));
        exit();

    } else {
        $error = 'Failed to update equipment.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Equipment - Agri-Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Edit Equipment</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Equipment Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($equipment['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
                <?php
                $categories = ["Tractor", "Harvester", "Plough", "Seeder", "Other"];
                foreach ($categories as $cat) {
                    $selected = $equipment['category'] === $cat ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($equipment['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="daily_rate" class="form-label">Daily Rate ($)</label>
            <input type="number" step="0.01" class="form-control" id="daily_rate" name="daily_rate" value="<?= $equipment['daily_rate'] ?>" required>
        </div>
        <div class="mb-3">
            <label for="weekly_rate" class="form-label">Weekly Rate ($)</label>
            <input type="number" step="0.01" class="form-control" id="weekly_rate" name="weekly_rate" value="<?= $equipment['weekly_rate'] ?>" required>
        </div>
        <div class="mb-3">
            <label for="monthly_rate" class="form-label">Monthly Rate ($)</label>
            <input type="number" step="0.01" class="form-control" id="monthly_rate" name="monthly_rate" value="<?= $equipment['monthly_rate'] ?>" required>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Change Image (optional)</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
            <?php if ($equipment['image_url']): ?>
                <img src="<?= $equipment['image_url'] ?>" class="mt-2" alt="Equipment Image" width="150">
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Update Equipment</button>
        <a href="manage.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
