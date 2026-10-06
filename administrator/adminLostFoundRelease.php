<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$released_by = $_SESSION['admin']['id'];

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM lost_found WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) die("Not found");

if ($item['status'] !== 'Found') {
    die("Only Found items can be released.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['claimed_by']);
    $student_id = trim($_POST['claimed_id']);
    $email = trim($_POST['claimed_email']);
    $contact = trim($_POST['claimed_contact']);
    $department = trim($_POST['claimed_department']);
    $address = trim($_POST['claimed_address']);

    $stmt = $conn->prepare("UPDATE lost_found SET claimed_by=?, claimed_id=?, claimed_email=?, claimed_contact=?, claimed_department=?, claimed_address=?, claimed_date=NOW(), is_claimed=1, released_by = ? WHERE id=?");
    $stmt->bind_param("ssssssii", $name, $student_id, $email, $contact, $department, $address, $released_by, $id);
    $stmt->execute();


     /*
    |--------------------------------------------------------------------------
    | Delete related matches after successful claim
    |--------------------------------------------------------------------------
    | Kapag na-claim na ang item, hindi na ito dapat lumabas
    | sa matching records kaya buburahin natin lahat ng
    | connected records sa lost_found_matches table.
    */

    $deleteMatches = $conn->prepare("DELETE FROM lost_found_matches WHERE lost_id = ? OR found_id = ?");
    $deleteMatches->bind_param("ii", $id, $id);
    $deleteMatches->execute();
    $deleteMatches->close();

    header("Location: adminLostFoundList.php");
    exit();
}

/* Icon per category (para sa category pill) */
$catIcons = [
    'Cash'     => 'bi-cash-coin',
    'Gadget'   => 'bi-phone',
    'Document' => 'bi-file-earmark-text',
    'Other'    => 'bi-box-seam'
];
$catIcon = $catIcons[$item['category']] ?? 'bi-box-seam';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../public/css/admin.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- font style -->
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ====================== PHOTO + ITEM DETAILS ===================== */
.photo-upload-row{
    display:flex;
    align-items:flex-start;
    gap:18px;
}

.photo-preview{
    display:flex;
    align-items:center;
    justify-content:center;
    width:84px;
    height:84px;
    padding:0;
    font-size:28px;
    color:#198754;
    border-radius:16px;
    background:#F3FBF7;
    border:none;
    flex-shrink:0;
    overflow:hidden;
    position:relative;
}

.photo-preview img{
    display:block;
    width:100%;
    height:100%;
    object-fit:cover;
    max-width:none;
}

.item-details-right{
    flex:1;
    min-width:0;
}

.category-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:11.5px;
    font-weight:800;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#147a49;
    background:#E8F7EF;
    border-radius:999px;
    padding:4px 12px;
    margin-bottom:10px;
}

.detail-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:10px 18px;
}

.detail-item{
    display:flex;
    flex-direction:column;
    min-width:0;
    line-height:1.3;
}

.detail-item.full{
    grid-column:1 / -1;
}

.detail-item small{
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#7A8A83;
}

.detail-item span{
    font-size:13px;
    font-weight:700;
    color: #0F1B2D;
    overflow-wrap:anywhere;
}

.detail-item .detail-amount{
    font-size:13px;
    font-weight:800;
    color: #0F1B2D;
}

/* ====================== Reported ===================== */
.reported-details{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    width:100%;
    margin-top:-5px;
}

.report-chip{
    display:flex;
    align-items:center;
    gap:10px;
    flex:1 1 220px;
    background: #f7faf8; /* #F3FBF7 */
    border:1px solid #D5EEDF;
    border-radius:12px;
    padding:10px 14px;
}

.report-chip-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    flex-shrink:0;
    border-radius:10px;
    background:#198754;
    color:#fff;
    font-size:15px;
    /* box-shadow:0 4px 10px rgba(25,135,84,0.25); */
}

.report-chip-text{
    display:flex;
    flex-direction:column;
    min-width:0;
    line-height:1.3;
}

.report-chip-text small{
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#7A8A83;
}

.report-chip-text span{
    font-size:13px;
    font-weight:700;
    color:#0F1B2D;
    overflow-wrap:anywhere;
}


/* ====================== FORM CARD ===================== */
.form-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:26px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.form-section-title{
    display:flex;
    align-items:center;
    font-weight:800;
    font-size:14px;
    color: #0F1B2D;
    margin-bottom:16px;
    gap:8px;
}

.form-section-title i{
    color: #198754;
}

.form-label{
    display:block;
    font-weight:700;
    font-size:12.5px;
    color: #4B5A54;
    margin-bottom:6px; 
}

.form-label .req{
    color: #E1596B;
}

.form-control-custom{
    width:100%;
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:11px 14px;
    font-size:13.5px;
    font-weight:600;
    color: #0F1B2D;
    /* background:#f9fbfa; */
    font-family:'Nunito', Arial, sans-serif;
}

.form-control-custom:focus{
    outline:none;
    border-color: #198754;
    background: #fff;
}


/* ====================== ACTION BUTTONS ===================== */
.form-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:22px;
}

.btn-cancel{
    font-weight:700;
    font-size:13.5px;
    color: #4B5A54;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:11px 22px;
    text-decoration:none;
}

.btn-cancel:hover{background:#f5f6fa;}

.btn-submit{
    display:flex;
    font-weight:700;
    font-size:13.5px;
    background:linear-gradient(135deg, #198754, #147a49);
    align-items:center;
    color: #fff;
    border:none;
    border-radius:10px;
    padding: 11px 24px;
    gap:8px;
    box-shadow:0 6px 14px rgba(25,135,84,0.25);
}

/* ====================== DIVIDER ===================== */
.divider-line{
    border:none;
    border-top:1px solid #EEF1F0;
    margin:6px 0;
    width:100%;
    opacity:1;
}


/* ====================== SIDE SUMMARY ===================== */
.summary-card{
    text-align:center;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:22px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.summary-tip{
    background:#E8F7EF;
    border-radius:10px;
    padding:12px 14px;
    font-size:11.5px;
    color:#147a49;
    font-weight:600;
    /* margin-top:16px; */
    display:flex;
    gap:8px;
    align-items:flex-start;
    line-height:1.5;
    text-align:left;
}

@media(max-width:992px){
    .form-actions{
        flex-direction:column-reverse;
    }

    .form-actions button{
        width:100%;
        justify-content:center;
    }

    .summary-card{
        margin-top:16px;
    }
}

@media(max-width:576px){
    .detail-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
}
</style>
</head>
<body>

    <!-- OVERLAY -->
    <div id="overlay"></div>

    <!-- SIDEBAR -->
    <div class="sidebar shadow-sm" id="sidebar">
        <div class="btn-close-outside" onclick="toggleSidebar()">
            <i class="fas fa-times" style="margin-left: -1px;"></i>
        </div>

        <div class="brand mb-3 mt-2">
            <i class="bi bi-search-heart"></i>
            L&nbsp;&nbsp;F&nbsp;<span class="text-success" style="margin-left: -5px;">I &nbsp;S</span>
        </div>
        <hr>

        <a href="adminDashboard.php"> <i class="bi bi-speedometer2"></i> Dashboard </a>
        <a href="adminStaffList.php"> <i class="bi bi-people"></i> Staff </a>


        <a href="adminLostFoundList.php" class="active"> <i class="bi bi-journal-text"></i> List </a>
        <a href="adminLostFoundRelease.php?id=<?= $item['id'] ?>" class="active" style="background: #30b175;margin-left: 20px;"> 
           <i class="bi bi-arrow-return-right"></i>
            <span> <i class="bi bi-hand-index-thumb"></i>&nbsp;&nbsp;&nbsp;Release</span> 
        </a>

        <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"><i class="bi bi-graph-up-arrow"></i> Statistic </a>
        <a href="adminLostFoundCalendar.php"> <i class="bi bi-calendar-event"></i> Calendar </a>
        <a href="adminChangePassword.php"> <i class="bi bi-lock" style="display: inline-block; transform: scaleX(1.4);"></i> Change Password </a>
        <a href="adminLogout.php" class="logout-btn"> <i class="bi bi-box-arrow-right"></i> Logout </a>
    </div>


    <!-- NAVBAR -->
    <nav class="navbar-custom shadow-sm">
        <div class="navbar-left">
            <img src="../uploads/SCHOOL.jpg" class="profile-img">  <!--  d-lg-none -->

            <div>
                <h5 class="navbar-title  text-success">Lost And Found Information System</h5>
                <div class="navbar-subtitle">DR. GLORIA D. LACSON FOUNDATION COLLEGES, INC.</div>
            </div>
        </div>

        <div class="navbar-right">
            <i class="fa-solid fa-bars icon menu-toggle-btn" onclick="toggleSidebar()"></i>
            <!-- <h5 class=" d-none d-lg-block">Hi, Admin</h5>
            <img src="https://picsum.photos/200"  class="profile-img d-none d-lg-block"> -->
        </div>
    </nav>

        <!-- MAIN-CONTENT -->
    <div class="main-content">
        <div class="mt-4 mb-3">
            <div class="page-heading">Release</div>
            <div class="page-subheading">Record item details so it can be searched, matched, and claimed.</div>
        </div>

        <div class="row g-3">
            <!-- FORM -->
            <div class="col-lg-8">
                <div class="form-card">

                    <form method="POST"  enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <div class="row g-3">

                                <div class="form-section-title"><i class="bi bi-box-seam"></i> Item Details</div>

                                <!-- REPORTED LOCATION + DATE -->
                                <div class="reported-details">
                                    <div class="report-chip">
                                        <span class="report-chip-icon"><i class="bi bi-geo-alt-fill"></i></span>
                                        <div class="report-chip-text">
                                            <small>Reported Location</small>
                                            <span><?= htmlspecialchars($item['reported_location']) ?></span>
                                        </div>
                                    </div>

                                    <div class="report-chip">
                                        <span class="report-chip-icon"><i class="bi bi-calendar-event-fill"></i></span>
                                        <div class="report-chip-text">
                                            <small>Date Reported</small>
                                            <span>
                                                <?= !empty($item['created_at'])
                                                    ? date('M d, Y · h:i A', strtotime($item['created_at']))
                                                    : '-' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!--  IMAGE AND DETAILS -->
                                <div class="photo-upload-row">

                                    <div class="photo-preview" id="photoPreview">
                                        <i  id="photoPlaceholder" style="<?= $item['image'] && $item['image'] !== 'default.jpg' ? 'display:none;' : '' ?>"></i>
                                        <img id="photoImg" alt="Preview"
                                            src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                                            style="<?= $item['image'] && $item['image'] !== 'default.jpg' ? 'display:block;' : '' ?>">
                                    </div>

                                    <div class="item-details-right">

                                        <span class="category-badge">
                                            <!-- <i class="bi <?= $catIcon ?>"></i> -->
                                            <?= htmlspecialchars($item['category']) ?>
                                        </span>

                                        <div class="detail-grid">

                                            <?php if ($item['category'] === 'Cash'): ?>
                                                <div class="detail-item full">
                                                    <small>Amount</small>
                                                    <span class="detail-amount">₱<?= number_format($item['cash_amount'], 2) ?></span>
                                                </div>

                                            <?php elseif ($item['category'] === 'Gadget'): ?>
                                                <div class="detail-item">
                                                    <small>Type</small>
                                                    <span><?= htmlspecialchars($item['gadget_type']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <small>Brand</small>
                                                    <span><?= htmlspecialchars($item['gadget_brand']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <small>Color</small>
                                                    <span><?= htmlspecialchars($item['gadget_color']) ?></span>
                                                </div>
                                                <div class="detail-item full">
                                                    <small>Features</small>
                                                    <span><?= htmlspecialchars($item['gadget_description']) ?></span>
                                                </div>

                                            <?php elseif ($item['category'] === 'Document'): ?>
                                                <div class="detail-item">
                                                    <small>Document Type</small>
                                                    <span><?= htmlspecialchars($item['document_type']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <small>Name</small>
                                                    <span><?= htmlspecialchars($item['document_name']) ?></span>
                                                </div>

                                            <?php elseif ($item['category'] === 'Other'): ?>
                                                <div class="detail-item">
                                                    <small>Item Name</small>
                                                    <span><?= htmlspecialchars($item['other_title']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <small>Description</small>
                                                    <span><?= htmlspecialchars($item['other_description']) ?></span>
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                    </div>

                                </div>


                                <hr class="divider-line">


                                <div class="col-md-8">
                                    <label class="form-label">Full Name <span class="req">*</span></label>
                                    <input type="text" name="claimed_by" class="form-control-custom" placeholder="e.g. Dexie Diaz" required>
                                </div>

                                 <div class="col-md-4">
                                    <label class="form-label">ID <span class="req">*</span></label>
                                    <input type="text" name="claimed_id" class="form-control-custom" placeholder="e.g. 1015150178" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="req">*</span></label>
                                    <input type="email" name="claimed_email" class="form-control-custom" placeholder="e.g. example@gmail.com" required>
                                </div>

                                 <div class="col-md-6">
                                    <label class="form-label">Contact <span class="req">*</span></label>
                                    <input type="text" name="claimed_contact" class="form-control-custom" placeholder="e.g. 09708000000" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Department<span class="req">*</span></label>
                                    <select name="claimed_department" class="form-control-custom" required>
                                        <option value="">Select Department</option>
                                        <option value="BSA"> Bachelor of Science in Accountancy </option>
                                        <option value="CRIM"> Bachelor of Science in Criminology </option>
                                        <option value="EDUC"> Bachelor of Science in Education </option>
                                        <option value="HMTM"> Bachelor of Science in Hospitality Management and Tourism Management </option>
                                        <option value="IT"> Bachelor of Science in Information Technology </option>
                                        <option value="RAD TECH / MED TECH"> Bachelor of Science in Radiologic Technology / Bachelor of Science in Medical Technology </option>
                                        <option value="FACULTY"> Faculty </option>
                                        <option value="VISITOR / GUEST"> Visitor / Guest </option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Address <span class="req">*</span></label>
                                    <input type="text" name="claimed_address" class="form-control-custom" placeholder="e.g. Brgy. San Sntonio">
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-submit"><i class="bi bi-check-lg"></i> Save Changes </button>
                                </div>
                            </div>
                    </form>

                </div>
            </div>

            <!-- SUMMARY -->
            <div class="col-lg-4">
                <div class="summary-card">

                    <div class="summary-tip" style="flex-direction:column;">
                        <div style="display:flex; gap:8px; align-items:flex-start; margin-bottom:8px;">
                            <i class="bi bi-info-circle"></i>
                            <span><strong>ID Requirements</strong></span>
                        </div>
                        <span style="font-weight:600;">
                            If claimant is a <strong>Student</strong>, present School ID. 
                            If <strong>Guardian</strong>, present ID of their child studying here. 
                            If <strong>Outsider</strong>, present any valid government ID.
                        </span>
                    </div>

                </div>
            </div>

        </div>
    </div>


<!-- ========= Default js ========== -->
<script>
function toggleSidebar(){
    document.getElementById("sidebar")
    .classList.toggle("show");

    document.getElementById("overlay")
    .classList.toggle("show");
}

/* CLOSE WHEN CLICK OVERLAY */
document.getElementById("overlay")
.addEventListener("click", function(){
    document.getElementById("sidebar")
    .classList.remove("show");

    this.classList.remove("show");
});

/* FIX RESIZE */
window.addEventListener("resize", ()=>{
    if(window.innerWidth >= 992){
        document.getElementById("sidebar")
        .classList.remove("show");

        document.getElementById("overlay")
        .classList.remove("show");
    }
});
</script>

<!-- Auto Log-out -->
<script>
    // check session every 15 minutes
    setInterval(() => {
        fetch('../middleware/adminAutoLogout.php')
            .then(res => res.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'adminLogin.php';
                }
            })
            .catch(err => console.error(err));
    }, 900000); // Every 15 minutes
</script>

</body>
</html>