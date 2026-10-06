<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
date_default_timezone_set('Asia/Manila');

$renewed_by = $_SESSION['admin']['id'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// GET ID
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    die("Missing item ID.");
}

// FETCH EXISTING RECORD
$fetchStmt = $conn->prepare("SELECT * FROM lost_found WHERE id = ?");
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$item = $fetchStmt->get_result()->fetch_assoc();
$fetchStmt->close();

if (!$item) {
    die("Item not found.");
}

// BLOCK RENEW kung claimed/resolved na
if ($item['is_claimed'] || $item['is_resolved']) {
    die("This item can no longer be renewed (already claimed or resolved).");
}

// BLOCK RENEW kung hindi pa naman Expired ang item
// (Renew ay para lang sa mga items na 8+ days na ang tanda)
$daysOld = (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($item['created_at'])))) / 86400;
$isItemExpired = ($daysOld > 7);

if (!$isItemExpired) {
    die("This item is not yet expired, so it cannot be renewed.");
}

// HANDLE POST — walang binabagong field, i-re-renew lang ang edad ng item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed");
    }

    $stmt = $conn->prepare("
        UPDATE lost_found SET
            renewed_by = ?,
            renewed_at = NOW(),
            created_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $renewed_by, $id);
    $stmt->execute();
    $stmt->close();

    // ✅ CLEANUP: burahin muna ang mga LUMANG unresolved matches ng item na ito
    // dahil bagong "buhay" na ang item pagkatapos ma-renew
    $cleanup = $conn->prepare("
        DELETE FROM lost_found_matches
        WHERE (lost_id = ? OR found_id = ?)
          AND is_resolved = 0
    ");
    $cleanup->bind_param("ii", $id, $id);
    $cleanup->execute();
    $cleanup->close();

    $status   = $item['status'];
    $category = $item['category'];
    $email    = $item['email'];

    $cash_amount        = $item['cash_amount'];
    $gadget_type        = $item['gadget_type'];
    $gadget_brand       = $item['gadget_brand'];
    $gadget_color       = $item['gadget_color'];
    $document_name      = $item['document_name'];
    $other_description  = $item['other_description'];

    $hasMatch = false;

    // MATCH SYSTEM (excluding sarili niya via AND id != ?)
    if ($status === 'Lost') {
        $matchStmt = $conn->prepare("SELECT * FROM lost_found WHERE status = 'Found' AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND id != ? AND ABS(DATEDIFF(created_at, NOW())) <= 7");
    } else {
        $matchStmt = $conn->prepare("SELECT * FROM lost_found WHERE status = 'Lost' AND category = ? AND is_claimed = 0 AND is_resolved = 0 AND id != ? AND ABS(DATEDIFF(created_at, NOW())) <= 7");
    }
    $matchStmt->bind_param("si", $category, $id);
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
            if (strtolower($other_description) == strtolower($match['other_description'])) $matched = true;
        }

        if ($matched) {
            $hasMatch = true;

            $lostId  = ($status === 'Lost') ? $id : $match['id'];
            $foundId = ($status === 'Found') ? $id : $match['id'];

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

    if ($hasMatch) {
        header("Location: adminLostFoundMatches.php");
    } else {
        $_SESSION['sweet_alert'] = [
            'icon'  => 'success',
            'title' => 'Item Renewed!',
            'text'  => 'The item has been renewed and is active again.'
        ];
        header("Location: adminLostFoundList.php");
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
/* ====================== PHOTO ===================== */
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
}

.item-details-right{
    flex: 1;
}

.item-details-right p{
    font-size: 12.5px;
    color: #4B5A54;
    margin-bottom: 6px;
}

/* ====================== CATEGORY BADGE ===================== */
.category-badge{
    display:inline-block;
    font-size:12.5px;
    font-weight:700;
}

.category-cash, .category-gadget, .category-document, .category-other{
    color: #4B5A54;
}

/* ====================== GADGET GRID ===================== */
.gadget-details-grid{
    display:flex;
    gap:24px;
}

.gadget-col{
    flex:0 0 auto;
    min-width: 100px;
}

/* ====================== EXPIRED NOTICE ===================== */
.expired-notice{
    display:flex;
    align-items:flex-start;
    gap:8px;
    font-weight:600;
    font-size:12.5px;
    color:#C0392B;
    background:#FDECEA;
    border-radius:10px;
    padding:12px 14px;
    margin-bottom:4px;
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

.form-control-custom{
    width:100%;
    border:1px solid #E7ECE9;
    border-radius:10px;
    padding:11px 14px;
    font-size:13.5px;
    font-weight:600;
    color: #0F1B2D;
    font-family:'Nunito', Arial, sans-serif;
    background: #FAFBFA;
}

/* ====================== DIVIDER ===================== */
.divider-line{
    border:none;
    border-top:1px solid #d7d7d7;
    margin:22px 0;
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

/* ====================== MATCH SIDEBAR ===================== */
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
    color: #0F1B2D;
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

.sidebar-sticky-right{
    position:sticky;
    top:15px;
}

@media(max-width:992px){
    .form-actions{
        flex-direction:column-reverse;
    }

    .form-actions button, .form-actions a{
        width:100%;
        justify-content:center;
    }
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
        <a href="adminLostFoundRenew.php?id=<?= $item['id'] ?>" class="active" style="background: #30b175;margin-left: 20px;">
           <i class="bi bi-arrow-return-right"></i>
            <span><i class="bi bi-arrow-clockwise"></i>&nbsp;&nbsp;&nbsp;Renew</span>
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
            <img src="../uploads/SCHOOL.jpg" class="profile-img">

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
            <div class="page-heading">Renew Item</div>
            <div class="page-subheading">This item has expired. Review the details below then renew to make it active again.</div>
        </div>

        <div class="row g-3">
            <!-- DETAILS (READ-ONLY) -->
            <div class="col-lg-8">
                <div class="form-card">

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

                        <div class="row g-3">

                            <div class="expired-notice">
                                <i class="bi bi-exclamation-circle"></i>
                                <span>This item has been unclaimed/unresolved for more than 7 days and is currently marked as <b>Expired</b>. Renewing will reset its report date to today and make it active again for matching.</span>
                            </div>

                            <div class="form-section-title"><i class="bi bi-box-seam"></i> Item Details</div>

                            <!-- IMAGE AND DETAILS -->
                            <div class="photo-upload-row">

                                <div class="photo-preview" id="photoPreview">
                                    <img id="photoImg" alt="Preview"
                                        src="../uploads/<?= htmlspecialchars($item['image']) ?>"
                                        style="display:block;">
                                </div>

                                <div class="item-details-right">

                                    <span class="category-badge category-<?= strtolower($item['category']) ?>">
                                        <?= htmlspecialchars($item['category']) ?>
                                    </span>

                                    <?php if ($item['category'] === 'Cash'): ?>
                                        <p>
                                            Amount:
                                            ₱<?= number_format($item['cash_amount'], 2) ?>
                                        </p>

                                    <?php elseif ($item['category'] === 'Gadget'): ?>

                                        <div class="gadget-details-grid">
                                            <div class="gadget-col">
                                                <p>
                                                    Type:
                                                    <?= htmlspecialchars($item['gadget_type'] ?? '-') ?>
                                                </p>

                                                <p>
                                                    Brand:
                                                    <?= htmlspecialchars($item['gadget_brand'] ?? '-') ?>
                                                </p>
                                            </div>

                                            <div class="gadget-col">
                                                <p>
                                                    Color:
                                                    <?= htmlspecialchars($item['gadget_color'] ?? '-') ?>
                                                </p>

                                                <p>
                                                    Features:
                                                    <?= htmlspecialchars($item['gadget_features'] ?? '-') ?>
                                                </p>
                                            </div>
                                        </div>

                                    <?php elseif ($item['category'] === 'Document'): ?>
                                        <p>
                                            Document Type:
                                            <?= htmlspecialchars($item['document_type'] ?? '-') ?>
                                        </p>

                                        <p>
                                            Name:
                                            <?= htmlspecialchars($item['document_name'] ?? '-') ?>
                                        </p>

                                    <?php elseif ($item['category'] === 'Other'): ?>
                                        <p>
                                            Description:
                                            <?= htmlspecialchars($item['other_description'] ?? '-') ?>
                                        </p>

                                    <?php endif; ?>
                                </div>

                            </div>

                            <hr class="divider-line">

                            <!-- REPORTER INFO (READ-ONLY) -->
                            <div class="col-md-6">
                                <label class="form-label">Reporter Type</label>
                                <div class="form-control-custom"><?= htmlspecialchars($item['reporter_role'] ?? '-') ?></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reporter Name</label>
                                <div class="form-control-custom"><?= htmlspecialchars($item['reporter_name'] ?? '-') ?></div>
                            </div>

                            <?php if (in_array($item['reporter_role'], ['Student', 'Anonymous'])): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Reporter ID</label>
                                    <div class="form-control-custom"><?= htmlspecialchars($item['reporter_id'] ?? '-') ?></div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Year</label>
                                    <div class="form-control-custom"><?= htmlspecialchars($item['reporter_year'] ?? '-') ?></div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <div class="form-control-custom"><?= htmlspecialchars($item['reporter_department'] ?? '-') ?></div>
                                </div>
                            <?php elseif ($item['reporter_role'] === 'Faculty'): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Faculty ID</label>
                                    <div class="form-control-custom"><?= htmlspecialchars($item['reporter_id'] ?? '-') ?></div>
                                </div>
                            <?php endif; ?>

                            <hr class="divider-line">

                            <!-- REPORT INFO (READ-ONLY) -->
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-control-custom"><?= htmlspecialchars($item['status']) ?></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Reported Location</label>
                                <div class="form-control-custom"><?= htmlspecialchars($item['reported_location']) ?></div>
                            </div>

                            <?php if ($item['status'] === 'Lost'): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <div class="form-control-custom"><?= htmlspecialchars($item['email'] ?? '-') ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="form-label">Original Report Date</label>
                                <div class="form-control-custom"><?= date('M d, Y h:i A', strtotime($item['created_at'])) ?></div>
                            </div>

                            <div class="form-actions">
                                <a href="adminLostFoundList.php" class="btn-cancel">Cancel</a>
                                <button type="submit" class="btn-submit"><i class="bi bi-arrow-clockwise"></i> Renew Item</button>
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
                            <span>Once renewed, this item is automatically checked against active reports for a possible match, if today is <b><?= $currentDate ?></b>, the system will only check reports dated <b><?= $startDate ?> to <?= $currentDate ?></b>.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

<!-- MATCHES (live preview base sa kasalukuyang status/category ng item) -->
<script>
async function loadMatches() {

    const status = "<?= addslashes($item['status']) ?>";
    const category = "<?= addslashes($item['category']) ?>";

    const formData = new FormData();
    formData.append('status', status);
    formData.append('category', category);
    formData.append('exclude_id', <?= (int)$item['id'] ?>);

    const res = await fetch('checkMatches.php', {
        method: 'POST',
        body: formData
    });

    const data = await res.json();

    let html = '';

    if (data.length === 0) {
        html = `
         <div class="bg-light" style="font-weight:600; font-size:13.5px; padding:10px 13px; border-radius:10px;"> No matches found</div>
        `;
    }

    data.forEach(item => {

        let details = '';

        if (item.category === 'Cash') {
            details = `Amount: ₱${item.cash_amount}`;
        } else if (item.category === 'Gadget') {
            details = `
            Type: ${item.gadget_type}<br>
            Brand/Model: ${item.gadget_brand} | ${item.gadget_color}`;
        } else if (item.category === 'Document') {
            details = `📄 ${item.document_name}`;
        } else {
            details = `Description: ${item.other_description}`;
        }

        html += `
            <div class="card mb-2 border-success">
                <div class="card-body" style="padding:10px 15px;">
                    <b style=" font-size:14px; color: #4B5A54;">${item.category}</b><br>
                    <span style="font-weight:600; font-size:13.5px; color: #4B5A54;">${details}</span><br>
                    <span style="font-weight:600; font-size:13.5px; color: #4B5A54;">Location: ${item.reported_location}</span>
                </div>
            </div>
        `;
    });

    document.getElementById('matchResults').innerHTML = html;
}

document.addEventListener("DOMContentLoaded", loadMatches);
</script>

<script>
function toggleSidebar(){
    document.getElementById("sidebar")
    .classList.toggle("show");

    document.getElementById("overlay")
    .classList.toggle("show");
}

document.getElementById("overlay")
.addEventListener("click", function(){
    document.getElementById("sidebar")
    .classList.remove("show");

    this.classList.remove("show");
});

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
    setInterval(() => {
        fetch('../middleware/adminAutoLogout.php')
            .then(res => res.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'adminLogin.php';
                }
            })
            .catch(err => console.error(err));
    }, 900000);
</script>

</body>
</html>