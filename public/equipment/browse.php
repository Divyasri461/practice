<?php
session_start();
require_once '../../config/database.php';

// Get filter parameters
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build query
$sql = "SELECT e.*, u.name as owner_name 
        FROM equipment e 
        JOIN users u ON e.owner_id = u.id 
        WHERE e.available = 1";

if ($category) {
    $sql .= " AND e.category = '$category'";
}

if ($search) {
    $sql .= " AND (e.name LIKE '%$search%' OR e.description LIKE '%$search%')";
}

$sql .= " ORDER BY e.created_at DESC";

$result = $conn->query($sql);
$equipment_list = $result->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$sql = "SELECT DISTINCT category FROM equipment ORDER BY category";
$categories = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Equipment - Agricultural Equipment Rental</title>
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="../equipment/browse.php">Browse Equipment</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../bookings/index.php">My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/logout.php">Logout</a>
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
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Available Equipment</h2>
            </div>
            <div class="col-md-4">
                <form method="GET" action="" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search equipment..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filter by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="browse.php" class="list-group-item list-group-item-action <?php echo !$category ? 'active' : ''; ?>">All Categories</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="?category=<?php echo urlencode($cat['category']); ?>"
                                   class="list-group-item list-group-item-action <?php echo $category === $cat['category'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row">
                    <?php foreach ($equipment_list as $equipment): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm rounded-4 h-100 equipment-card">
                                <img src="<?php echo htmlspecialchars($equipment['image_url'] ?? '../assets/images/default-equipment.jpg'); ?>"
                                     class="card-img-top" alt="<?php echo htmlspecialchars($equipment['name']); ?>"
                                     style="height: 180px; width: 100%; object-fit: cover; border-top-left-radius: .75rem; border-top-right-radius: .75rem; display: block;">

                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-semibold mb-2"><?php echo htmlspecialchars($equipment['name']); ?></h5>
                                    <p class="card-text text-muted flex-grow-1" style="min-height: 3rem; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars(mb_strimwidth($equipment['description'], 0, 140, '...')); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="fw-bold text-success">$<?php echo number_format($equipment['daily_rate'], 2); ?></span><small class="text-muted"> / day</small><br>
                                            <small class="text-secondary">Owner: <?php echo htmlspecialchars($equipment['owner_name']); ?></small>
                                        </div>
                                        <a href="view.php?id=<?php echo $equipment['id']; ?>" class="btn btn-success btn-sm px-3" aria-label="View details of <?php echo htmlspecialchars($equipment['name']); ?>">Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                    <?php if (empty($equipment_list)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No equipment found matching your criteria.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>