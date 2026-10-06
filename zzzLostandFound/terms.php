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
    max-width: 1200px;
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

/* ======== TERMS CARD ========= */
.terms-card{
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:16px;
    padding:10px 20px;
}

.terms-item{
    display:flex;
    padding:20px 26px; /* 24px 26px */
    border-bottom:1px solid #E7ECE9;
    gap:18px;
}

.terms-item:last-child{ border-bottom:none; }

.terms-num{
    display:flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    min-width:34px;
    border-radius:10px;
    background: #0F1B2D;
    color: #fff;
    font-weight:900;
    font-size:13px;
}

.terms-item:nth-child(2) .terms-num,
.terms-item:nth-child(4) .terms-num,
.terms-item:nth-child(6) .terms-num{
    background:#198754;
}

.terms-item h6{
    font-weight:800;
    font-size:15px;
    margin-bottom:6px;
}

.terms-item p{
    font-weight:600;
    font-size:13px;
    color:#7C8A85;
    line-height:1.7;
    margin:0;
}

.terms-meta{
    text-align:center;
    font-weight:700;
    font-size:12px;
    color:#9AA6A1;
    margin-top:24px;
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
    border:1px solid red;
}




/* ============ FOOTER ============ */
footer{
    max-width:1200px;
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

    .toolbar{
        flex-direction:column;
        align-items:stretch;
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
            <a href="missing.php">Missing</a>
            <a href="claim.php">Claim Item</a>
            <a href="missing-report.php">How to report</a>
            <a href="terms.php" class="active btn-login mb-3 mb-lg-0"><i class="bi bi-shield-lock" style="margin-right:6px;"></i>Term's and Conditions</a>
        </div>

         <i class="bi bi-list menu-toggle" onclick="document.getElementById('navbarLinks').classList.toggle('show')"></i>
    </div>

    <!-- FEATURES -->
    <section class="section" style="margin-top: 40px">
        <div class="mt-4 mb-3">
            <div class="page-heading">Terms and Conditions</div>
            <div class="page-subheading">By using the Lost and Found Information System, you agree to the terms below. Please read them carefully before submitting a report or claiming an item.</div>
        </div>

        <div class="terms-card">
            <div class="terms-item">
                <div class="terms-num">01</div>
                <div>
                    <h6>Accurate Information</h6>
                    <p>Users must provide truthful and accurate details when reporting a found or missing item, including item description, date, and location. False reports may result in the report being removed and access to the system restricted.</p>
                </div>
            </div>

            <div class="terms-item">
                <div class="terms-num">02</div>
                <div>
                    <h6>Proof of Ownership</h6>
                    <p>Claimants must be able to describe or verify unique details of the item before it is released. Staff reserve the right to deny a claim if ownership cannot be reasonably confirmed.</p>
                </div>
            </div>

            <div class="terms-item">
                <div class="terms-num">03</div>
                <div>
                    <h6>Staff and Admin Access</h6>
                    <p>Managing item records, verifying claims, and releasing items are restricted to authorized staff and admin accounts. Only these accounts may log in to access management features of the system.</p>
                </div>
            </div>

            <div class="terms-item">
                <div class="terms-num">04</div>
                <div>
                    <h6>Public Search</h6>
                    <p>Any visitor may browse the list of found items without creating an account. Personal contact details of claimants and reporters are kept private and are not displayed publicly.</p>
                </div>
            </div>

            <div class="terms-item">
                <div class="terms-num">05</div>
                <div>
                    <h6>Unclaimed Items</h6>
                    <p>Items that remain unclaimed after the retention period set by the office may be disposed of, donated, or turned over according to campus policy.</p>
                </div>
            </div>

            <div class="terms-item">
                <div class="terms-num">06</div>
                <div>
                    <h6>Limitation of Liability</h6>
                    <p>The system and the office facilitate the return of lost items on a best-effort basis and are not liable for items lost, damaged, or not recovered while in transit or storage.</p>
                </div>
            </div>

            <div class="terms-item">
                <div class="terms-num">07</div>
                <div>
                    <h6>Changes to These Terms</h6>
                    <p>These terms may be updated from time to time. Continued use of the system after changes are posted constitutes acceptance of the revised terms.</p>
                </div>
            </div>
        </div>

        <div class="terms-meta">Last updated: September 2026</div>

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
    }, 5 * 60 * 1000);
</script>
</body>
</html>