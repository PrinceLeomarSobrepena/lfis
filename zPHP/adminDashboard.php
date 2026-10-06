<?php 
include '../middleware/adminMiddleware.php';
include '../config/db.php';

$admin = $_SESSION['admin'];

/* ✅ ALWAYS FETCH FRESH DATA */
$adminId = $admin['id'];

$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
/*gumagawa ako sysytem usning php tas napansin ko sa isang website bago mag load yung system may nalabas na cloudflare ata yun tas verifying  paano gawin yun*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
<title>Document</title>
<style>
@media (min-width: 1200px) {
    .custom-container {
        padding-left: 109px;
        padding-right: 109px;
    }
}

    /* BRAND */
.navbar-brand {
  font-family: 'Poppins', sans-serif;
  font-weight: 600;
  font-size: 24px;
  /* background: linear-gradient(90deg, #00c4cc, #7d2ae8); */
    background: linear-gradient(90deg, #00c4cc, #7d2ae8, #e8892a );
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-left: -15px;
}

/* GLOBAL NAV LINK — SAME FOR DESKTOP & MOBILE */
.nav-link {
  font-size: 16px !important;
  line-height: 1.5;
  padding: 8px 0;
}

/* SPACE BETWEEN DESKTOP NAV ITEMS */
.navbar-nav > .nav-item {
  margin-right: 16px;
}

.toggle{
    padding: 2px 5px;
    
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.toggle:hover{
    background-color: #f5f7f9;
    border-color: #c5cbd3;
}

#profileDropdown::after {
    display: none;
}

/* CUSTOM WIDTH FOR MOBILE OFFCANVAS */
#mobileMenu {
  width: 100%;          /* pwede 70%, 80%, bahala ka */
  max-width: 330px;    /* para di sobrang laki sa tablet */
}

@media(max-width: 992px){
    .btn-hidden{
        display: none;
    }
    .nav-link {
        font-size: 19px !important;
        margin-left: 15px;
    }
    .navbar-brand{
        margin-left: -2px !important;
    }
}

    footer {
        margin-top: auto;
        padding: 20px 0;
        font-size: 14px;
    }

    /* HR NA MAY MARGIN (BAGO FOOTER) */
    .content-hr {
        border-top: 1px solid inherit;
        margin: 60px auto 30px;
    }
</style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar bg-white sticky-top" style="border-bottom: 1px solid #dbdfe1;">
        <div class="container px-4  d-flex align-items-center"><!--px-lg-2-->
            <!-- LEFT SIDE -->
            <div class="d-flex align-items-center">
                <!-- TOGGLE -->
                <button class="btn d-lg-none toggle me-2"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0" href="#">OCRs</a>
            </div>

            <!-- DESKTOP NAV -->
            <ul class="navbar-nav mx-auto d-none d-lg-flex flex-row">
                <li class="nav-item"><a class="nav-link" href="customerDashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="customerReservationCalendar.php">Calendar</a></li>
                <li class="nav-item"><a class="nav-link" href="customerReservationCreate.php">Add-Reservation</a></li>
                <li class="nav-item"><a class="nav-link" href="customerReservationList.php">Reservations</a></li>
            </ul>

            <!-- Profile Dropdown -->
                <div class="dropdown "> <!-- d-none d-lg-inline -->
                    <a href="#" class="d-flex align-items-center gap-3 link-dark text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span>Hi,  <?= htmlspecialchars($admin['firstName']) ?></span>
                        <img src="../public/img/shidou.png" alt="Customer Image" class="rounded-circle border" width="150" height="150" style="object-fit: cover; width: 35px; height: 35px">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end text-small mt-3" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="customerReservationProfile.php">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="adminLogout.php">Log-out</a></li>
                    </ul>
                </div>
        </div>
    </nav>

    <!-- OFFCANVAS (MOBILE MENU) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body" style="color: red;">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="customerDashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="customerReservationCalendar.php">Calendar</a></li>
                <li class="nav-item"><a class="nav-link" href="customerReservationCreate.php">Add-Reservation</a></li>
                <li class="nav-item"><a class="nav-link" href="customerReservationList.php">Reservations</a></li>
            </ul>
        </div>
    </div>


    <!-- <a href="adminCustomerList.php" class="btn btn-primary">Customer</a><br><br>
    <a href="adminCategoryList.php">category</a><br>
    <a href="adminMenuList.php">Menu</a><br><br>
    <a href="adminOccasionList.php">occasion</a><br><br>
    <a href="adminServiceList.php">service</a><br><br>
      <a href="adminGuestList.php">Guest</a><br><br>

    <a href="adminMapList.php">location</a><br><br>

    <a href="reservationList.php">reservationList.php</a> <a href="reservationCalendar.php">reservationCalendar.php</a><br><br>

    <a href="feedbaclList.php">feedbaclList.php</a><br><br>

    <a href="adminLogout.php" class="btn btn-danger">Logout</a>
 -->



 <!-- MAIN CONTENT -->
<div class="main-content py-5 mt-5">
    <div class="container">

        <div class="row g-4">

            <!-- CARD 1 -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <img src="https://picsum.photos/400/200?1" class="card-img-top" alt="Image">
                    <div class="card-body">
                        <h5 class="card-title">Card Title 1</h5>
                        <p class="card-text">Sample description ng card. Pwede mo lagyan ng info.</p>
                        <a href="#" class="btn btn-success">View</a>
                    </div>
                </div>
            </div>

            <!-- CARD 2 -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <img src="https://picsum.photos/400/200?2" class="card-img-top" alt="Image">
                    <div class="card-body">
                        <h5 class="card-title">Card Title 2</h5>
                        <p class="card-text">Another content description dito.</p>
                        <a href="#" class="btn btn-success">View</a>
                    </div>
                </div>
            </div>

            <!-- CARD 3 -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <img src="https://picsum.photos/400/200?3" class="card-img-top" alt="Image">
                    <div class="card-body">
                        <h5 class="card-title">Card Title 3</h5>
                        <p class="card-text">Pwede mo palitan yung image at text.</p>
                        <a href="#" class="btn btn-success">View</a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>



<!-- MAIN CONTAINER -->
<div class="container mt-5">
    <h1 class="mb-4 text-center">Our Items</h1>

    <div class="row">

        <!-- CARD 1 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 1</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 2 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 2</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 3 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 3</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 4 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 4</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 5 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 5</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 6 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 6</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 7 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 7</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 8 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 8</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

        <!-- CARD 9 -->
        <div class="col-md-4 mb-4">
        <div class="card">
            <img src="https://via.placeholder.com/300x200" class="card-img-top">
            <div class="card-body">
            <h5 class="card-title">Card 9</h5>
            <p class="card-text">Sample description.</p>
            </div>
        </div>
        </div>

    </div>
</div>




    <!-- HR BEFORE FOOTER (MAY LEFT & RIGHT MARGIN NA) -->
    <div class="container custom-container">
        <hr class="content-hr mx-3">
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container custom-container" style="margin-top: -20px">
            <div class="d-flex justify-content-between align-items-end flex-wrap mx-3">

                <!-- LEFT -->
                <div class="mb-5">
                    <h4 class="mb-2">Kirisaki Group</h4>
                    <div class="d-flex gap-3">
                        <span class="text-decoration-none text-muted">Catering</span> 
                <span class="text-decoration-none text-muted">|</span> 
                        <span class="text-decoration-none text-muted">Reservation</span>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="text-muted mb-5">
                    © 2026, Kirisaki Group Powered by Prince
                            
                </div>

            </div>
        </div>
    </footer>

    <script>
        // check session every 10 seconds
        setInterval(() => {
            fetch('../middleware/adminAutoLogout.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.active) {
                        window.location.href = 'adminLogin.php';
                    }
                })
                .catch(err => console.error(err));
        }, 900000); // Every 15 mins and 5 seconds
    </script>
    <!-- Weak "anti-inspect" tricks (NOT secure) -->
    <script>
    // Disable right click


    // Disable F12 / Ctrl+Shift+I (easy to bypass)
    // document.addEventListener('keydown', function(e) {
    //     if (e.key === "F12" || 
    //         (e.ctrlKey && e.shiftKey && e.key === "I")) {
    //     e.preventDefault();
    //     }
    // });
    </script>

</body>
</html>