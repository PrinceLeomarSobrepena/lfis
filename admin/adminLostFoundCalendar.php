<?php
include '../middleware/adminMiddleware.php';
include '../config/db.php';

$events = [];

$result = $conn->query("SELECT id, category, status, created_at FROM lost_found ORDER BY created_at DESC");

while($row = $result->fetch_assoc()) {

    $date = date('Y-m-d', strtotime($row['created_at']));

    $events[$date][] = [
        'category' => $row['category'],
        'status' => $row['status']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Responsive Calendar View</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

<style>
    * {
    box-sizing: border-box;
    }

    body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #fafafa;/*#f0f2f5*/
    /* background-color: #f4f6f9; */
    }

	.navbar-brand img {
	width: 30px;
	height: 30px;
	}
	.nav-link {
	font-weight: 500;
	}

    .container{
        max-width: 1520px;
        margin: 0 auto;
        /* margin-top: 10px; */
        padding: 20px;
    }

    .calendar-container {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 20px;
    }

    .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background-color: #dcdcdc;
    }

    .calendar-cell {
    background-color: #fff;
    min-height: 80px;
    padding: 6px;
    font-size: 14px;
    color: #333;
    position: relative;
    }

    .calendar-cell.inactive {
    background-color: #f9f9f9;
    color: #bbb;
    }

    .calendar-cell.today {
    background-color: #e0edff;
    font-weight: bold;
    }

    .day-header {
    background-color: #f1f3f7;
    font-weight: 600;
    text-align: center;
    padding: 10px 0;
    }

    @media (max-width: 768px) {
    .calendar-cell {
        min-height: 60px;
        font-size: 12px;
        padding: 4px;
    }

    .day-header {
        font-size: 12px;
        padding: 6px 0;
    }
    }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">

    <div class="container-fluid px-4">

        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="aaa.png" alt="PH Logo" class="me-2">
            <strong>i-Vote OVAU 2025</strong>
        </a>

        <div class="d-flex">

            <a class="nav-link d-flex align-items-center me-3" href="#">
                Login
            </a>

            <a class="nav-link d-flex align-items-center" href="#">
                About
            </a>

        </div>

    </div>

</nav>

<div class="container">

    <div class="calendar-container">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">

            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="prevMonth()">&#x25C0;</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="nextMonth()">&#x25B6;</button>
                <button class="btn btn-outline-primary btn-sm" onclick="goToday()">Today</button>
            </div>

            <h5 class="m-0 fw-normal" id="monthTitle"></h5>

            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm active">Month</button>
            </div>

        </div>

        <div class="calendar-grid" id="calendarGrid">

            <div class="day-header">Sun</div>
            <div class="day-header">Mon</div>
            <div class="day-header">Tue</div>
            <div class="day-header">Wed</div>
            <div class="day-header">Thu</div>
            <div class="day-header">Fri</div>
            <div class="day-header">Sat</div>

        </div>

    </div>

</div>

<script>

const calendarGrid = document.getElementById("calendarGrid");
const monthTitle = document.getElementById("monthTitle");

const events = <?= json_encode($events) ?>;

let currentDate = new Date();

function renderCalendar(date) {

    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    const startDay = firstDay.getDay();
    const totalDays = lastDay.getDate();

    const monthName = date.toLocaleString('default', { month: 'long' });

    monthTitle.textContent = `${monthName} ${year}`;

    const cells = calendarGrid.querySelectorAll(".calendar-cell");

    cells.forEach(cell => cell.remove());

    const prevLastDay = new Date(year, month, 0).getDate();

    const totalCells = 42;

    let dayCounter = 1;
    let nextMonthDay = 1;

    for (let i = 0; i < totalCells; i++) {

        const cell = document.createElement("div");

        cell.className = "calendar-cell";

        if (i < startDay) {

            cell.classList.add("inactive");

            cell.textContent = prevLastDay - startDay + i + 1;

        }

        else if (dayCounter <= totalDays) {

            const today = new Date();

            if (
                dayCounter === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            ) {
                cell.classList.add("today");
            }

            const currentDay = dayCounter;

            cell.innerHTML = `<div>${currentDay}</div>`;

            const fullDate =
                year + '-' +
                String(month + 1).padStart(2, '0') + '-' +
                String(currentDay).padStart(2, '0');

            if (events[fullDate]) {

                events[fullDate].forEach(event => {

                    const badge = document.createElement("div");

                    badge.classList.add("event-badge");

                    if (event.status === 'Lost') {
                        badge.classList.add("lost");
                    } else {
                        badge.classList.add("found");
                    }

                    badge.innerText =
                        event.status + ' - ' + event.category;

                    cell.appendChild(badge);

                });

            }

            dayCounter++;

        }

        else {

            cell.classList.add("inactive");

            cell.textContent = nextMonthDay++;

        }

        calendarGrid.appendChild(cell);

    }

}

function prevMonth() {

    currentDate.setMonth(currentDate.getMonth() - 1);

    renderCalendar(currentDate);

}

function nextMonth() {

    currentDate.setMonth(currentDate.getMonth() + 1);

    renderCalendar(currentDate);

}

function goToday() {

    currentDate = new Date();

    renderCalendar(currentDate);

}

renderCalendar(currentDate);

</script>

</body>
</html>