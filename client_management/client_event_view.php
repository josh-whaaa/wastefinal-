<?php
include '../includes/header.php';
include '../includes/conn.php';

// Fetch only upcoming events (not maintenance)
$events = [];
$now = date('Y-m-d H:i:s');
$sql = "SELECT schedule_id, event_name, day, time, status FROM schedule_table WHERE status = 'Scheduled' AND CONCAT(day, ' ', time) >= ? ORDER BY day, time";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $now);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $time = strlen($row['time']) === 5 ? $row['time'] . ':00' : $row['time'];
    $startDateTime = $row['day'] . 'T' . $time;
    $events[] = [
        'id'    => $row['schedule_id'],
        'title' => $row['event_name'],
        'start' => $startDateTime,
        'color' => '#49755c',
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upcoming Event Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
    <link href="../assets/css/material-dashboard.css?v=3.2.0" rel="stylesheet">
    <style>
        body { background: #f6f8fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .calendar-box { background: #fff; border-radius: 20px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); border: 1px solid #e3e6ea; margin: 24px auto; max-width: 900px; }
        #calendar { background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(102,192,94,0.06); padding: 18px; border: 1px solid #e3e6ea; min-height: 600px; }
        .fc-toolbar-title { color: #49755c; font-weight: 600; }
        .fc-event { border-radius: 10px; background: #eaf7ee !important; color: #49755c !important; font-weight: 500; }
        .fc-event:hover { background: #d4f5e9 !important; color: #2d4c3c !important; }
        .event-table th, .event-table td { vertical-align: middle !important; }
        .event-table tbody tr { transition: background 0.2s; cursor: pointer; }
        .event-table tbody tr:hover { background: #f2f7f6; }
    </style>
</head>
<body>
<?php include '../sidebar/client_sidebar.php'; ?>
<main class="main-content position-relative h-100 border-radius-lg">
    <?php include '../includes/navbar.php'; ?>
    <div class="card-body px-4 pt-4">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-lg border-0">
                        <div class="card-header p-0 position-relative mt-n4 mx-4 z-index-2 border-0">
                            <div style="background: linear-gradient(60deg, #66c05eff, #49755cff);" class="shadow-dark border-radius-lg pt-4 pb-3"> 
                                <h5 class="text-white text-center text-uppercase font-weight-bold mb-0">Upcoming Event Schedule</h5>
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2">
                            <div class="table-responsive p-0">
                                <table class="table event-table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 ps-3">Event Name</th>
                                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 ps-3">Date</th>
                                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 ps-3">Time</th>
                                            <th class="text-uppercase text-secondary text-xs font-weight-bolder opacity-7 ps-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $ev):
                                            $dateTime = explode('T', $ev['start']);
                                            $date = $dateTime[0];
                                            $time = isset($dateTime[1]) ? substr($dateTime[1], 0, 5) : '';
                                        ?>
                                        <tr class="event-row" data-event="<?= htmlspecialchars($ev['title']) ?>" data-date="<?= htmlspecialchars($date) ?>" data-time="<?= htmlspecialchars($time) ?>" data-status="Scheduled">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="../assets/img/logo.png" alt="event" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;box-shadow:0 2px 8px rgba(102,192,94,0.10);">
                                                    <span><?= htmlspecialchars($ev['title']) ?></span>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($date) ?></td>
                                            <td><?= htmlspecialchars($time) ?></td>
                                            <td><span class="badge bg-success">Scheduled</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// document.addEventListener('DOMContentLoaded', function () {
//     // Event hover popover for table
//     document.querySelectorAll('.event-row').forEach(function(row) {
//         row.addEventListener('mouseenter', function() {
//             const eventName = this.dataset.event;
//             const eventDate = this.dataset.date;
//             const eventTime = this.dataset.time;
//             const eventStatus = this.dataset.status;
//             const popover = document.createElement('div');
//             popover.className = 'popover bs-popover-top show';
//             popover.style.position = 'absolute';
//             popover.style.zIndex = '9999';
//             popover.style.background = '#fff';
//             popover.style.border = '1px solid #e3e6ea';
//             popover.style.borderRadius = '8px';
//             popover.style.boxShadow = '0 2px 8px rgba(102,192,94,0.10)';
//             popover.style.padding = '12px 18px';
//             popover.innerHTML = `<strong>${eventName}</strong><br>Date: ${eventDate}<br>Time: ${eventTime}<br>Status: ${eventStatus}`;
//             document.body.appendChild(popover);
//             const rect = this.getBoundingClientRect();
//             popover.style.top = (rect.top + window.scrollY - popover.offsetHeight - 10) + 'px';
//             popover.style.left = (rect.left + window.scrollX + rect.width/2 - popover.offsetWidth/2) + 'px';
//             this._popover = popover;
//         });
//         row.addEventListener('mouseleave', function() {
//             if (this._popover) {
//                 this._popover.remove();
//                 this._popover = null;
//             }
//         });
//     });
// });
</script>
</body>
</html>
