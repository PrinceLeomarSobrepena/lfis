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
    background: #f5f6fa;
    color: #0F1B2D;
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

.school-logo { width:40px; height:40px; object-fit:contain; }
.brand-divider { width:1px; height:28px; background:#dee2e6; }

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

.section-head{
    text-align:center;
    max-width: 600px;
    margin:0 auto 40px;
    /* border: 1px solid red; */
}

.section-label{
    color:#198754;
    font-weight:800;
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.6px;
    margin-bottom:10px;
    display:block;
}

.section-title{
    font-weight:900;
    font-size:30px;
    letter-spacing:-.7px;
    margin-bottom:10px;
}

.section-sub{
    color:#7C8A85;
    font-weight:600;
    font-size:14px;
}


/* ============ HERO ============ */
.hero-section {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 100px 15px 40px;   /* para hindi matakpan ng fixed navbar */
}

.hero-logo {
    width: 140px;
    margin-bottom: 20px;
}

.hero-section h1 {
    font-weight: 900;
    letter-spacing: -1px;
    font-size: 44px;
}

@media (max-width: 576px) {
    .hero-section h1 { font-size: 30px; }
    .hero-logo { width: 110px; }
}

/* ============ HERO SCROLL ============ */
.scroll-down {
    position: absolute;
    bottom: 30px;
    font-size: 26px;
    color: #198754;
}
.hero-section { position: relative; }

/* ======== PROCESS CARDS ========= */
.flow-wrap{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:0;
    position:relative;
}

.flow-step{
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius: 14px;
    padding: 26px 20px;
    position: relative;
    margin: 0 8px;
}

.flow-num{
    width:34px;
    height:34px;
    border-radius:10px;
    background: #0F1B2D;
    color: #fff;
    font-weight:900;
    font-size:13px;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom:14px; /* 16px */
}

.flow-step:nth-child(2) .flow-num,
.flow-step:nth-child(4) .flow-num{
    background:#198754;
}

.flow-step h6{
    font-weight:800;
    font-size:14.5px;
    margin-bottn:6px;
}

.flow-step p{
    font-weight:600;
    font-size:12.5px;
    color: #7C8A85;
    line-height:1.5;
    margin:0;
    /* border:1px solid red; */
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

.feat-card:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 30px rgba(20,60,40,0.08);
    border-color: #dcefe4;
}

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

/* ============ STATS STRIP  #198754, #0f5c38 ============ */
.stats-strip{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    background: #fff;
    color: #0f5c38;
    border:1px solid #dee2e6;
    border-radius:18px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    padding:20px 30px;
    gap:20px;
}

.stat-box{
    text-align:center;
    border-right:1px solid #dee2e6;
}

.stat-box:last-child{ border-right:none }

.stat-box .snum{
    font-weight:900;
    font-size:30px;
    letter-spacing:-1px;
   
    color: #0F1B2D; 
}

.stat-box .slbl{
    font-weight:700;
    font-size:11.5px;
    text-transform:uppercase;
    letter-spacing:.4px;
    margin-top:4px;
    opacity:.85;
}

/* ============ CTA BAND ============ */
.cta-band{
    position:relative;
    overflow:hidden;
    text-align:center;
    background:#0F1B2D;
    color:#fff;
    border-radius:22px;
    padding:56px 40px ;
}

.cta-band::before{
    content:"";
    position:absolute;
    width:400px;
    height:400px;
    background:radial-gradient(circle, rgba(25,135,84,0.35), transparent 70%);
    top:-150px;
    right:-100px;
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
}
</style>
</head>
<body>

    <div id="overlay"></div>
    <div class="navbar-top"></div>

    <div class="navbar shadow-sm">
        <div class="brand">
             <!-- <img src="../uploads/logoo.png" alt="Dr. Gloria D. Lacson Logo" class="school-logo">
              <span class="brand-divider"></span> -->
            <i class="bi bi-search-heart"></i>
            L&nbsp;&nbsp;F&nbsp;<span class="text-success" style="margin-left: -5px;">I &nbsp;S</span>
        </div>

        <div class="navbar-right" id="navbarLinks">
            <a href="index.php" class="active">Home</a>
            <a href="found.php">Found</a>
            <a href="lost.php">Lost</a>
            <a href="claim.php">Claim Item</a>
            <a href="missing-report.php">How to report</a>
            <!-- <a href="terms.php" class="btn-login mb-3 mb-lg-0"><i class="bi bi-shield-lock" style="margin-right:6px;"></i>Term's and Conditions</a> -->
        </div>

         <i class="bi bi-list menu-toggle" onclick="document.getElementById('navbarLinks').classList.toggle('show')"></i>
    </div>


    <!-- <div class="section text-center" style="margin-top:90px; padding-bottom:0;">
        <img src="../uploads/logoo.png" alt="School Logo" style="width:100px;" class="mb-3">
        <h1 style="font-weight:900; letter-spacing:-1px;">Dr. Gloria D. Lacson</h1>
        <p class="section-sub">Foundation Colleges, Inc. &bull; Lost And Found Information System</p>
    </div> -->
    <div class="hero-section">
        <img src="../uploads/logoo.png" alt="School Logo" class="hero-logo">
        <h1>Dr. Gloria D. Lacson</h1>
        <p class="section-sub">Foundation Colleges, Inc. &bull; Lost And Found Information System</p>

        <a href="#how" class="scroll-down"><i class="bi bi-chevron-double-down"></i></a>
    </div>



    <!-- HOW IT WORKS -->
    <div class="section" style="margin-top: 100px">
        <div class="section-head">
            <span class="section-label">-The Process-</span>
            <div class="section-title">From found to returned, in four steps</div>
            <div class="section-sub">A clear trail from the moment an item is picked up to the moment it's back in its owner's hands.</div>
        </div>

        <div class="flow-wrap">
            <div class="flow-step">
                <div class="flow-num">01</div>
                <h6>Item is logged</h6>
                <p>Staff record a found item with photo, location, and description in seconds.</p>
            </div>

            <div class="flow-step">
                <div class="flow-num">02</div>
                <h6>System matches it</h6>
                <p>Found items are automatically checked against active lost reports.</p>
            </div>

            <div class="flow-step">
                <div class="flow-num">03</div>
                <h6>Owner is notified</h6>
                <p>A possible match triggers a notification so the owner can confirm details.</p>
            </div>

            <div class="flow-step">
                <div class="flow-num">04</div>
                <h6>Verified pickup</h6>
                <p>Claim is verified on record before the item is released — every time.</p>
            </div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="section" style="margin-top: 100px">
        <div class="section-head">
            <span class="section-label">Built for Campus</span>
            <div class="section-title">Everything the office needs, everything students want</div>
            <div class="section-sub">One system for staff to manage intake, and for anyone to search what's been found.</div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #E8F7EF;color: #198754;"><i class="bi bi-layers"></i></div>
                    <h6>Smart Item Matching</h6>
                    <p>Found items are automatically compared against lost reports by category, location, and description.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #FFF4E5;color: #B8860B;"><i class="bi bi-search"></i></div>
                    <h6>Public Search, Private Access</h6>
                    <p>Anyone can browse found items without an account. Managing entries stays restricted to admin and staff logins.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #EAF1FF;color: #3366CC;"><i class="bi bi-clipboard-data"></i></div>
                    <h6>Full Audit Trail</h6>
                    <p>Every intake, match, and claim is logged with a timestamp and handling staff member.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #FCEAED;color: #E1596B;"><i class="bi bi-shield-check"></i></div>
                    <h6>Verified Claims</h6>
                    <p>Ownership is confirmed against report details before any item is released.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #F1EAFC;color: #7C4DFF;"><i class="bi bi-graph-up-arrow"></i></div>
                    <h6>Campus Statistics</h6>
                    <p>Track hotspots, common item types, and turnaround time to spot patterns over time.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feat-card">
                    <div class="feat-icon" style="background: #E8F7EF;color: #198754;"><i class="bi bi-calendar-event"></i></div>
                    <h6>Pickup Scheduling</h6>
                    <p>Claimants pick a pickup slot in advance so staff aren't caught off guard at the counter.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="section" style="margin-top: 100px">
        <div class="section-head">
            <span class="section-label">-The Process-</span>
            <div class="section-title">From found to returned, in four steps</div>
            <div class="section-sub">A clear trail from the moment an item is picked up to the moment it's back in its owner's hands.</div>
        </div>

        <div class="stats-strip">
            <div class="stat-box"><div class="snum">142</div><div class="slbl">Items Found</div></div>
            <div class="stat-box"><div class="snum">96</div><div class="slbl">Matched</div></div>
            <div class="stat-box"><div class="snum">68%</div><div class="slbl">Match Rate</div></div>
            <div class="stat-box"><div class="snum">2.1 days</div><div class="slbl">Avg. Return</div></div>
        </div>
    </section>

    <!-- CTA -->
    <section class="section" style="margin-top: 100px">
        <div class="section-head">
            <span class="section-label">-The Process-</span>
            <div class="section-title">From found to returned, in four steps</div>
            <div class="section-sub">A clear trail from the moment an item is picked up to the moment it's back in its owner's hands.</div>
        </div>

        <div class="cta-band">
            <h3>Lost something on campus?</h3>
            <p>Check the list first — it takes less time than filing a report.</p>
        </div>
    </section>

    <!-- FOOTER -->
    <footer style="margin-top: 100px">
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