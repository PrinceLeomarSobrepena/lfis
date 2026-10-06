<?php
session_start();
include '../config/db.php'; //../config/db.php

$result = $conn->query("
    SELECT lf.*,
        EXISTS (
            SELECT 1 FROM lost_found_matches m
            WHERE (m.lost_id = lf.id OR m.found_id = lf.id)
        ) AS has_match
    FROM lost_found lf
    WHERE lf.status = 'Found'
    ORDER BY lf.created_at DESC
");

// ==================== ICON DETECTOR ====================
function getItemIcon($category, $row) {

    if ($category === 'Cash') {
        return ['bi-cash-stack', '#E8F7EF', '#198754'];
    }

    if ($category === 'Gadget') {
        $type = strtolower($row['gadget_type'] ?? '');
        $icon = 'bi-cpu';

        if (strpos($type, 'phone') !== false)      $icon = 'bi-phone';
        elseif (strpos($type, 'laptop') !== false) $icon = 'bi-laptop';
        elseif (strpos($type, 'tablet') !== false) $icon = 'bi-tablet';
        elseif (strpos($type, 'watch') !== false)  $icon = 'bi-smartwatch';
        elseif (strpos($type, 'audio') !== false)  $icon = 'bi-headphones';

        return [$icon, '#F1EAFC', '#7C4DFF'];
    }

    if ($category === 'Document') {
        $type = strtolower($row['document_type'] ?? '');
        $icon = 'bi-file-earmark-text';

        if (strpos($type, 'id') !== false)       $icon = 'bi-credit-card-2-front';
        elseif (strpos($type, 'passport') !== false) $icon = 'bi-file-earmark-text';

        return [$icon, '#FFE4E8', '#D6336C']; // '#FCEAED', '#E1596B'
    }

    if ($category === 'Other') {
        $desc = strtolower($row['other_title'] ?? '');

        // dagdag ka nalang dito ng keyword -> icon kung may makikita kang common items
        $keywords = [
            // 'umbrella' => 'bi-umbrella',
            // 'payong'   => 'bi-umbrella',
            // 'wallet'   => 'bi-wallet2',
            // 'bag'      => 'bi-bag',
            // 'backpack' => 'bi-bag',
            // 'key'      => 'bi-key',
            // 'susi'     => 'bi-key',
            // 'watch'    => 'bi-watch',
            // 'book'     => 'bi-book',
            // 'bottle'   => 'bi-cup-straw',
            // 'tumbler'  => 'bi-cup-straw',
            // 'card'     => 'bi-credit-card-2-front',
            // 'jacket'   => 'bi-bag',
            // 'charger'  => 'bi-plug',
            // 'earphone' => 'bi-earbuds',
            
            // Bags & Wallets
            'wallet'      => 'bi-wallet2',
            'bag'         => 'bi-bag',
            'backpack'    => 'bi-backpack',
            'purse'       => 'bi-bag',
            'handbag'     => 'bi-bag',
            'tote bag'    => 'bi-bag',

            // Keys
            'key'         => 'bi-key',
            'keys'        => 'bi-key',
            'keychain'    => 'bi-key',

            // Umbrella
            'umbrella'    => 'bi-umbrella',

            // Watches & Jewelry
            'watch'       => 'bi-watch',
            'wristwatch'  => 'bi-watch',
            'ring'        => 'bi-gem',
            'necklace'    => 'bi-gem',
            'bracelet'    => 'bi-gem',
            'jewelry'     => 'bi-gem',
            'jewellery'   => 'bi-gem',

            // Books & School Supplies
            'book'        => 'bi-book',
            'notebook'    => 'bi-journal',
            'journal'     => 'bi-journal',
            'binder'      => 'bi-journal-bookmark',
            'folder'      => 'bi-folder',
            'file'        => 'bi-file-earmark',
            'pen'         => 'bi-pen',
            'ballpen'     => 'bi-pen',
            'pencil'      => 'bi-pencil',
            'eraser'      => 'bi-eraser',

            // Bottles & Drinks
            'bottle'      => 'bi-cup-straw',
            'water bottle'=> 'bi-cup-straw',
            'tumbler'     => 'bi-cup-straw',
            'cup'         => 'bi-cup',
            'mug'         => 'bi-cup-hot',
            'coffee'      => 'bi-cup-hot',

            // Clothing
            'jacket'      => 'bi-person',
            'coat'        => 'bi-person',
            'hoodie'      => 'bi-person',
            'shirt'       => 'bi-person',
            'uniform'     => 'bi-person',
            'clothes'     => 'bi-person',
            'shoes'       => 'bi-person-walking',
            'sneakers'    => 'bi-person-walking',
            'slippers'    => 'bi-person-walking',

            // Electronics
            'phone'       => 'bi-phone',
            'cellphone'   => 'bi-phone',
            'smartphone'  => 'bi-phone',
            'laptop'      => 'bi-laptop',
            'computer'    => 'bi-pc-display',
            'tablet'      => 'bi-tablet',
            'charger'     => 'bi-plug',
            'adapter'     => 'bi-plug',
            'earphone'    => 'bi-earbuds',
            'earphones'   => 'bi-earbuds',
            'earbuds'     => 'bi-earbuds',
            'airpods'     => 'bi-earbuds',
            'headset'     => 'bi-headphones',
            'headphones'  => 'bi-headphones',
            'powerbank'   => 'bi-battery-charging',
            'power bank'  => 'bi-battery-charging',
            'battery'     => 'bi-battery-charging',
            'usb'         => 'bi-usb-drive',
            'flashdrive'  => 'bi-usb-drive',
            'flash drive' => 'bi-usb-drive',
            'camera'      => 'bi-camera',

            // Cards & IDs
            'card'        => 'bi-credit-card-2-front',
            'credit card' => 'bi-credit-card-2-front',
            'id'          => 'bi-person-vcard',
            'school id'   => 'bi-person-vcard',
            'license'     => 'bi-person-vcard',
            'driver license' => 'bi-person-vcard',

            // Documents
            'document'    => 'bi-file-earmark-text',
            'documents'   => 'bi-file-earmark-text',
            'paper'       => 'bi-file-earmark-text',
            'papers'      => 'bi-file-earmark-text',
            'receipt'     => 'bi-receipt',

            // Money
            'money'       => 'bi-cash-stack',
            'cash'        => 'bi-cash-stack',
            'coin'        => 'bi-coin',
            'coins'       => 'bi-coin',

            // Eyewear
            'glasses'     => 'bi-eyeglasses',
            'eyeglasses'  => 'bi-eyeglasses',
            'sunglasses'  => 'bi-sunglasses',

            // Sports
            'basketball'  => 'bi-dribbble',
            'football'    => 'bi-circle-fill',
            'volleyball'  => 'bi-circle-fill',
            'ball'        => 'bi-circle-fill',

            // Personal Items
            'comb'        => 'bi-scissors',
            'toothbrush'  => 'bi-brush',
            'makeup'      => 'bi-stars',
            'cosmetics'   => 'bi-stars',

            // Toys / Games
            'toy'         => 'bi-controller',
            'game'        => 'bi-controller',
            'controller'  => 'bi-controller',

            // Medical
            'medicine'    => 'bi-capsule',
            'medication'  => 'bi-capsule',
            'bandage'     => 'bi-bandaid',

            // Other common items
            'helmet'      => 'bi-bicycle',
            'cap'         => 'bi-person',
            'hat'         => 'bi-person',
        ];

        $icon = 'bi-box-seam'; // default kapag walang match

        foreach ($keywords as $word => $iconClass) {
            if (strpos($desc, $word) !== false) {
                $icon = $iconClass;
                break;
            }
        }

        return [$icon, '#FFF4E5', '#B8860B'];
    }

    return ['bi-box-seam', '#f5f6fa', '#4B5A54'];
}
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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

/* ====================== TOOLBAR ===================== */
.toolbar{
    display:flex;
    flex-wrap:wrap;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px; /*14px*/
    padding:16px;
    align-items:center;
    gap:12px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.search-box{
    flex:1;
    min-width:220px;
    position:relative;
}

.search-box i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color: #8CA298;
    font-size:14px;
}

.search-box input{
    width:100%;
    font-size:13.5px;
    font-weight:600;
    color: #0F1B2D;
    /background: #fff; /* #f9fbfa */
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:10px 14px 10px 38px;
}

.search-box input:focus{
    outline:none;
    border-color: #198754;
    background: #fff;
}

.filter-select{
    min-width:150px;
    font-size:13px;
    font-weight:700;
    color: #4B5A54;
    background: #fff;  /* #f9fbfa */
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:10px 14px;
}

.filter-select:focus{
    outline:none;
    border-color: #198754;
}

/* ====================== RESET BUTTON ===================== */
.btn-reset{
    display:flex;
    align-items:center;
    white-space:nowrap;
    background: #fff;  /*linear-gradient(135deg, #198754, #147a49);    #0F1B2D   */
    border:1px solid #E7ECE9;
    color: #4B5A54;
    /* border:none; */
    padding:10px 20px;
    border-radius:10px;
    font-weight:700;
    font-size:13.5px;
    gap:8px;
}

.btn-reset:hover{
    background:#f5f6fa;
}

/* .btn-reset i {
    font-size: 15px;
    -webkit-text-stroke: 0.7px currentColor;
} */

.btn-reset i {
    font-size: 15px;
    -webkit-text-stroke: 0.7px currentColor;
    display: inline-block;
}

.btn-reset i.spin {
    animation: resetSpin 0.5s ease;
}

@keyframes resetSpin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(-360deg);
    }
}



/* ====================== CHIP TOKEN ===================== */
.status-pills{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin:16px 0 18px;
}

.status-pill{
    font-weight:700;
    font-size:12.5px;
    border:1px solid #E7ECE9;
    background:#fff;
    color:#4B5A54;
    padding:8px 16px;
    border-radius:30px;
    cursor:pointer;
    transition:.2s;
}

.status-pill.active{
    background: #0F1B2D; /*#0F1B2D*/
    color: #fff;
    border-color: #0F1B2D;
}

.status-pill:hover:not(.active){
    background: #f5f6fa;
}



/* ====================== RESULT ITEMS ===================== */
.results-count{
    font-size:12.5px;
    color: #7C8A85;
    font-weight:700;
    margin-bottom:16px;
}
.results-count b{color:#0F1B2D;}

/* ====================== ITEM CARDS ===================== */
.item-card{
    overflow:hidden;
    height:100%;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    transition:.2s ease;
}

.item-card:hover{
    transform:translateY(-3px);
    box-shadow:0 14px 30px rgba(20,60,40,0.08);
    border-color: #dcefe4;
}

.item-thumb{
    height:130px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:38px;
    position:relative;
}

.item-status{
    position:absolute;
    top:10px;
    left:10px;   /* dating right:10px */
    font-weight:800;
    font-size:10px;
    padding:4px 10px;
    border-radius:20px;
    /* border: 1px solid red; */
}

/* .status-found{background: #E8F7EF;color: #198754;}
.status-matched{background: #FFF4E5;color: #B8860B;}
.status-claimed{background: #EAF1FF;color: #3366CC;}
.status-resolved{background: #F1EAFC;color: #7C4DFF;} */
.item-id{
    position:absolute;
    top:10px;
    right:10px;
    font-weight:800;
    font-size:10px;
    padding:4px 10px;
    border-radius:20px;
    background: rgba(255,255,255,0.85);
    color: #4B5A54;
}

.item-body{
    padding:14px 16px 16px;
}

.item-title{
    font-weight:800;
    font-size:14px;
    color: #0F1B2D;
    margin-bottom:6px;
}

.item-meta{
    display:flex;
    align-items:center;
    font-size:11.5px;
    color: #7C8A85;
    font-weight:600;
    gap:6px;
    margin-bottom:4px;
}

.item-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:12px;
    padding-top:12px;
    border-top:1px solid #F0F2F1;
}

.item-date{
     display:flex;   /*bago*/
    align-items:center;   /*bago*/
    font-weight:700;
    font-size:11px;
    color: #9AA6A1;
     gap:6px;    /*bago*/
}

.btn-view{
    display:flex;
    align-items:center;
    font-weight:800;
    font-size:12px;
    color:#198754;
    gap:4px;
}


/* ============ CTA BAND ============ */
.cta-band{
    position:relative;
    overflow:hidden;
    text-align:center;
    background: #0F1B2D;
    color:#fff;
    border-radius:22px;
    padding:30px 40px; /* 56px 40px ; */
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

.cta-band h5{
    font-weight:700;
}
.cta-band p{
    font-size:14.5px;
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
            <a href="found.php" class="active">Found</a>
            <a href="lost.php">Lost</a>
            <a href="claim.php">Claim Item</a>
            <a href="missing-report.php">How to report</a>
            <!-- <a href="terms.php" class="btn-login mb-3 mb-lg-0"><i class="bi bi-shield-lock" style="margin-right:6px;"></i>Term's and Conditions</a> -->
        </div>

         <i class="bi bi-list menu-toggle" onclick="document.getElementById('navbarLinks').classList.toggle('show')"></i>
    </div>

    <!-- FEATURES -->
    <section class="section" style="margin-top: 40px">
        <div class="mt-4 mb-3">
            <div class="page-heading">Found Item Logs</div>
            <div class="page-subheading">Record item details so it can be searched, matched, and claimed.</div>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search by item name, location, or keyword...">
            </div>

            <select class="filter-select" id="categoryFilter">
                <option value="">All Categories</option>
                <option value="Cash">Cash</option>
                <option value="Gadget">Gadget</option>
                <option value="Document">Document</option>
                <option value="Other">Other</option>
            </select>

            <select class="filter-select" id="locationFilter">
                <option value="">All Locations</option>
                <option value="Main Lobby">Main Lobby</option>
                <option value="Library">Library</option>
                <option value="Canteen / Cafeteria">Canteen / Cafeteria</option>
                <option value="Gymnasium">Gymnasium</option>
                <option value="Computer Laboratory">Computer Laboratory</option>
                <option value="Science Laboratory">Science Laboratory</option>
                <option value="Registrar's Office">Registrar's Office</option>
                <option value="Guidance Office">Guidance Office</option>
                <option value="Faculty Room">Faculty Room</option>
                <option value="Parking Area">Parking Area</option>
                <option value="Comfort Room">Comfort Room</option>
                <option value="Chapel">Chapel</option>
                <option value="Other">Other</option>
            </select>



            <!-- DATE FILTER -->
    <!-- <select class="filter-select" id="dateFilter">
        <option value="">All Dates</option>
        <option value="today">Today</option>
        <option value="7days">Last 7 Days</option>
        <option value="30days">Last 30 Days</option>
        <option value="thisMonth">This Month</option>
        <option value="lastMonth">Last Month</option>
    </select> -->



            <button class="btn-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset </button>
        </div>

        <!-- STATUS FILTER PILLS -->
        <div class="status-pills" id="statusPills">
            <div class="status-pill active" data-status="" style="padding:8px 24px; ">All</div>
            <div class="status-pill" data-status="Found">Found</div>
            <div class="status-pill" data-status="Matched">Matched</div>
            <div class="status-pill" data-status="Claimed">Claimed</div>
            <div class="status-pill" data-status="Resolved">Resolved</div>
        </div>

        <!-- <div class="results-count">Showing <b>142</b> items</div> -->
        <!-- <div class="results-count">Showing <b id="resultsCount"><?= $result->num_rows ?></b> items</div> -->
        <?php $totalItems = $result->num_rows; ?>
        <div class="results-count">
            Showing <b id="resultsCount"><?= $totalItems ?></b> out of <b id="totalCount"><?= $totalItems ?></b> items
        </div>

        <!-- GRID -->
        <!-- <div class="row g-3">
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#E8F7EF;color:#198754;">
                            <i class="bi bi-wallet2"></i>
                            <span class="item-status status-found">Found</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Brown Leather Wallet</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Library, 2F</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Wallets & Bags</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 15, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#FFF4E5;color:#B8860B;">
                            <i class="bi bi-phone"></i>
                            <span class="item-status status-matched">Matched</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Samsung Phone, cracked case</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Canteen</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Electronics</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 14, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#EAF1FF;color:#3366CC;">
                            <i class="bi bi-key"></i>
                            <span class="item-status status-claimed">Claimed</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Keys with red lanyard</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Gymnasium</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Keys</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 13, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#E8F7EF;color:#198754;">
                            <i class="bi bi-umbrella"></i>
                            <span class="item-status status-found">Found</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Black Foldable Umbrella</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Main Gate</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Others</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 12, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#FCEAED;color:#E1596B;">
                            <i class="bi bi-credit-card-2-front"></i>
                            <span class="item-status status-found">Found</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">School ID - Nursing Dept.</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Classroom 204</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Documents / ID</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 11, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#F1EAFC;color:#7C4DFF;">
                            <i class="bi bi-headphones"></i>
                            <span class="item-status status-found">Found</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Wireless Earbuds, white case</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Library, 1F</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Electronics</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 10, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#E8F7EF;color:#198754;">
                            <i class="bi bi-bag"></i>
                            <span class="item-status status-matched">Matched</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Gray Backpack</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Gymnasium</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Wallets & Bags</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 9, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="./claim.php" class="card-link">
                    <div class="item-card">
                        <div class="item-thumb" style="background:#FFF4E5;color:#B8860B;">
                            <i class="bi bi-watch"></i>
                            <span class="item-status status-claimed">Claimed</span>
                        </div>
                        <div class="item-body">
                            <div class="item-title">Silver Wristwatch</div>
                            <div class="item-meta"><i class="bi bi-geo-alt"></i> Canteen</div>
                            <div class="item-meta"><i class="bi bi-tag"></i> Others</div>
                            <div class="item-footer">
                                <span class="item-date">Aug 8, 2026</span>
                                <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div> -->

        <div class="row g-3">
            <?php if ($result->num_rows === 0): ?>
                <div class="text-center py-5" style="color: #7C8A85;">
                    <i class="bi bi-inboxes" style="font-size:28px;color: #BFCBC5;"></i>
                    <div class="mt-2 fw-bold">No found items have been recorded yet.</div>
                </div>
                <!-- <div id="noResults" class="text-center py-5" style="display:none; color:#7C8A85;">
                    <i class="bi bi-search" style="font-size:28px;color:#BFCBC5;"></i>
                    <div class="mt-2 fw-bold">Walang item na tumugma sa filter mo.</div>
                </div> -->
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        list($icon, $bg, $color) = getItemIcon($row['category'], $row);

                        if ($row['is_claimed']) {
                            $statusClass = 'status-claimed'; $statusLabel = 'Claimed';
                        } elseif ($row['is_resolved']) {
                            $statusClass = 'status-resolved'; $statusLabel = 'Resolved';
                        } elseif ($row['has_match']) {
                            $statusClass = 'status-matched'; $statusLabel = 'Matched';
                        } else {
                            $statusClass = 'status-found'; $statusLabel = 'Found';
                        }

                        if ($row['category'] === 'Cash') {
                            $title = 'Cash - ₱' . number_format($row['cash_amount'], 2);
                        } elseif ($row['category'] === 'Gadget') {
                            // $title = trim(($row['gadget_brand'] ?? '') . ' ' . ($row['gadget_type'] ?? ''));
                            $title = trim(($row['gadget_type'] ?? ''));
                        } elseif ($row['category'] === 'Document') {
                            $title = $row['document_type'] ?? 'Document';
                        } else {
                            $title = $row['other_title'] ?? 'Item';
                        }
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 item-col"
                        data-id="<?= $row['id'] ?>"
                        data-category="<?= htmlspecialchars($row['category']) ?>"
                        data-location="<?= htmlspecialchars($row['reported_location']) ?>"
                        data-status="<?= $statusLabel ?>"
                        data-title="<?= htmlspecialchars(strtolower($title)) ?>"
                        data-date="<?= htmlspecialchars(strtolower(date('F d, Y', strtotime($row['created_at'])))) ?>">
                        <a href="./claim.php?id=<?= $row['id'] ?>" class="card-link">
                            <div class="item-card">
                                <div class="item-thumb" style="background:<?= $bg ?>;color:<?= $color ?>;">
                                    <i class="bi <?= $icon ?>"></i>
                                    <span class="item-status <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    <span class="item-id">#<?= $row['id'] ?></span>
                                </div>
                                <div class="item-body">
                                    <div class="item-title"><?= htmlspecialchars($title) ?></div>
                                    <div class="item-meta"><i class="bi bi-geo-alt"></i>Location: <?= htmlspecialchars($row['reported_location']) ?></div>
                                    <div class="item-meta"><i class="bi bi-tag"></i>Category: <?= htmlspecialchars($row['category']) ?></div>
                                    <div class="item-footer">
                                        <span class="item-date"><i class="bi bi-calendar-event"></i>Date: <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                        <span class="btn-view"> View <i class="bi bi-arrow-right"></i></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- LAGING NAKA-RENDER, hidden lang default; gagamitin ng JS filter -->
        <div id="noResults" class="text-center py-5 w-100" style="display:none; color: #7C8A85;">
            <i class="bi bi-search" style="font-size:28px;color: #BFCBC5;"></i>
            <div class="mt-2 fw-bold">No items match your filter.</div>
        </div>

        <div class="cta-band mt-5">
            <h5>Looking for a lost item?</h5>
            <p>Check the found items list first — you might find what you're looking for.</p>
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
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const locationFilter = document.getElementById('locationFilter');
const statusPills = document.querySelectorAll('#statusPills .status-pill');
const itemCols = document.querySelectorAll('.item-col');
const noResults = document.getElementById('noResults');
const resetButton = document.querySelector('.btn-reset');

let activeStatus = '';

function applyFilters() {
    const searchTerm = searchInput.value.trim().toLowerCase();
    const category = categoryFilter.value;
    const location = locationFilter.value;

    // BAGO: hiwalay na version ng searchTerm na walang "#", gagamitin lang sa id match
    const searchTermForId = searchTerm.replace('#', '');

    let visibleCount = 0;

    itemCols.forEach(col => {
        const matchesSearch = !searchTerm ||
            col.dataset.title.includes(searchTerm) ||
            col.dataset.location.toLowerCase().includes(searchTerm) ||
            col.dataset.category.toLowerCase().includes(searchTerm) ||
            col.dataset.date.includes(searchTerm) ||
            col.dataset.id.includes(searchTermForId);

        const matchesCategory = !category || col.dataset.category === category;
        const matchesLocation = !location || col.dataset.location === location;
        const matchesStatus = !activeStatus || col.dataset.status === activeStatus;

        const show = matchesSearch && matchesCategory && matchesLocation && matchesStatus;
        col.style.display = show ? '' : 'none';

        if (show) visibleCount++;
    });

    noResults.style.display = visibleCount === 0 ? 'block' : 'none';

    // BAGO: i-update yung "Showing X items"
    document.getElementById('resultsCount').textContent = visibleCount;
}

searchInput.addEventListener('input', applyFilters);
categoryFilter.addEventListener('change', applyFilters);
locationFilter.addEventListener('change', applyFilters);

// Status pills
statusPills.forEach(pill => {
    pill.addEventListener('click', function() {
        statusPills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        activeStatus = this.dataset.status;
        applyFilters();
    });
});


// Reset button
// resetButton.addEventListener('click', function() {
//     searchInput.value = '';
//     categoryFilter.value = '';
//     locationFilter.value = '';
//     activeStatus = '';

//     statusPills.forEach(pill => {
//         pill.classList.remove('active');
//     });

//     // Activate "All"
//     document
//         .querySelector('#statusPills .status-pill[data-status=""]')
//         .classList.add('active');

//     // Apply reset filters
//     applyFilters();
// });
resetButton.addEventListener('click', function() {
    const resetIcon = this.querySelector('i');
        // Paikutin ang reset icon
        resetIcon.classList.remove('spin');
        // Restart animation kahit paulit-ulit pindutin
        void resetIcon.offsetWidth;
        resetIcon.classList.add('spin');

    // Reset filters
    searchInput.value = '';
    categoryFilter.value = '';
    locationFilter.value = '';
    activeStatus = '';

    statusPills.forEach(pill => {
        pill.classList.remove('active');
    });

    // Activate "All"
    document
        .querySelector('#statusPills .status-pill[data-status=""]')
        .classList.add('active');

    // Apply reset filters
    applyFilters();
});

</script>


<script>
    // Redirect after exactly 10 minutes
    setTimeout(function () {
        window.location.href = "https://www.google.com";
    }, 15 * 60 * 1000);
</script>

</body>
</html>