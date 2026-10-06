<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
date_default_timezone_set('Asia/Manila');

$created_by = $_SESSION['admin']['id'];
  
// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// HANDLE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed");
    }

    $location = trim($_POST['reported_location']);
    $category = trim($_POST['category']);
    $status   = trim($_POST['status']);
    $email    = trim($_POST['email'] ?? '');

    // IMAGE
// ✅ Image handling
    $imageName = $_FILES['image']['name'] ?? '';
    $imageTmp  = $_FILES['image']['tmp_name'] ?? '';
    $finalImageName = 'default1.png';

    if (!empty($imageName)) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExt   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $fileType = mime_content_type($imageTmp);

        // Get file extension
        $fileExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes) && in_array($fileExt, $allowedExt)) {
            $imageNameClean = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", basename($imageName));
            $finalImageName = time() . '_' . $imageNameClean;
            $uploadPath = '../uploads/' . $finalImageName;

            if (!move_uploaded_file($imageTmp, $uploadPath)) {
                $finalImageName = 'default1.png';
            }
        } else {
            $finalImageName = 'default1.png';
        }
    }

    // FIELDS
    $reporter_role       = $_POST['reporter_role'] ?? NULL;
    $reporter_name       = $_POST['reporter_name'] ?? NULL;
    $reporter_id = $_POST['reporter_id'] ?? NULL;
    $reporter_year       = $_POST['reporter_year'] ?? NULL;
    $reporter_department = $_POST['reporter_department'] ?? NULL;


    // VALIDATION: Anonymous is not allowed sa Lost reports
    if ($status === 'Lost' && $reporter_role === 'Anonymous') {
        die("Anonymous reporting is not allowed for Lost item reports. Please provide a name.");
    }

    // ANONYMOUS: name is always "Anonymous" (ignore anything submitted)
    if ($reporter_role === 'Anonymous') {
        $reporter_name = 'Anonymous';
    }

    // VALIDATION: Student/Faculty/Visitor need a name
    if (in_array($reporter_role, ['Student', 'Faculty', 'Visitor / Guest']) && empty(trim($reporter_name ?? ''))) {
        die("Reporter name is required.");
    }

    // VALIDATION: Student and Anonymous need ID, year, and department
    if (in_array($reporter_role, ['Student', 'Anonymous']) && (empty($reporter_id) || empty($reporter_year) || empty($reporter_department))) {
        die("ID, Year, and Department are required.");
    }

    // VALIDATION: Faculty needs ID
    if ($reporter_role === 'Faculty' && empty(trim($reporter_id ?? ''))) {
        die("Faculty ID is required.");
    }

    // CLEANUP: walang ID/year/department ang Visitor / Guest
    if ($reporter_role === 'Visitor / Guest') {
        $reporter_id         = NULL;
        $reporter_year       = NULL;
        $reporter_department = NULL;
    }

    // CLEANUP: walang year/department ang Faculty
    if ($reporter_role === 'Faculty') {
        $reporter_year       = NULL;
        $reporter_department = NULL;
    }

    
    $cash_amount = $_POST['cash_amount'] ?? NULL;

    $gadget_type = $_POST['gadget_type'] ?? NULL;
    $gadget_brand = $_POST['gadget_brand'] ?? NULL;
    $gadget_color = $_POST['gadget_color'] ?? NULL;
    $gadget_description = $_POST['gadget_description'] ?? NULL;

    $document_type = $_POST['document_type'] ?? NULL;
    $document_name = $_POST['document_name'] ?? NULL;

    $other_title       = $_POST['other_title'] ?? NULL;
    $other_description = $_POST['other_description'] ?? NULL;

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO lost_found (
            reported_location,
            category,
            status,
            email,
            reporter_role,
            reporter_name,
            reporter_id,
            reporter_year,
            reporter_department,
            image,
            cash_amount,
            gadget_type,
            gadget_brand,
            gadget_color,
            gadget_description,
            document_type,
            document_name,
            other_title,
            other_description,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssssssdssssssssi",   // note: 4 bagong "s" para sa role/name/year/department
        $location,
        $category,
        $status,
        $email,
        $reporter_role,
        $reporter_name,
        $reporter_id,
        $reporter_year,
        $reporter_department,
        $finalImageName,
        $cash_amount,
        $gadget_type,
        $gadget_brand,
        $gadget_color,
        $gadget_description,
        $document_type,
        $document_name,
        $other_title,
        $other_description,
        $created_by
    );

    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    $hasMatch = false;

    // MATCH SYSTEM
    if ($status === 'Lost') {
        $matchStmt = $conn->prepare("SELECT * FROM lost_found WHERE status = 'Found' AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7");
    } else {
        $matchStmt = $conn->prepare("SELECT * FROM lost_found WHERE status = 'Lost' AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND ABS(DATEDIFF(created_at, NOW())) <= 7");
    }
    $matchStmt->bind_param("s", $category);
    $matchStmt->execute();
    $matches = $matchStmt->get_result();

    while ($match = $matches->fetch_assoc()) {

        $matched = false;

        if ($category === 'Cash') {
            if ($cash_amount == $match['cash_amount']) $matched = true;  
        }
        elseif ($category === 'Gadget') {
            if (
                strtolower($gadget_type) == strtolower($match['gadget_type']) &&
                strtolower($gadget_brand) == strtolower($match['gadget_brand']) &&
                strtolower($gadget_color) == strtolower($match['gadget_color'])
            ) $matched = true;
        }
        elseif ($category === 'Document') {
            if (strtolower($document_name) == strtolower($match['document_name'])) $matched = true;
        }
        elseif ($category === 'Other') {
            if (
                strtolower($other_title) == strtolower($match['other_title']) &&
                strtolower($other_description) == strtolower($match['other_description'])
            ) $matched = true;
        }

        if ($matched) {
            $hasMatch = true;

            $lostId = ($status === 'Lost') ? $newId : $match['id'];
            $foundId = ($status === 'Found') ? $newId : $match['id'];

            $check = $conn->prepare("SELECT id FROM lost_found_matches WHERE lost_id = ? AND found_id = ?");
            $check->bind_param("ii", $lostId, $foundId);
            $check->execute();

            if ($check->get_result()->num_rows === 0) {
                $insertMatch = $conn->prepare("INSERT INTO lost_found_matches (lost_id, found_id) VALUES (?, ?)");
                $insertMatch->bind_param("ii", $lostId, $foundId);
                $insertMatch->execute();

                // Email Send
                $receiverEmail = '';

                if ($status === 'Found') {
                    $receiverEmail = $match['email'];
                } else {
                    $receiverEmail = $email;
                }

                if (!empty($receiverEmail)) {

                    $emailData = http_build_query([
                        'receiver_email' => $receiverEmail,
                        'category' => $category,
                        'reported_location' => $match['reported_location']
                    ]);

                    $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/adminLostFoundCreateEmail.php');

                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $emailData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    curl_exec($ch);
                    curl_close($ch);
                }


            }
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // header("Location: " . ($hasMatch ? "adminLostFoundMatches.php" : "adminLostFoundList.php"));

    // if ($hasMatch) {
    //     header("Location: adminLostFoundMatches.php");
    // } else {
    //     header("Location: 1adminLostFoundCreate.php");
    // }

    if ($hasMatch) {
        header("Location: adminLostFoundMatches.php");
} else {
    $_SESSION['sweet_alert'] = [
        'icon'  => 'success',
        'title' => 'Item Saved!',
        'text'  => 'No matches were found yet, but the item has been recorded.'
    ];
    header("Location: adminLostFoundCreate.php");
}

    exit();
}
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
/* Spinner styles */
.spinner-wrapper{
    background-color: rgba(255,255,255,0.9);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: none;
    justify-content: center;
    align-items: center;
}

.spinner-border{
    height: 60px;
    width: 60px;
}

    /* ====================== PHOTO UPLOAD ===================== */
.photo-upload-row{
    display:flex;
    align-items:center;
    gap:18px;
    /* margin-bottom: 0px; */
}

.photo-preview{
    display:flex;
    align-items:center;
    justify-content:center;
    width:84px;
    height:84px;
    font-size:28px;
    color: #198754;
    border-radius:16px;
    background: #F3FBF7;
    border:2px dashed #c9e9d7;
    flex-shrink:0;
    overflow:hidden;
    position:relative;
}

.photo-preview img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:none;
}

.photo-upload-actions{
    flex:1;
}

.btn-upload-photo{
    display:inline-flex;
    align-items:center;
    font-weight:700;
    font-size:12.5px;
    color: #4B5A54;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:9px;
    padding:9px 18px;
    gap:7px;
    cursor:pointer;
}

.btn-upload-photo:hover{background:#f5f6fa;}

.photo-hint{
    font-weight:600;
    font-size:11px;
    color:#9AA6A1;
    margin-top:8px;
}

    /* ====================== CCREATE LOST AND FOUND ===================== */
    .type-toggle{
        display: flex;
        gap:10px;
        margin-bottom:20px;
    }

    .type-option{
        display:flex;
        flex:1;
        background:#fff;
        border:1.5px solid #E7ECE9;
        border-radius:12px;
        padding:15px 18px; /* 16px */
        cursor:pointer;
        align-items:center;
        gap:12px;
        transition:.2s ease;
    }

    /* .type-option:hover{
        border-color: #c9e9d7;
        transform:translateY(-2px);                    
        box-shadow:0 10px 26px rgba(20,60,40,0.08);  
    } */

    .type-option.active{
        border-color: #198754;
        /* background: #F3FBF7; */
    }

    .type-icon{
        display:flex;
        width: 40px;
        height:40px;
        border-radius:10px;
        align-items:center;
        justify-content:center;
        font-size:17px;
        flex-shrink:0;
    }

    .type-text h6{
        font-weight:800;
        font-size:13.5px;
        margin-bottom:2px;
        color:#0F1B2D;
    }

    .type-text p{
        font-weight:600;
        font-size:11.5px;
        color: #7C8A85;
        margin:0;
    }

    .type-check{
        display:flex;
        margin-left:auto;
        width:20px;
        height:20px;
        border-radius:50%;
        border:2px solid #dee2e6;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
    }

    .type-option.active .type-check{
        border-color: #198754;
        background: #198754;
        color: #fff;
        font-size:17px;
    }

    /* ====================== FORM CARD ===================== */
    .form-card{
        background: #fff;
        border:1px solid #E7ECE9;
        border-radius:14px;
        padding:26px;
        box-shadow:0 4px 20px rgba(20,60,40,0.04);
    }

    .form-section-title{
        display:flex;
        font-weight:800;
        font-size:14px;
        color: #0F1B2D;
        margin-bottom:16px;
        align-items:center;
        gap: 8px;
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
        color:#E1596B;
    }

    /* ====================== INPUT ===================== */
    .form-control-custom{
        font-family:'Nunito', Arial, sans-serif;
        width:100%;
        border: 1px solid #E7ECE9;
        border-radius:10px;
        padding:11px 14px;
        font-weight:600;
        font-size:13.5px;
        color: #0F1B2D;
    }

    .form-control-custom:focus{
        outline:none;
        border-color:#198754;
        background:#fff;
        color: #4B5A54;
    }

    .divider-line{
        border:none;
        border-top:1px solid #EEF1F0;
        margin:22px 0;
    }

    /* ====================== UPLOAD BOX ===================== */
    .upload-box{
        border:2px dashed #dbe6e0;
        border-radius:12px;
        padding:26px 18px;
        text-align:center;
        /* background: #FAFEFC; */
        cursor:pointer;
        margin-top:0;
        transition:.2s;
    }

    .upload-box:hover{
        border-color: #198754;
        /* background: #F3FBF7; */
    }

    .upload-box i{
        display:block;
        font-size:26px;
        color: #198754;
        margin-bottom:8px;
    }

    .upload-box h6{
        font-weight:800;
        font-size:13;
        margin-bottom:2px;
        color: #0F1B2D;
    }
    .upload-box p{
        font-weight:600;
        font-size:11.5px;
        color: #7C8A85;
        margin:0;
    }

    /* ====================== ACTION BUTTONS ===================== */
    .form-actions{
        display:flex;
        justify-content:flex-end;
        gap:10px;
        margin-top:22px;
    }

    .btn-reset{
        font-weight:700;
        font-size:13.5px;
        color: #4B5A54;
        background: #fff;
        border:1px solid #E7ECE9;
        border-radius:10px;
        padding:11px 22px;
        text-decoration:none;
    }

    .btn-reset:hover{
         background:#f5f6fa;
    }

    .btn-submit{
        display:flex;
        font-weight:700;
        font-size:13.5px;
        background:linear-gradient(135deg, #198754, #147a49);
        color: #fff;
        border:none;
        border-radius:10px;
        padding: 11px 24px;
        align-items:center;
        gap:8px;
        box-shadow:0 6px 14px rgba(25,135,84,0.25);
    }

    /* ====================== SIDE SUMMARY ===================== */
    .match-card{
        background: #fff;
        border:1px solid #E7ECE9;
        border-radius:14px;
        padding:22px;
         box-shadow:0 4px 20px rgba(20,60,40,0.04);
    }

    .match-card h6{
        font-weight:800;
        font-size:13.5px;
        margin-bottom:12px;
        color: #0F1B2D;;
    }

    .match-tip{
        display:flex;
        align-items:flex-start;
        font-weight:600;
        font-size:11.5px;
        color: #147a49;
        padding: 12px 14px;
        background: #E8F7EF;
        border-radius: 10px;
        gap:8px;
        line-height:1.5;
        margin-top:16px;
    }

    .match-tip i{
        margin-top:1px;
    }

.match-card-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
}

.match-badge{
    display:inline-block;
    font-weight:800;
    font-size:10px;
    border-radius:20px;
    padding:3px 10px;
    flex-shrink:0;
}

.match-badge-lost{background:#FCEAED;color:#D9534F;}
.match-badge-found{background:#EAF7EF;color:#198754;}

    /* ============ sidebar right ============ */
    .sidebar-sticky-right{
        position:sticky;
        top:15px;
    }

    /* ====================== FORM CARD MEDIA ===================== */
    @media(max-width: 1200px){
        .type-toggle{
            flex-direction:column;
        }
    }
    @media(max-width: 992px){
        /* .type-toggle{
            flex-direction:column;
        } */

    }


</style>
</head>
<body>
    <?php if (!empty($_SESSION['sweet_alert'])): ?>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        Swal.fire({
            icon: "<?= $_SESSION['sweet_alert']['icon'] ?>",
            title: "<?= addslashes($_SESSION['sweet_alert']['title']) ?>",
            text: "<?= addslashes($_SESSION['sweet_alert']['text']) ?>",
            confirmButtonColor: "#198754"
        });
    });
    </script>
    <?php unset($_SESSION['sweet_alert']); endif; ?>

    <!-- Spinner -->
    <div class="spinner-wrapper" id="spinner">
        <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

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
        <a href="adminLostFoundCreate.php" class="active" style="background: #30b175;margin-left: 20px;"> 
           <i class="bi bi-arrow-return-right"></i>
            <span style="padding-left:7px">Create</span> 
        </a>
        
        <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"> <i class="bi bi-graph-up-arrow"></i> Statistic </a>
        <a href="adminLostFoundCalendar.php"> <i class="bi bi-calendar-event"></i> Calendar </a>
        <a href="adminChangePassword.php"> <i class="bi bi-lock" style="display: inline-block; transform: scaleX(1.4);"></i> Change Password </a>
        <a href="adminLogout.php" class="logout-btn"> <i class="bi bi-box-arrow-right"></i> Logout </a>
    </div>


    <!-- NAVBAR -->
    <nav class="navbar-custom shadow-sm">
        <div class="navbar-left">
            <img src="../uploads/SCHOOL.jpg" class="profile-img ">  <!--  d-lg-none -->

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
            <div class="page-heading">Log a Lost or Found Item</div>
            <div class="page-subheading">Record item details so it can be searched, matched, and claimed.</div>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="form-card">
                    <!-- <div class="form-section-title"><i class="bi bi-box-seam"></i> Item Details</div> -->

                    <form method="POST" enctype="multipart/form-data" id="itemForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="row g-3">
                        
                            <!-- CHECK TYPE TOGGLE -->
                            <div>  <!-- optional div -->
                                <div class="type-toggle">
                                    <input type="hidden" name="status" id="statusInput" value="Found">

                                    <div class="type-option active" onclick="selectType(this,'found')">
                                        <div class="type-icon" style="background: #E8F7EF;color: #198754;"><i class="bi bi-box-seam"></i></div>
                                        <div class="type-text">
                                            <h6>Found Item</h6>
                                            <p>Someone turned this item in</p>
                                        </div>
                                        <div class="type-check"><i class="bi bi-check"></i></div>
                                    </div>

                                    <div class="type-option" onclick="selectType(this,'lost')">
                                        <div class="type-icon" style="background: #FCEAED;color: #E1596B;"><i class="bi bi-question-circle"></i></div>
                                        <div class="type-text">
                                            <h6>Lost Item Report</h6>
                                            <p>An item report it missing</p>
                                        </div>
                                        <div class="type-check"><i class="bi bi-check"></i></div>
                                    </div>
                                </div>
                            </div>


                            <!-- UPLOAD IMAGE -->
                            <label class="form-label" style="margin-bottom: -10px">Upload Photo <span class="req">*</span> ( not required )</label>
                            <div class="photo-upload-row">
                                <div class="photo-preview" id="photoPreview">
                                    <i class="bi bi-person" id="photoPlaceholder"></i>
                                    <img id="photoImg" alt="Preview">
                                </div>
                                <div class="photo-upload-actions">
                                    <label class="btn-upload-photo" for="photoInput">
                                        <i class="bi bi-cloud-arrow-up"></i> Upload Photo
                                    </label>
                                    <input type="file" name="image" id="photoInput" accept="image/png, image/jpeg" style="display:none;" onchange="previewPhoto(event)">
                                    <div class="photo-hint">PNG or JPG, up to 5MB. Square image recommended.</div>
                                </div>
                            </div>


                            <!-- REPORTER -->
                            <div class="col-md-12" style="margin-top: 20px">
                                <label class="form-label">Reporter Type <span class="req">*</span></label>
                                <select class="form-control-custom" name="reporter_role" onchange="showReporterFields(this)" required>
                                    <option value="" disabled selected>Select reporter type</option>
                                    <option value="Student">Student</option>
                                    <option value="Faculty">Faculty</option>
                                    <option value="Visitor / Guest">Visitor / Guest</option>
                                    <option value="Anonymous">Anonymous</option>
                                </select>

                                <div id="reporterFields" class="mt-3"></div>
                            </div>


                            <div class="col"> 
                                 <div id="locationContainer" class="mt-0"></div>
                            </div>

                            <!-- <div class="col-md-8">
                                <label class="form-label">Item Name <span class="req">*</span></label>
                                <input type="text" class="form-control-custom" placeholder="e.g. Brown Leather Wallet">
                            </div> -->
                            <div class="col-md-12"  style="margin-top: 0px">
                                <label class="form-label">Category <span class="req">*</span></label>
                                <select class="form-control-custom" name="category"  onchange="showCategory(this); loadMatches();" required>
                                    <option value="" disabled selected>Select Category</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Gadget">Gadget</option>
                                    <option value="Document">Document</option>
                                    <option value="Other">Other</option>
                                </select>

                                <div id="dynamicFields" class="mt-3"></div>
                            </div>


                            <div class="form-actions">
                                <!-- <button type="reset" class="btn-reset">Reset</button> -->
                                  <a href="adminLostFoundList.php" class="btn-reset">Cancel</a>
                                <button class="btn-submit"><i class="bi bi-check-lg"></i> Save Item</button>
                            </div>
                        </div>
                    </form>
                  
                </div>
            </div>

            <!-- MATCH -->
            <div class="col-lg-4">
                <div class="sidebar-sticky-right">
                    <div class="match-card" id="possibleMatchesCard">
                        <?php
                            $currentDate = date('F d');
                            $startDate = date('F d', strtotime('-6 days'));
                        ?>
                        <h6>Posible Match:</h6>
                        <div id="matchResults"></div>
                        <div class="match-tip">
                            <i class="bi bi-info-circle"></i>
                            <span>Once saved, this item is automatically checked against active lost reports for a possible match, if today is <b><?= $currentDate ?></b>, the system will only check reports dated <b><?= $startDate ?> to <?= $currentDate ?></b>.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

<!-- spinner -->
<script>
const itemForm = document.getElementById('itemForm');
const itemSpinner = document.getElementById('spinner');

itemForm.addEventListener('submit', () => {
    itemSpinner.style.display = 'flex'; // show spinner immediately
});
</script>


<!-- VIEW PHOTO -->
<script>
/* PHOTO PREVIEW */
function previewPhoto(event){
    const file = event.target.files[0];
    if(!file) return;

    const reader = new FileReader();
    reader.onload = function(e){
        document.getElementById("photoImg").src = e.target.result;
        document.getElementById("photoImg").style.display = "block";
        document.getElementById("photoPlaceholder").style.display = "none";

        document.getElementById("summaryImg").src = e.target.result;
        document.getElementById("summaryImg").style.display = "block";
        document.querySelector("#summaryAvatar i").style.display = "none";
    };
    reader.readAsDataURL(file);
}


/* LIVE SUMMARY */
function updateSummary(){
    const first = document.getElementById("firstName").value.trim();
    const last = document.getElementById("lastName").value.trim();
    const email = document.getElementById("email").value.trim();
    const contact = document.getElementById("contact").value.trim();
   

    document.getElementById("sumName").innerText = (first || last) ? (first + " " + last).trim() : "New Staff Member";
    document.getElementById("sumEmail").innerText = email || "—";
    document.getElementById("sumContact").innerText = contact || "—";
  
}
</script>


<!-- REPORTER -->
<script>
function showReporterFields(select) {
    let html = '';

    if (select.value === 'Student') {
        html = `
            <div class="row">
                <div class="col-md-8">
                    <label class="form-label">Name <span class="req">*</span></label>
                    <input type="text" name="reporter_name" class="form-control-custom mb-3" placeholder="e.g. Juan Dela Cruz" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Student ID <span class="req">*</span></label>
                    <input type="text" name="reporter_id" class="form-control-custom mb-3" placeholder="e.g. 2026-00123" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Year <span class="req">*</span></label>
                    <select name="reporter_year" class="form-control-custom mb-3" required>
                        <option value="" disabled selected>Select year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Department <span class="req">*</span></label>
                    <select name="reporter_department" class="form-control-custom mb-3" required>
                        <option value="" disabled selected>Select department</option>
                        <option value="BSA">BSA</option>
                        <option value="CRIM">CRIM</option>
                        <option value="EDUC">EDUC</option>
                        <option value="HMTM">HMTM</option>
                        <option value="IT">IT</option>
                        <option value="RAD TECH / MED TECH">RAD TECH / MED TECH</option>
                    </select>
                </div>
            </div>
        `;
    }

    else if (select.value === 'Faculty') {
        html = `
            <div class="row">
                <div class="col-md-8">
                    <label class="form-label">Name <span class="req">*</span></label>
                    <input type="text" name="reporter_name" class="form-control-custom mb-3" placeholder="e.g. Sir. Carlo Niño Castillo" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Faculty ID <span class="req">*</span></label>
                    <input type="text" name="reporter_id" class="form-control-custom mb-3" placeholder="e.g. F-2026-045" required>
                </div>
            </div>
        `;
    }

    else if (select.value === 'Visitor / Guest') {
        html = `
            <label class="form-label">Name <span class="req">*</span></label>
            <input type="text" name="reporter_name" class="form-control-custom mb-3" placeholder="e.g. Juan Dela Cruz" required>
        `;
    }

    else if (select.value === 'Anonymous') {
        html = `
            <div class="row">
                <div class="col-md-12">
                    <div class="bg-light mb-3" style="font-weight:600; font-size:13px; padding:10px 13px; border-radius:10px;">
                        Name will be recorded as <b>Anonymous</b>. ID, Year, and Department are still required.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Student ID <span class="req">*</span></label>
                    <input type="text" name="reporter_id" class="form-control-custom mb-3" placeholder="e.g. 2026-00123" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Year <span class="req">*</span></label>
                    <select name="reporter_year" class="form-control-custom mb-3" required>
                        <option value="" disabled selected>Select year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Department <span class="req">*</span></label>
                    <select name="reporter_department" class="form-control-custom mb-3" required>
                        <option value="" disabled selected>Select department</option>
                        <option value="BSA">BSA</option>
                        <option value="CRIM">CRIM</option>
                        <option value="EDUC">EDUC</option>
                        <option value="HMTM">HMTM</option>
                        <option value="IT">IT</option>
                        <option value="RAD TECH / MED TECH">RAD TECH / MED TECH</option>
                    </select>
                </div>
            </div>
        `;
    }

    document.getElementById('reporterFields').innerHTML = html;
}
</script>



<!-- FOUND -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    showLocation("Found");
    updateReporterRoleOptions("Found");
});
</script>


<!-- LOST -->
<script>
// function showLocation(status) {
//     let html = '';

//     if (status === 'Lost') {
//         html = `
//             <label class="form-label"> Lost Location <span class="req">*</span></label>
//             <input type="text" name="reported_location" class="form-control-custom mb-3" placeholder="e.g. Lost in room 303" required>

//             <label class="form-label"> Email <span class="req">*</span></label>
//             <input type="email" name="email" class="form-control-custom mb-3" placeholder="e.g. example@gmail.com" required>
//         `;
//     } else {
//         html = `
//             <label class="form-label"> Found Location <span class="req">*</span></label>
//             <input type="text" name="reported_location" class="form-control-custom mb-3" placeholder="e.g. Found in room 303" required>
//         `;
//     }

//     document.getElementById('locationContainer').innerHTML = html;
// }


function locationOptionsHtml(selectedValue = '') {
    const locations = [
        'Main Lobby',
        'Library',
        'Canteen / Cafeteria',
        'Gymnasium',
        'Computer Laboratory',
        'Science Laboratory',
        'Registrar\'s Office',
        'Guidance Office',
        'Faculty Room',
        'Parking Area',
        'Comfort Room',
        'Chapel',
        'Other'
    ];

    let html = `<option value="" disabled ${!selectedValue ? 'selected' : ''}>Select location</option>`;

    locations.forEach(loc => {
        html += `<option value="${loc}" ${selectedValue === loc ? 'selected' : ''}>${loc}</option>`;
    });

    return html;
}

function showLocation(status) {
    let html = '';

    if (status === 'Lost') {
        html = `
            <label class="form-label"> Email <span class="req">*</span></label>
            <input type="email" name="email" class="form-control-custom mb-3" placeholder="e.g. example@gmail.com" required>

            <label class="form-label"> Lost Location <span class="req">*</span></label>
            <select name="reported_location_select" class="form-control-custom mb-3" onchange="handleLocationSelect(this)" required>
                ${locationOptionsHtml()}
            </select>
            <input type="text" name="reported_location" id="otherLocationInput" class="form-control-custom mb-3" placeholder="Specify location" style="display:none;">
        `;
    } else {
        html = `
            <label class="form-label"> Found Location <span class="req">*</span></label>
            <select name="reported_location_select" class="form-control-custom mb-3" onchange="handleLocationSelect(this)" required>
                ${locationOptionsHtml()}
            </select>
            <label class="form-label"> Other Location <span class="req">*</span></label>
            <input type="text" name="reported_location" id="otherLocationInput" class="form-control-custom mb-3" placeholder="Specify location" style="display:none;">
        `;
    }

    document.getElementById('locationContainer').innerHTML = html;
}

function handleLocationSelect(select) {
    const otherInput = document.getElementById('otherLocationInput');

    if (select.value === 'Other') {
        otherInput.style.display = 'block';
        otherInput.value = '';
        otherInput.required = true;
        otherInput.name = 'reported_location';
        select.removeAttribute('name');
    } else {
        otherInput.style.display = 'none';
        otherInput.required = false;
        otherInput.removeAttribute('name');
        select.name = 'reported_location';
    }
}
</script>


<!-- CATEGORY -->
<script>
function showCategory(select) {

    let html = '';

    if (select.value === 'Cash') {
        html = `
            <label class="form-label"> Amount <span class="req">*</span></label>
            <input type="number" name="cash_amount" class="form-control-custom mb-3" placeholder="e.g. 500" required>
        `;
    }

    else if (select.value === 'Gadget') {
        html = `
            <label class="form-label"> Gadget Type <span class="req">*</span></label>
            <select name="gadget_type" class="form-control-custom   mb-3" onchange="showGadgetFields(this)" style=" outline:none;" required>
                <option value="" disabled selected>Select gadget type</option>
                <option value="Cell Phone">Cell Phone</option>
                <option value="Laptop">Laptop</option>
                <option value="Tablet">Tablet</option>
                <option value="Smart watches">Smart watches</option>
                <option value="Audio Gadgets">Audio Gadgets</option>
            </select>

            <div id="gadgetFields"></div>
        `;
    }

    else if (select.value === 'Document') {
        html = `
            <label class="form-label"> Document type <span class="req">*</span></label>
            <input type="text" name="document_type" class="form-control-custom mb-3" placeholder="e.g id, passport, psa" required>

            <label class="form-label"> Name on Document <span class="req">*</span></label>
            <input type="text" name="document_name" class="form-control-custom mb-3" placeholder="e.g. name on document" required>
        `;
    }

    else if (select.value === 'Other') {
        html = `
            <label class="form-label"> Item Name <span class="req">*</span></label>
            <input type="text" name="other_title" class="form-control-custom mb-3" placeholder="e.g. Umbrella" required>

            <label class="form-label"> Description <span class="req">*</span></label>
            <input type="text" name="other_description" class="form-control-custom mb-3" placeholder="e.g. black folding umbrella with wooden handle" required>
        `;
    }

    document.getElementById('dynamicFields').innerHTML = html;
}
</script>


<!-- GADGET -->
<script>
function showGadgetFields(select) {
    let html = '';

    if (select.value !== '') {
        html = `
            <label class="form-label"> Brand / Model <span class="req">*</span></label>
            <input type="text" name="gadget_brand" class="form-control-custom mb-3" placeholder="e.g. Samsung, Apple, Asus" required>

            <label class="form-label"> Color <span class="req">*</span></label>
            <input type="text" name="gadget_color" class="form-control-custom mb-3" placeholder="e.g. black" required>

            <label class="form-label"> Item Description <span class="req">*</span></label>
            <textarea name="gadget_description" class="form-control-custom mb-3" placeholder="e.g black case" required></textarea>
        `;
    }

    document.getElementById('gadgetFields').innerHTML = html;
}
</script>


<!-- MATCHES -->
<script>
async function loadMatches() {

    const status = document.querySelector('[name="status"]').value;
    const category = document.querySelector('[name="category"]').value;

    if (!status || !category) return;

    const formData = new FormData();
    formData.append('status', status);
    formData.append('category', category);

    const res = await fetch('checkMatches.php', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    let html = '';

    if (data.length === 0) {
        html = `
         <div class="bg-light" style="font-weight:600; font-size:13.5px; padding:10px 13px; border-radius:10px;"> No matches found</div>
        `; //alert alert-secondary
    }

    data.forEach(item => {

        let details = '';

        if (item.category === 'Cash') {
            details = `Amount: ₱${item.cash_amount}`; //💰
        } else if (item.category === 'Gadget') {
            details = `
            Type: ${item.gadget_type}<br>
            Brand/Model: ${item.gadget_brand}<br> 
            Color: ${item.gadget_color}`; //📱
        } else if (item.category === 'Document') {
            details = `
            Type: ${item.document_type}<br>
            Name: ${item.document_name}`;
        } else {
            details = `
            Item Name: ${item.other_title}<br>
            Description: ${item.other_description}`; //📝
        }

        const matchStatus = item.status || (status === 'Found' ? 'Lost' : 'Found');
        const badgeClass = matchStatus === 'Lost' ? 'match-badge-lost' : 'match-badge-found';

        html += `
            <div class="card mb-2 border-success">
                <div class="card-body" style="padding:10px 15px;">
                    <div class="match-card-head">
                        <b style="font-size:14px; color: #4B5A54;">${item.category}</b>
                        <span class="match-badge ${badgeClass}">${matchStatus}</span>
                    </div>
                    <span style="font-weight:600; font-size:12.5px; color: #4B5A54;">${details}</span><br>
                    <span style="font-weight:600; font-size:12.5px; color: #4B5A54;">Location: ${item.reported_location}</span>
                </div>
            </div>
        `;
    });

    document.getElementById('matchResults').innerHTML = html;

    if (data.length > 0 && window.innerWidth <= 992) {
        document.getElementById('possibleMatchesCard')
            .scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
    }

}
</script>


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


/* TYPE TOGGLE  sa lost and found no database*/
// function selectType(el, type){
//     document.querySelectorAll(".type-option").forEach(o=>o.classList.remove("active"));
//     el.classList.add("active");
//     document.getElementById("sumType").innerText = (type === "found") ? "Found Item" : "Lost Item Report";
// }
function selectType(el, type){
    document.querySelectorAll(".type-option").forEach(o=>o.classList.remove("active"));
    el.classList.add("active");

    const statusValue = (type === "lost") ? "Lost" : "Found";
    document.getElementById("statusInput").value = statusValue;

    showLocation(statusValue);
    updateReporterRoleOptions(statusValue);   // <-- bago
    loadMatches();
}

function updateReporterRoleOptions(status){
    const roleSelect = document.querySelector('[name="reporter_role"]');
    const currentValue = roleSelect.value;

    let optionsHtml = `<option value="" disabled selected>Select reporter type</option>
        <option value="Student">Student</option>
        <option value="Faculty">Faculty</option>
        <option value="Visitor / Guest">Visitor / Guest</option>`;

    // Anonymous is only allowed kapag Found
    if (status === 'Found') {
        optionsHtml += `<option value="Anonymous">Anonymous</option>`;
    }

    roleSelect.innerHTML = optionsHtml;

    // kung na-Anonymous na pero pumalit sa Lost, i-reset yung selection at fields
    if (status === 'Lost' && currentValue === 'Anonymous') {
        document.getElementById('reporterFields').innerHTML = '';
    } else {
        // panatilihin yung previous selection kung valid pa rin
        if ([...roleSelect.options].some(o => o.value === currentValue)) {
            roleSelect.value = currentValue;
        }
    }
}
</script>

<!-- Auto Log-out -->
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



<?php if (!empty($_SESSION['sweet_alert'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    Swal.fire({
        icon: "<?= $_SESSION['sweet_alert']['icon'] ?>",
        title: "<?= addslashes($_SESSION['sweet_alert']['title']) ?>",
        text: "<?= addslashes($_SESSION['sweet_alert']['text']) ?>",
        confirmButtonColor: "#198754"
    });
});
</script>
<?php unset($_SESSION['sweet_alert']); endif; ?>

</body>
</html>