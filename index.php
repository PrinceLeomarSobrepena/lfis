<?php
include 'config/db.php';

$result = $conn->query("SELECT * FROM lost_found ORDER BY created_at DESC LIMIT 3");

$total = $conn->query("SELECT COUNT(*) as cnt FROM lost_found")->fetch_assoc()['cnt'];
$claimed = $conn->query("SELECT COUNT(*) as cnt FROM lost_found WHERE is_claimed = 1")->fetch_assoc()['cnt'];
$unclaimed = $total - $claimed;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost & Found System</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Script Library -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
body{
    background: #f5f5f5;
    font-family: Arial, sans-serif;
}

/* NAVBAR */
.navbar{
    background: rgb(33, 33, 34)0;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.navbar-brand{
    font-weight: 700;
    letter-spacing: 0.5px;
    font-size: 20px;
}

.navbar .nav-link,
.navbar a{
    color: #e5e7eb !important;
    font-weight: 500;
    margin: 0 6px;
    transition: 0.2s ease;
}

.navbar .nav-link:hover,
.navbar a:hover{
    color: #ffffff !important;
    transform: translateY(-1px);
}

/* Buttons */
.navbar .btn{
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 13px;
    transition: 0.2s ease;
}

.navbar .btn:hover{
    transform: scale(1.05);
}

/* Active look (optional) */
.navbar .active{
    color: #60a5fa !important;
}

/* HERO */
.hero{
    background: linear-gradient(135deg, #0d6efd, #4f46e5);
    color: white;
    padding: 70px 20px;
    text-align: center;
}
.hero h1{
    font-size: 38px;
    font-weight: bold;
}
.hero p{
    opacity: 0.9;
}

/* STATS */
.stats-card{
    background: #fff;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

/* SEARCH */
.search-box{
    max-width: 500px;
    margin: auto;
}

/* CARD */
.lost-card{
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.10);
    padding: 12px;
    display: flex;
    gap: 15px;
    transition: 0.2s ease;
    height: 100%;
}
.lost-card:hover{
    transform: translateY(-3px);
}

.lost-image{
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 10px;
    flex-shrink: 0;
}

.lost-details{
    flex: 1;
}

.lost-title{
    font-size: 17px;
    font-weight: bold;
}

.lost-text{
    font-size: 14px;
    margin-bottom: 4px;
}

/* FOOTER */
.footer{
    background: #111827;
    color: #ccc;
    text-align: center;
    padding: 20px;
    margin-top: 40px;
}
</style>

</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top px-3">
    
    <a class="navbar-brand" href="#">
        🔍 Lost & Found
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">

        <ul class="navbar-nav ms-auto align-items-lg-center">

            <li class="nav-item">
                <a class="nav-link active" href="#">Home</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">Report Item</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">Browse</a>
            </li>

            <li class="nav-item ms-lg-3">
                <a href="#" class="btn btn-outline-light btn-sm">Login</a>
            </li>

        </ul>

    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <h1>Lost & Found System</h1>
    <p>Find your lost items or report found items easily and quickly</p>

    <!-- SEARCH -->
    <div class="search-box mt-4">
        <input type="text" class="form-control" placeholder="Search lost items...">
    </div>
</div>

<!-- STATS -->
<div class="container mt-4">
<div class="row g-3">

    <div class="col-md-4">
        <div class="stats-card">
            <h5>Total Items</h5>
            <h3><?= $total ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stats-card">
            <h5>Claimed</h5>
            <h3 class="text-success"><?= $claimed ?></h3>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stats-card">
            <h5>Unclaimed</h5>
            <h3 class="text-danger"><?= $unclaimed ?></h3>
        </div>
    </div>

</div>
</div>

<!-- CONTENT -->
<div class="container mt-5">

<h3 class="mb-3">Latest Posts</h3>

<div class="row g-3">

<?php while($row = $result->fetch_assoc()): ?>

<div class="col-12 col-md-6 col-lg-4">

    <div class="lost-card">

        <img src="uploads/<?= htmlspecialchars($row['image']) ?>" class="lost-image">

        <div class="lost-details">

            <div class="d-flex justify-content-between">
                <div class="lost-title">
                    <?= htmlspecialchars($row['category']) ?>
                </div>

                <div>
                    <?= $row['is_claimed']
                        ? '<span class="badge bg-success">Claimed</span>'
                        : '<span class="badge bg-secondary">Unclaimed</span>'
                    ?>
                </div>
            </div>

            <div class="lost-text">
                <strong>Location:</strong>
                <?= htmlspecialchars($row['reported_location']) ?>
            </div>

            <div class="lost-text">
                <strong>Date:</strong>
                <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
            </div>

        </div>

    </div>

</div>

<?php endwhile; ?>

</div>

</div>


   <button class="gcash-fab" data-bs-toggle="modal" data-bs-target="#gcashModal" 
            style="position: fixed; bottom: 30px; right: 20px; width: 60px; height: 60px; border-radius: 50%; background-color: #007dff; color: white; border: none; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; cursor: pointer; perspective: 600px;">
        <div class="fab-inner" style="width: 100%; height: 100%; position: relative; transform-style: preserve-3d; transition: transform 0.8s;">
            <!-- Front: Image -->
            <img src="../public/img/gcash.png" class="fab-front" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%; backface-visibility: hidden; position: absolute; top:0; left:0; transform: scale(1.7);">
           
            <!-- Back: Font Icon -->
            <i class="bi bi-qr-code-scan fab-back" style="font-size: 1.9rem; color: white; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; border-radius: 50%; backface-visibility: hidden; transform: rotateY(180deg); position: absolute; top:0; left:0;"></i>
        </div>
    </button>

    <!-- Modal -->
    <div class="modal fade" id="gcashModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">

                <div class="modal-header">
                    <!-- GCash Logo -->
                    <img src="../public/img/gcash.png" alt="GCash Logo" style="margin-left: -20px;">
                    <h5 class="modal-title mb-0" style="margin-left: -20px;">Scan to Pay via GCash</h5>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center mt-3">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=GCASH-PAYMENT-DEMO"
                        alt="GCash QR Code"
                        class="img-fluid qr-image mb-3">

                    <p class="text-muted">
                        Scan this QR code using your GCash app.
                    </p>

                </div>
            </div>
        </div>
    </div>



<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Lost & Found System | All rights reserved
</div>

</body>
</html>