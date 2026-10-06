<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';
date_default_timezone_set('Asia/Manila');

/*
|--------------------------------------------------------------------------
| MONTH / YEAR NAVIGATION
|--------------------------------------------------------------------------
*/
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// safety clamp
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDayTimestamp = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDayTimestamp);
$startWeekday = (int)date('w', $firstDayTimestamp); // 0 = Sunday
$monthLabel = date('F Y', $firstDayTimestamp);

// prev/next month+year
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$today = date('Y-m-d');
$rangeStart = date('Y-m-01', $firstDayTimestamp);
$rangeEnd   = date('Y-m-t', $firstDayTimestamp);
$startDT = $rangeStart . ' 00:00:00';
$endDT   = $rangeEnd   . ' 23:59:59';

/*
|--------------------------------------------------------------------------
| HELPER: short label ng item base sa category
|--------------------------------------------------------------------------
*/
function shortItemLabel($row){
    switch ($row['category']) {
        case 'Cash':
            return '₱' . number_format((float)$row['cash_amount'], 2) . ' cash';
        case 'Gadget':
            return trim(($row['gadget_brand'] ?? '') . ' ' . ($row['gadget_type'] ?? '')) ?: 'Gadget';
        case 'Document':
            return $row['document_name'] ?: 'Document';
        case 'Other':
            return $row['other_title'] ?: 'Item';
        default:
            return $row['category'];
    }
}

/*
|--------------------------------------------------------------------------
| PULL EVENTS FOR THE MONTH
|--------------------------------------------------------------------------
| Nagbabase tayo sa lost_found table:
| - Lost reported (status = Lost, created_at)
| - Found reported (status = Found, created_at)
| - Claimed (claimed_date)
| - Resolved (resolved_at)
|
| $eventsByDay  -> counts per type, ginagamit ng dots sa calendar grid
| $itemsByDay   -> buong item details, ginagamit pag click yung araw
*/
$eventsByDay = [];
$itemsByDay  = [];

function addEvent(&$arr, $date, $type){
    $day = (int)date('j', strtotime($date));
    if (!isset($arr[$day])) {
        $arr[$day] = ['lost' => 0, 'found' => 0, 'claimed' => 0, 'resolved' => 0];
    }
    $arr[$day][$type]++;
}

function pushItem(&$arr, $day, $item){
    if (!isset($arr[$day])) $arr[$day] = [];
    $arr[$day][] = $item;
}

$selectCols = "id, category, status, reported_location, cash_amount, gadget_type, gadget_brand, document_name, other_title, created_at, claimed_date, resolved_at";

// Lost / Found reports (base sa created_at)
$stmt = $conn->prepare("SELECT $selectCols FROM lost_found WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDT, $endDT);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $type = $row['status'] === 'Lost' ? 'lost' : 'found';
    addEvent($eventsByDay, $row['created_at'], $type);

    $day = (int)date('j', strtotime($row['created_at']));
    pushItem($itemsByDay, $day, [
        'type'     => $type,
        'label'    => ($type === 'lost' ? 'Lost' : 'Found') . ' - ' . shortItemLabel($row),
        'category' => $row['category'],
        'location' => $row['reported_location'],
        'time'     => date('h:i A', strtotime($row['created_at'])),
        'id'       => $row['id'],
    ]);
}
$stmt->close();

// Claimed items
$stmt = $conn->prepare("SELECT $selectCols FROM lost_found WHERE is_claimed = 1 AND claimed_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDT, $endDT);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if (empty($row['claimed_date'])) continue;
    addEvent($eventsByDay, $row['claimed_date'], 'claimed');

    $day = (int)date('j', strtotime($row['claimed_date']));
    pushItem($itemsByDay, $day, [
        'type'     => 'claimed',
        'label'    => 'Claimed - ' . shortItemLabel($row),
        'category' => $row['category'],
        'location' => $row['reported_location'],
        'time'     => date('h:i A', strtotime($row['claimed_date'])),
        'id'       => $row['id'],
    ]);
}
$stmt->close();

// Resolved matches
$stmt = $conn->prepare("SELECT $selectCols FROM lost_found WHERE is_resolved = 1 AND resolved_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDT, $endDT);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if (empty($row['resolved_at'])) continue;
    addEvent($eventsByDay, $row['resolved_at'], 'resolved');

    $day = (int)date('j', strtotime($row['resolved_at']));
    pushItem($itemsByDay, $day, [
        'type'     => 'resolved',
        'label'    => 'Resolved - ' . shortItemLabel($row),
        'category' => $row['category'],
        'location' => $row['reported_location'],
        'time'     => date('h:i A', strtotime($row['resolved_at'])),
        'id'       => $row['id'],
    ]);
}
$stmt->close();

$weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
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
/* ====================== CALENDAR TOOLBAR ===================== */

.cal-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    background: #fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:14px 18px;
    gap:12px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
    margin-bottom:16px;
}

.cal-nav{
    display:flex;
    align-items:center;
    gap:10px;
}

.cal-nav-btn{
    width:34px;
    height:34px;
    border-radius:9px;
    border:1px solid #E7ECE9;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#4B5A54;
    cursor:pointer;
    text-decoration:none;
}

.cal-nav-btn:hover{background: #f5f6fa;}

.cal-month-title{
    font-weight:800;
    font-size:16px;
    color:#0F1B2D;
    min-width:150px;
    text-align:center;
}

.btn-today{
    border:1px solid #E7ECE9;
    background:#fff;
    color:#4B5A54;
    font-weight:700;
    font-size:12.5px;
    padding:8px 16px;
    border-radius:9px;
    text-decoration:none;
}
.btn-today:hover{background:#f5f6fa;}

/* ====================== CALENDAR GRID ===================== */
.calendar-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:18px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.calendar-grid{
    display:grid;
    grid-template-columns:repeat(7, 1fr);
}

.calendar-weekday{
    font-weight:800;
    font-size:11px;
    color: #7C8A85;
    text-transform:uppercase;
    letter-spacing:.4px;
    text-align:center;
    padding:6px 0 10px;
}

.calendar-day{
    min-height:84px;
    border:1px solid #EEF1F0;
    padding:8px;
    display:flex;
    flex-direction:column;
    gap:6px;
    cursor:pointer;
    transition:background .15s ease;

    margin-top:-1px;
    margin-left:-1px;
}

.calendar-day:hover{
    background:#FAFEFC;
}

.calendar-day.empty{
    background:transparent;
    border:none;
    cursor:default;
}

.calendar-day.empty:hover{
    background:transparent;
}

/* .calendar-day.today{
    border-color: #198754;
    background: #F3FBF7;
} */

.calendar-day.selected{
    box-shadow: inset 0 0 0 2px #198754;
    background:#F3FBF7;
}

.calendar-day-num{
    font-weight:800;
    font-size:12.5px;
    color: #0F1B2D;
    border-radius:50%;
    margin-bottom:6px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:24px;
    height:24px;
}

.calendar-day.today .calendar-day-num{
    background: #198754;
    color: #fff;
}

.calendar-dots{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
}

.cal-dot{
    width:8px;
    height:8px;
    border-radius:50%;
    flex-shrink:0;
}

.dot-lost{background:#E1596B;}
.dot-found{background:#198754;}
.dot-claimed{background:#3366CC;}
.dot-resolved{background:#B8860B;}


/* grid */
.calendar-weekdays{
    display:grid;
    grid-template-columns:repeat(7,1fr);
    /* background:#FAFBFA; */
    /* border-bottom:1px solid #E7ECE9; */
    margin:-18px -18px 0 -18px; /* i-offset yung padding ng .calendar-card */
    padding:0 18px;
}

.calendar-day{
    min-height:100px;
    align-items:flex-start;
}

.calendar-event{
    font-size:10.5px;
    font-weight:700;
    padding:3px 7px;
    border-radius:6px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    display:flex;
    align-items:center;
    gap:5px;
    width:100%;
}

.calendar-event .dot{
    width:6px;
    height:6px;
    border-radius:50%;
    flex-shrink:0;
}

.calendar-event.ev-lost{background:#FCEAED;color:#E1596B;}
.calendar-event.ev-lost .dot{background:#E1596B;}

.calendar-event.ev-found{background:#E8F7EF;color:#198754;}
.calendar-event.ev-found .dot{background:#198754;}

.calendar-event.ev-claimed{background:#EAF1FF;color:#3366CC;}
.calendar-event.ev-claimed .dot{background:#3366CC;}

.calendar-event.ev-resolved{background:#FFF4E5;color:#B8860B;}
.calendar-event.ev-resolved .dot{background:#B8860B;}

.calendar-more{
    font-size:10px;
    font-weight:700;
    color:#7C8A85;
}


@media(max-width:768px){
    .calendar-day{
        min-height:56px;
        padding:5px;
    }
    .calendar-weekday{
        font-size:9.5px;
    }
}

/* ====================== LEGEND / SIDE PANEL ===================== */
.legend-card{
    background:#fff;
    border:1px solid #E7ECE9;
    border-radius:14px;
    padding:20px;
    box-shadow:0 4px 20px rgba(20,60,40,0.04);
}

.legend-card h6{
    font-weight:800;
    font-size:13.5px;
    color:#0F1B2D;
    /* margin-bottom:14px; */
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 0;
    /* border-bottom:1px solid #F0F2F1; */
}

.legend-item:last-child{
    border-bottom:none;
}

.legend-dot{
    width:8px;
    height:8px;
    border-radius:50%;
    flex-shrink:0;
}

.legend-label{
    font-weight:700;
    font-size:12.5px;
    color: #4B5A54;
}

.legend-desc{
    font-weight:600;
    font-size:11px;
    color: #9AA6A1;
}

.legend-sticky{
    position:sticky;
    top:15px;
}

.legend-summary-tip{
    display:flex;
    align-items:flex-start;
    font-weight:600;
    font-size:11.5px;
    color:#147a49;
    padding:12px 14px;
    background:#E8F7EF;
    border-radius:10px;
    gap:8px;
    line-height:1.5;
    margin-top:12px;
}

.legend-summary-tip i{
    margin-top:1px;
}

/* DAY ITEMS PANEL (lumalabas pag pinindot yung araw) */
.day-items-back{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-weight:700;
    font-size:12px;
    color:#198754;
    cursor:pointer;
    margin-bottom:12px;
}

.day-items-back:hover{
    text-decoration:underline;
}

.day-item-row{
    display:flex;
    gap:10px;
    padding:10px 0;
    border-bottom:1px solid #F0F2F1;
}

.day-item-row:last-child{
    border-bottom:none;
    padding-bottom:0;
}

.day-item-dot{
    width:9px;
    height:9px;
    border-radius:50%;
    margin-top:5px;
    flex-shrink:0;
}

.day-item-info .t{
    font-weight:800;
    font-size:12.5px;
    color:#0F1B2D;
    margin-bottom:2px;
}

.day-item-info .s{
    font-size:11px;
    color:#7C8A85;
    font-weight:600;
}

.day-items-empty{
    text-align:center;
    padding:30px 10px;
    color:#9AA6A1;
    font-weight:600;
    font-size:12.5px;
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
        <a href="adminLostFoundList.php"> <i class="bi bi-journal-text"></i> List </a>
        <a href="adminLostFoundMatches.php"> <i class="bi bi-layers"></i> Match Items </a>
        <a href="adminLostFoundItems.php"> <i class="bi bi-search"></i> Found Items </a>
        <a href="adminLostFoundAuditTrail.php"> <i class="bi bi-clipboard-data"></i> Audit Trail </a>
        <a href="adminLostFoundStatistic.php"><i class="bi bi-graph-up-arrow"></i> Statistic </a>
        <a href="adminLostFoundCalendar.php" class="active"> <i class="bi bi-calendar-event"></i> Calendar </a>
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

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <div class="mt-4 mb-3">
            <div class="page-heading">Calendar</div>
            <div class="page-subheading">Lost/Found reports, claims, and resolved matches in one view.</div>
        </div>

        <!-- CALENDAR TOOLBAR -->
        <div class="cal-toolbar">
            <div class="cal-nav">
                <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="cal-nav-btn"><i class="bi bi-chevron-left"></i></a>
                <div class="cal-month-title"><?= htmlspecialchars($monthLabel) ?></div>
                <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="cal-nav-btn"><i class="bi bi-chevron-right"></i></a>
                <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn-today">Today</a>
            </div>
        </div>

        <div class="row g-3">

            <!-- CALENDAR (LEFT) -->
            <div class="col-lg-9">
                <div class="calendar-card">

                    <!-- WEEKDAY HEADER (hiwalay na sa grid) -->
                    <div class="calendar-weekdays">
                        <?php foreach ($weekdayLabels as $wd): ?>
                            <div class="calendar-weekday"><?= $wd ?></div>
                        <?php endforeach; ?>
                    </div>

                    <div class="calendar-grid">

                        <?php for ($i = 0; $i < $startWeekday; $i++): ?>
                            <div class="calendar-day empty"></div>
                        <?php endfor; ?>

                        <?php for ($day = 1; $day <= $daysInMonth; $day++):
                            $thisDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $isToday = ($thisDate === $today);
                            $dayItems = $itemsByDay[$day] ?? [];
                            $shownItems = array_slice($dayItems, 0, 2);
                            $extraCount = count($dayItems) - count($shownItems);
                        ?>
                            <div class="calendar-day <?= $isToday ? 'today' : '' ?>" data-day="<?= $day ?>" onclick="showDayItems(<?= $day ?>)">
                                <div class="calendar-day-num"><?= $day ?></div>

                                <?php foreach ($shownItems as $ev): ?>
                                    <div class="calendar-event ev-<?= $ev['type'] ?>">
                                        <span class="dot"></span><?= htmlspecialchars($ev['label']) ?>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($extraCount > 0): ?>
                                    <div class="calendar-more">+<?= $extraCount ?> more</div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>

                    </div>
                </div>
            </div>

            <!-- RIGHT PANEL: LEGEND (default) / DAY ITEMS (pag pinindot yung araw) -->
            <div class="col-lg-3">
                <div class="legend-sticky">

                    <!-- LEGEND (default view) -->
                    <div class="legend-card" id="legendPanel">
                        <h6>Legend</h6>

                        <div class="legend-item">
                            <span class="legend-dot dot-lost"></span>
                            <div>
                                <div class="legend-label">Lost Report</div>
                                <div class="legend-desc">Item reported as lost on this day</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot dot-found"></span>
                            <div>
                                <div class="legend-label">Found Report</div>
                                <div class="legend-desc">Item turned in / reported found</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot dot-claimed"></span>
                            <div>
                                <div class="legend-label">Claimed</div>
                                <div class="legend-desc">Item released to its owner</div>
                            </div>
                        </div>

                        <div class="legend-item">
                            <span class="legend-dot dot-resolved"></span>
                            <div>
                                <div class="legend-label">Resolved</div>
                                <div class="legend-desc">Match confirmed and closed</div>
                            </div>
                        </div>

                        <div class="legend-summary-tip">
                            <i class="bi bi-info-circle"></i>
                            <span>Click on any day to see the items reported, claimed, or resolved on that date.</span>
                        </div>
                    </div>

                    <!-- DAY ITEMS (hidden by default) -->
                    <div class="legend-card" id="dayItemsPanel" style="display:none;">
                        <div class="day-items-back" onclick="showLegend()">
                            <i class="bi bi-arrow-left"></i> Back to Legend
                        </div>
                        <h6 id="dayItemsTitle">Items</h6>
                        <div id="dayItemsList"></div>
                    </div>

                </div>
            </div>

        </div>
    </div>


<!-- DAY ITEMS DATA + INTERACTION -->
<script>
const itemsByDay = <?= json_encode($itemsByDay, JSON_UNESCAPED_UNICODE) ?>;
const monthLabel = <?= json_encode($monthLabel) ?>;

const dotColors = {
    lost: '#E1596B',
    found: '#198754',
    claimed: '#3366CC',
    resolved: '#B8860B'
};

const typeLabels = {
    lost: 'Lost report',
    found: 'Found report',
    claimed: 'Claimed',
    resolved: 'Resolved'
};

function showDayItems(day) {
    const items = itemsByDay[day] || [];

    document.getElementById('legendPanel').style.display = 'none';
    document.getElementById('dayItemsPanel').style.display = 'block';
    document.getElementById('dayItemsTitle').innerText = monthLabel.split(' ')[0] + ' ' + day;

    document.querySelectorAll('.calendar-day').forEach(c => c.classList.remove('selected'));
    const cell = document.querySelector('.calendar-day[data-day="' + day + '"]');
    if (cell) cell.classList.add('selected');

    const listEl = document.getElementById('dayItemsList');

    if (items.length === 0) {
        listEl.innerHTML = '<div class="day-items-empty"><i class="bi bi-inbox" style="font-size:22px;color:#C7CFCB;display:block;margin-bottom:6px;"></i>No activity on this day.</div>';
        return;
    }

    let html = '';
    items.forEach(item => {
        html += `
            <div class="day-item-row">
                <span class="day-item-dot" style="background:${dotColors[item.type]}"></span>
                <div class="day-item-info">
                    <div class="t">${item.label}</div>
                    <div class="s">${typeLabels[item.type]} • ${item.location ?? '-'} • ${item.time}</div>
                </div>
            </div>
        `;
    });

    listEl.innerHTML = html;
}

function showLegend() {
    document.getElementById('dayItemsPanel').style.display = 'none';
    document.getElementById('legendPanel').style.display = 'block';
    document.querySelectorAll('.calendar-day').forEach(c => c.classList.remove('selected'));
}
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