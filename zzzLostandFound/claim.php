<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lost And Found Information System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
* {
    margin:0;
    padding:0;
    box-sizing:border-box;

}
body {
    font-family:'Nunito', Arial, sans-serif;
    background:#f5f6fa;
    color:#0F1B2D;
    overflow-x:hidden;
}

a {
    text-decoration:none;
}

/* ====== NAVBAR TOP ====== */
.navbar-top {
    position: fixed;
    top: 0;
    left: 10px;   /*290px SAME as navbar margin-left */
    right: 10px;   /*15px SAME as navbar margin-right */
    height: 74px;
    background: #f5f6fa;
    /* border: 1px solid #104a84; */
    border-radius: 0 0 12px 12px;
    z-index: 1010;
}

/* ====== NAVBAR ====== */
.navbar{
    position: fixed;
    top: 15px;
    left: 15px;
    right: 15px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 14px 25px;
    display: flex;
    justify-content: space-between;
    align-items:center;
    z-index: 1020;
}

.brand {
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:900;
    font-size:17px;
    color:#0F1B2D;
}
.brand i {
    width:38px;
    height:38px;
    background:linear-gradient(135deg, #198754, #147a49);
    color:#fff;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:17px;
}

.navbar-right {
    display: flex;
    gap: 28px;
    align-items: center;
}

.navbar-right a {
    color: #4B5A54;
    font-weight: 700;
    font-size: 14.5px
}

.navbar-right a:hover {
    color: #198754;
}

.navbar-right a.active{
    color: #147a49;
     font-weight:900;
}

.menu-toggle{display:none;font-size:32px;color:#4B5A54;cursor:pointer;}

/* ======== SECTION SHARED ========= */
/* ======== SECTION SHARED ========= */
.section{
    max-width: 1150px;
    margin:0 auto;
    padding:60px 15px;
    /* border: 1px solid red; */
}

.page-heading{
    font-weight:800;
    font-size:21px;
    margin-bottom:2px;
    color: #0F1B2D;
}

.page-subheading{
    font-size:13px;
    color: #7C8A85;
    font-weight:600;
}


/* ============ FEATURES ============ */
.feat-card{
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:16px;
    padding:26px 22px;
    /* height:100%; */
    transition:.2s ease;
}

/* .feat-card:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 30px rgba(20,60,40,0.08);
    border-color: #dcefe4;
} */

.feat-icon{
    display:flex;
    width:46px;
    height:46px;
    align-items:center;
    justify-content:center;
    font-size:19px;
    border-radius:12px;
    margin-bottom:16px;
}

.feat-card h6{
    font-weight:800;
    font-size:15px;
    margin-bottom:8px;
}

/* .feat-card p{
    font-weight:600;
    font-size:12.8px;
    color: #7C8A85;
    margin-bottom:0;
    border:1px solid red;
} */
.feat-card .thank-you-note {
    text-align: center;
    font-weight: 600;
    font-size: 13.8px;
    color: #7C8A85;
    padding:30px 0px;
    margin-top: 20px;
    margin-bottom: 0;
}

.feat-card p.bring-label{
    color : #198754;
    text-transform:uppercase;
    letter-spacing:.4px;
    font-size:11.5px;
    font-weight:800;
    margin-bottom:16px;
}

.bring-list{
    list-style:none;
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:4px;
}

.bring-list li{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:700;
    font-size:13.5px;
    color: #0F1B2D;
    padding-bottom:10px;
    border-bottom:1px solid #E7ECE9;
}

.bring-list li:last-child{
    border-bottom:none;
    padding-bottom:0;
}

.bring-list li i{
    color:#198754;
    font-size:16px;
    flex-shrink:0;
}

/* ============ Hours ============ */
.hours-box{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:6px;
}

.hours-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
}

.hours-row .days{
    font-weight:700;
    font-size:12.8px;
    color:#0F1B2D;
}

.hours-row .time{
    font-weight:800;
    font-size:12px;
    color:#B8860B;
    background:#FFF4E5;
    padding:5px 10px;
    border-radius:8px;
    white-space:nowrap;
}

.hours-note{
    font-weight:600;
    font-size:12px;
    color: #7C8A85;
    margin-top:2px;
}

/* ============ sidebar right ============ */
.sidebar-sticky-right{
    position:sticky;
    top:110px;
}

/* ============ FOOTER ============ */
footer{
    max-width:1150px;
    margin:0 auto;
    padding:40px 15px 30px;
    /* border: 1px solid red; */
}

.footer-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding-bottom:24px;
    border-bottom:1px solid #E7ECE9;
    flex-wrap:wrap;
    gap:16px;
}

.footer-links{
    display:flex;
    gap:24px;
    flex-wrap:wrap;
}

.footer-links a{
    font-weight:700;
    font-size:12.5px;
    color:#7C8A85;
}

.footer-links a:hover{ color:#198754; }

.footer-bottom{
    text-align:center;
    font-weight:600;
    font-size:11.5px;
    padding-top:24px;
    color:#9AA6A1;
}

/* ======== media 992px ========= */
@media(max-width: 992px){
    .navbar-top{
        display: none;
    }

    .navbar{
        position: fixed;
        top: 0px;
        left: 0px;
        right: 0px;
        border-radius: 0px;
        border: 0px;
        border-bottom: 1px solid #dee2e6;
    }

    .navbar-right{
        position:fixed;
        top:76px; /* 80px */
        left:0px; 
        right:0px;
        background:#fff;
        border:1px solid #dee2e6;
        border-radius:0px 0px 12px 12px;
        flex-direction:column;
        align-items:stretch;
        padding:16px;
        gap:6px;
        display:none;
        z-index:999;
    }

    .navbar-right.show{
        display:flex;
    }

    .navbar-right a{
        padding:10px 8px;border-radius:8px;
    }

    .navbar-right a:hover{
          background-color: #f9f9f9;
    }

     .menu-toggle{
        display:block;
    }

    .flow-wrap{
        grid-template-columns:1fr;
        gap:14px;
    }

    .flow-step{
        margin:0;
    }

    section{
        margin-top:20px !important;
    }
}
</style>
</head>
<body>

    <div id="overlay"></div>
    <div class="navbar-top"></div>

    <div class="navbar shadow-sm">
        <div class="brand">
            <i class="bi bi-search-heart"></i>
            L&nbsp;&nbsp;F&nbsp;<span class="text-success" style="margin-left: -5px;">I &nbsp;S</span>
        </div>

        <div class="navbar-right" id="navbarLinks">
            <a href="index.php">Home</a>
            <a href="found.php" >Found</a>
            <a href="lost.php">Lost</a>
            <a href="claim.php" class="active">Claim Item</a>
            <a href="missing-report.php">How to report</a>
            <!-- <a href="terms.php" class="btn-login mb-3 mb-lg-0"><i class="bi bi-shield-lock" style="margin-right:6px;"></i>Term's and Conditions</a> -->
        </div>

         <i class="bi bi-list menu-toggle" onclick="document.getElementById('navbarLinks').classList.toggle('show')"></i>
    </div>


    <!-- FEATURES -->
    <section class="section" style="margin-top: 40px">
        <div class="mt-4 mb-3">
            <div class="page-heading">Claim Your Item</div>
            <div class="page-subheading">Follow these steps to retrieve your lost item from the Lost and Found Office.</div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8 col-md-8">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #E8F7EF;color: #198754;"><i class="bi bi-layers"></i></div>
                    <h6>To claim your item, bring the following.</h6>
                    <p class="bring-label">What to bring</p>
                    <ul class="bring-list">
                        <li><i class="bi bi-check2-circle"></i> Student ID</li>
                        <li><i class="bi bi-check2-circle"></i> Proof of ownership</li>
                        <li><i class="bi bi-check2-circle"></i> Valid identification</li>
                         <li><i class="bi bi-check2-circle"></i> If a parent/guardian is claiming the item, bring the student's ID as well</li>
                    </ul>
                    <p class="thank-you-note bg-light">
                        Thank you for helping make the claiming process smooth and efficient.<br>
                        <span style="font-size: 12.8px;  font-weight: 700;">-Dr. Gloria D. Lacson Foundation Colleges, Inc.</span>
                    </p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="sidebar-sticky-right">
                    <div class="feat-card">
                        <div class="feat-icon" style="background: #FFF4E5;color: #B8860B;"><i class="bi bi-calendar-event"></i></div>
                        <h6>Office hours</h6>
                        <div class="hours-box">
                            <div class="hours-row">
                                <span class="days">Monday–Friday</span>
                                <span class="time">8:00 AM – 5:00 PM</span>
                            </div>
                            <p class="hours-note">Closed weekends and holidays</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- FOOTER -->
    <footer style="margin-top: -20px">
        <div class="footer-top">
            <div class="brand">
                <i class="bi bi-search-heart"></i>
                L&nbsp;&nbsp;F&nbsp;<span class="text-success" style="margin-left: -5px;">I &nbsp;S</span>
            </div>
            <div class="footer-links">
                <a href="#how">How it works</a>
                <a href="#features">Features</a>
                <a href="#stats">Impact</a>
                <a href="#"><i class="bi bi-shield-lock" style="margin-right:4px;"></i>Staff Login</a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 Lost And Found Information System. All rights reserved.
        </div>
    </footer>

<script>
    // Redirect after exactly 10 minutes
    setTimeout(function () {
        window.location.href = "https://www.google.com";
    }, 15 * 60 * 1000);
</script>


</body>
</html>