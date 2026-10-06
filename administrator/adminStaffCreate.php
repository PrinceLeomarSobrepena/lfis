<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token mismatch.");
    }

    $firstName = htmlspecialchars(trim($_POST['firstName']));
    $lastName  = htmlspecialchars(trim($_POST['lastName']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact   = trim($_POST['contact']);
    // $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $password = password_hash('staff_123', PASSWORD_DEFAULT);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    } 

    // ✅ Image handling
    $imageName = $_FILES['image']['name'] ?? '';
    $imageTmp  = $_FILES['image']['tmp_name'] ?? '';
    $finalImageName = 'default.jpg';

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
                $finalImageName = 'default.jpg';
            }
        } else {
            $finalImageName = 'default.jpg';
        }
    }

    $mustChangePassword = 0;
    $stmt = $conn->prepare("INSERT INTO staff (firstName, lastName , email, contact, image, password, mustChangePassword) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $firstName,  $lastName , $email, $contact, $finalImageName, $password,  $mustChangePassword);
    $stmt->execute();
    $stmt->close();


    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: adminStaffList.php");
    exit;
    
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
/* ====================== PHOTO UPLOAD ===================== */
.photo-upload-row{
    display:flex;
    align-items:center;
    gap:18px;
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

/* ====================== SIDE SUMMARY ===================== */
.summary-card{
    text-align:center;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:22px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.summary-avatar{
    display:flex;
    align-items:center;
    justify-content:center;
    width:84px;
    height:84px;
    font-size:30px;
    color:#c9e9d7;
    border-radius:50%;
    background: #F3FBF7;
    border:2px solid #E7ECE9;
    margin:0 auto 14px;
    overflow:hidden;
}

.summary-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:none;
}

.summary-card h6{
    font-weight:800;
    font-size:14.5px;
    color: #0F1B2D;
    margin-bottom:2px;
}

.summary-divider{
    border:none;
    border-top:1px solid #F0F2F1;
    margin:16px 0;
}

.summary-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:12px;
    padding:8px 0;
    text-align:left;
}

.summary-row span:first-child{
    font-weight:700;
    color: #7C8A85;
}

.summary-row span:last-child{
    font-weight:800;
    color: #0F1B2D;
    text-align:right;
    word-break:break-word;
}

.summary-tip{
    background:#E8F7EF;
    border-radius:10px;
    padding:12px 14px;
    font-size:11.5px;
    color:#147a49;
    font-weight:600;
    margin-top:16px;
    display:flex;
    gap:8px;
    align-items:flex-start;
    line-height:1.5;
    text-align:left;
}

@media(max-width:992px){
      /* .photo-upload-row{
        flex-direction:column;
        align-items:flex-start;
    } */

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
       
        <a href="adminStaffList.php" class="active"> <i class="bi bi-people"></i> Staff </a>
        <a href="adminStaffCreate.php" class="active" style="background: #30b175;margin-left: 20px;"> 
           <i class="bi bi-arrow-return-right"></i>
            <span style="padding-left:7px">Create</span> 
        </a>

        <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
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
            <h5 class=" d-none d-lg-block">Hi, Admin</h5>
            <img src="https://picsum.photos/200"  class="profile-img d-none d-lg-block">
        </div>
    </nav>

            <!-- MAIN-CONTENT -->
    <div class="main-content">
        <div class="mt-4 mb-3">
            <div class="page-heading">Create</div>
            <div class="page-subheading">Everyone with access to manage the system.</div>
        </div>  

        <div class="row g-3">
            <!-- FORM -->
            <div class="col-lg-8">
                <div class="form-card">

                    <form method="POST"  enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="form-section-title"><i class="bi bi-image"></i> Profile Photo</div>

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

                        <hr class="divider-line">

                        <div class="form-section-title"><i class="bi bi-person"></i> Personal Details</div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="req">*</span></label>
                                <input type="text" name="firstName" class="form-control-custom" placeholder="e.g. Juan" oninput="updateSummary()" id="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="req">*</span></label>
                                  <input type="text" name="lastName" class="form-control-custom" placeholder="e.g. Ramirez" oninput="updateSummary()" id="lastName" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contact Number <span class="req">*</span></label>
                                <input type="text" name="contact" class="form-control-custom" placeholder="09XX XXX XXXX" oninput="updateSummary()" id="contact"  required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="req">*</span></label>
                                <input type="email" name="email" class="form-control-custom" placeholder="juan.ramirez@lfis.edu.ph" oninput="updateSummary()" id="email" required>
                            </div>                            
                        </div>

                        <div class="form-actions">
                            <!-- <button class="btn-cancel">Cancel</button> -->
                            <button class="btn-submit"><i class="bi bi-check-lg"></i> Create Staff Account</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SUMMARY -->
            <div class="col-lg-4">
                <div class="summary-card">
                    <div class="summary-avatar" id="summaryAvatar">
                        <i class="bi bi-person"></i>
                        <img id="summaryImg" alt="Preview">
                    </div>
                    <h6 id="sumName">New Staff Member</h6>

                    <hr class="summary-divider">

                    <div class="summary-row"><span>Email</span><span id="sumEmail">—</span></div>
                    <div class="summary-row"><span>Contact</span><span id="sumContact">—</span></div>
                    <div class="summary-row"><span>Status</span><span>Active</span></div>

                    <div class="summary-tip">
                        <i class="bi bi-info-circle"></i>
                        <span>A login invite will be sent to the email address once the account is created.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>



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


<script>
const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function () {
        const email = this.value.trim();
        const regex = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;

        this.setCustomValidity("");

        if (email === "") return;

        if (!regex.test(email)) {
            this.setCustomValidity("Please enter a valid email address.");
            return;
        }

        fetch('checkStaff_email.php?email=' + encodeURIComponent(email))
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    this.setCustomValidity("This email is already registered.");
                } else {
                    this.setCustomValidity("");
                }
            })
            .catch(() => {
                this.setCustomValidity("Unable to verify email.");
            });
    }
);
</script>

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