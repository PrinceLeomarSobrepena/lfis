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
    height:100%;
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

.feat-card p{
    font-weight:600;
    font-size:12.8px;
    color: #7C8A85;
    margin-bottom:0;
    /* border:1px solid red; */
}

/* ======== STEP CARDS ========= */
.step-wrap{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.step-card{
    display:flex;
    align-items:flex-start;
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:16px;
    padding:24px 26px;
    gap:20px;
        transition:.2s ease;
}

.step-card:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 30px rgba(20,60,40,0.08);
    border-color: #dcefe4;
}

.step-num{
    display:flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    min-width:34px;
    font-weight:900;
    font-size:13px;
    border-radius:10px;
    background:#0F1B2D;
    color:#fff;
}

.step-card:nth-child(2) .step-num,
.step-card:nth-child(4) .step-num,
.step-card:nth-child(6) .step-num{
    background:#198754;
}

.step-card h6{
    font-weight:800;
    font-size:15.5px;
    margin-bottom:6px;
}

.step-card p{
    font-weight:600;
    font-size:13px;
    color:#7C8A85;
    line-height:1.7;
    margin:0;
}

.step-tag{
    display:inline-block;
    font-weight:800;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.4px;
    color: #B8860B;
    background: #FFF4E5;
    padding:5px 10px;
    border-radius:8px;
    margin-top:10px;
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

      .step-card{ flex-direction:column; }

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
            <a href="found.php">Found</a>
            <a href="lost.php">Lost</a>
            <a href="claim.php">Claim Item</a>
             <a href="missing-report.php" class="active">How to report</a>
            <!-- <a href="terms.php" class="btn-login mb-3 mb-lg-0"><i class="bi bi-shield-lock" style="margin-right:6px;"></i>Term's and Conditions</a> -->
        </div>

         <i class="bi bi-list menu-toggle" onclick="document.getElementById('navbarLinks').classList.toggle('show')"></i>
    </div>

    <!-- FEATURES -->
    <section class="section" style="margin-top: 40px">
        <div class="mt-4 mb-3">
            <div class="page-heading">Report Missing Item Step</div>
            <div class="page-subheading">Record item details so it can be searched, matched, and claimed.</div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8 col-md-12">
                <div class="step-wrap">
                    <div class="step-card">
                        <div class="step-num">01</div>
                        <div>
                            <h6>Go to the Lost and Found Office</h6>
                            <p>Once you notice your item is missing, go to the Lost and Found Office in person. The sooner it's reported, the higher the chance of a match.</p>
                            <span class="step-tag">Start here</span>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-num">02</div>
                        <div>
                            <h6>Give your information to the staff on duty</h6>
                            <p>Tell the staff if you're a Student, Faculty, or Visitor/Guest. Students need to provide their Student ID, Year, and Department; Faculty need their Faculty ID. A valid email address is also required so you can be notified automatically if a match is found.</p>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-num">03</div>
                        <div>
                            <h6>Describe the item and its last known location</h6>
                            <p>Tell the staff the item's category (Cash, Gadget, Document, or Other) along with specific details — brand, color, amount, or any unique marks — plus the specific place where you last had it, such as the Library, Canteen, Computer Laboratory, or another area on campus. Accurate details help the system match it faster against items already turned in.</p>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-num">04</div>
                        <div>
                            <h6>Staff encodes your report into the system</h6>
                            <p>Your report is logged and automatically checked against found items reported within the last 7 days. You'll receive an email notification right away if a possible match comes in.</p>
                        </div>
                    </div>

                    <div class="step-card">
                        <div class="step-num">05</div>
                        <div>
                            <h6>Claim your item</h6>
                            <p>If a match is confirmed, return to the office with a valid ID — your School ID if you're a student, a guardian's ID if claiming for your child, or any government-issued ID if you're a visitor. See the Claim Item page for full requirements.</p>
                            <span class="step-tag">Final step</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="sidebar-sticky-right">
                    <div class="feat-card mb-3">
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

                    <div class="feat-card">
                        <div class="feat-icon" style="background: #E8F7EF;color: #198754;">
                            <i class="bi bi-info-circle"></i>
                        </div>

                        <div class="bg-light" style="padding:15px; border-radius:12px;">
                            <h6>Important Reminder</h6>
                            <p>
                                Report your missing item as soon as possible. Providing accurate
                                and complete information will help the staff identify possible
                                matches more quickly.
                            </p>
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