<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Get featured equipment
$sql = "SELECT e.*, u.name as owner_name 
        FROM equipment e 
        JOIN users u ON e.owner_id = u.id 
        WHERE e.available = 1 
        ORDER BY e.created_at DESC 
        LIMIT 6";
$result = $conn->query($sql);
$featured_equipment = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultural Equipment Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styleex.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top bg-white">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">Agri-Rental</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item"><a class="nav-link" href="equipment/browse.php">Browse Equipment</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserRole() == 'owner'): ?>
                        <li class="nav-item"><a class="nav-link" href="equipment/manage.php">Manage</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="bookings/index.php">My Bookings</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-danger ms-2" href="?logout=1">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="auth/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-success ms-2" href="auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section text-white d-flex align-items-center justify-content-center">
    <div class="text-center px-3">
        <h1 class="display-4 fw-bold">Empowering Farmers with Easy Equipment Rentals</h1>
        <p class="lead mt-3 mb-4">Rent or lease modern agricultural tools from nearby owners with just a few clicks.</p>
        <a href="equipment/browse.php" class="btn btn-lg btn-success px-4 py-2">Browse Equipment</a>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="mb-4">Why Choose Agri-Rental?</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <div class="icon mb-3 fs-2 text-success"><i class="bi bi-clock-history"></i></div>
                    <h5>24/7 Booking Access</h5>
                    <p>Reserve equipment anytime with flexible rental durations.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <div class="icon mb-3 fs-2 text-success"><i class="bi bi-tools"></i></div>
                    <h5>Verified Equipment</h5>
                    <p>Only verified and quality-checked tools are listed on our platform.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 p-4 border-0 shadow-sm">
                    <div class="icon mb-3 fs-2 text-success"><i class="bi bi-cash-stack"></i></div>
                    <h5>Affordable Rates</h5>
                    <p>Best prices guaranteed — rent equipment near your location.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Equipment -->
<section class="container py-5">
    <h2 class="text-center mb-5">Featured Equipment</h2>
    <div class="row">
        <?php foreach ($featured_equipment as $equipment): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <img src="<?php echo $equipment['image_url'] ?? 'assets/images/default-equipment.jpg'; ?>"
                         class="card-img-top" alt="<?php echo htmlspecialchars($equipment['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($equipment['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($equipment['description'], 0, 80)) . '...'; ?></p>
                        <p class="card-text"><span class="text-success fw-bold">$<?php echo $equipment['daily_rate']; ?></span> /day</p>
                        <a href="equipment/view.php?id=<?php echo $equipment['id']; ?>" class="btn btn-outline-primary">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Call to Action -->
<section class="text-white py-5" style="background: linear-gradient(to right, #2c5282, #4CAF50);">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">List Your Equipment Today</h2>
        <p class="mb-4">Have unused farm tools? Earn extra income by renting them out.</p>
        <a href="#" class="btn btn-light btn-lg px-4">Get Started</a>
    </div>
</section>

<!-- Footer -->
<footer class="bg-white py-4 mt-5 border-top">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
        <p class="mb-0 text-muted">© 2025 Agri-Rental. All rights reserved.</p>
        <div>

            <a href="#" class="text-muted">Contact</a>
        </div>
    </div>
</footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>