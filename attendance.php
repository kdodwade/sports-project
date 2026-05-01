<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "sports_academy");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$message = '';
$messageType = '';

// Mark attendance — now needs sport_id too
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $player_id = intval($_POST['player_id']);
    $sport_id  = intval($_POST['sport_id']);
    $date      = $conn->real_escape_string($_POST['date']);
    $status    = $conn->real_escape_string($_POST['status']);

    $check = $conn->query("SELECT id FROM attendance WHERE player_id=$player_id AND sport_id=$sport_id AND date='$date'");
    if ($check->num_rows > 0) {
        $conn->query("UPDATE attendance SET status='$status' WHERE player_id=$player_id AND sport_id=$sport_id AND date='$date'");
        $message = "Attendance updated successfully!";
    } else {
        $conn->query("INSERT INTO attendance (player_id, sport_id, date, status) VALUES ($player_id, $sport_id, '$date', '$status')");
        $message = "Attendance marked successfully!";
    }
    $messageType = 'success';
}

// Filter
$filter_sport  = isset($_GET['sport'])  ? $conn->real_escape_string($_GET['sport'])  : '';
$filter_date   = isset($_GET['date'])   ? $conn->real_escape_string($_GET['date'])   : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Players list for form — with their enrolled sports
$players_res = $conn->query("
    SELECT p.id, p.full_name, s.id as sport_id, s.sport_name
    FROM players p
    JOIN player_sports ps ON p.id = ps.player_id
    JOIN sports s ON ps.sport_id = s.id
    ORDER BY p.full_name, s.sport_name
");

// Attendance records with filters
$where = ["a.date='$filter_date'"];
if ($filter_sport)  $where[] = "s.sport_name='$filter_sport'";
if ($filter_status) $where[] = "a.status='$filter_status'";
$where_sql = implode(" AND ", $where);

$records_res = $conn->query("
    SELECT a.*, p.full_name, s.sport_name as sport
    FROM attendance a
    JOIN players p ON a.player_id = p.id
    JOIN sports s  ON a.sport_id  = s.id
    WHERE $where_sql
    ORDER BY p.full_name
");

// Summary stats for today
$total_res   = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE date='$filter_date'");
$present_res = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE date='$filter_date' AND status='Present'");
$absent_res  = $conn->query("SELECT COUNT(*) as cnt FROM attendance WHERE date='$filter_date' AND status='Absent'");

$total_count   = $total_res   ? $total_res->fetch_assoc()['cnt']   : 0;
$present_count = $present_res ? $present_res->fetch_assoc()['cnt'] : 0;
$absent_count  = $absent_res  ? $absent_res->fetch_assoc()['cnt']  : 0;

// Sports list for filter
$sports_res = $conn->query("SELECT sport_name FROM sports ORDER BY sport_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance - Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #333; }

  /* HEADER */
  .header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white; padding: 18px 30px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 3px 12px rgba(0,0,0,0.2);
  }
  .header h1 { font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
  .header nav a {
    color: rgba(255,255,255,0.85); text-decoration: none;
    margin-left: 18px; font-size: 0.9rem; transition: color 0.2s;
  }
  .header nav a:hover { color: white; }

  .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }

  /* STATS */
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
  .stat-card {
    background: white; border-radius: 12px; padding: 20px 24px;
    text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    border-top: 4px solid;
  }
  .stat-card.total   { border-color: #1e3c72; }
  .stat-card.present { border-color: #27ae60; }
  .stat-card.absent  { border-color: #e74c3c; }
  .stat-card .num  { font-size: 2.2rem; font-weight: 700; }
  .stat-card .label{ font-size: 0.85rem; color: #777; margin-top: 4px; }
  .stat-card.total   .num { color: #1e3c72; }
  .stat-card.present .num { color: #27ae60; }
  .stat-card.absent  .num { color: #e74c3c; }

  /* CARD */
  .card {
    background: white; border-radius: 12px; padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 24px;
  }
  .card h3 { color: #1e3c72; font-size: 1.1rem; margin-bottom: 16px;
             padding-bottom: 10px; border-bottom: 2px solid #eee; }

  /* FORM GRID */
  .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; }
  label { font-size: 0.85rem; font-weight: 600; color: #555; }
  select, input[type=date] {
    padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-size: 0.95rem; outline: none; transition: border 0.2s;
  }
  select:focus, input[type=date]:focus { border-color: #2a5298; }
  .btn-primary {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white; border: none; padding: 11px 28px;
    border-radius: 8px; font-size: 0.95rem; font-weight: 600;
    cursor: pointer; transition: opacity 0.2s; align-self: flex-end;
  }
  .btn-primary:hover { opacity: 0.88; }

  /* FILTER BAR */
  .filter-bar {
    display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
  }
  .filter-bar .form-group { min-width: 150px; }
  .btn-filter {
    background: #2a5298; color: white; border: none;
    padding: 10px 22px; border-radius: 8px; cursor: pointer;
    font-size: 0.9rem; font-weight: 600;
  }
  .btn-filter:hover { background: #1e3c72; }
  .btn-reset {
    background: #eee; color: #555; border: 1px solid #ddd;
    padding: 10px 18px; border-radius: 8px; cursor: pointer;
    font-size: 0.9rem; text-decoration: none; font-weight: 600;
  }
  .btn-reset:hover { background: #ddd; }

  /* TABLE */
  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
  th { background: #1e3c72; color: white; padding: 12px; text-align: left; }
  td { padding: 11px 12px; border-bottom: 1px solid #f0f0f0; }
  tr:hover td { background: #f7f9fc; }

  .badge {
    display: inline-block; padding: 3px 12px; border-radius: 50px;
    font-size: 0.8rem; font-weight: 700;
  }
  .badge-present { background: #d4edda; color: #155724; }
  .badge-absent  { background: #f8d7da; color: #721c24; }
  .badge-leave   { background: #fff3cd; color: #856404; }

  .alert {
    padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;
    font-weight: 600;
  }
  .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #27ae60; }

  .no-data { text-align: center; color: #999; padding: 30px; }

  @media(max-width:600px){
    .stats { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<div class="header">
  <h1>🏅 Sports Academy &nbsp;|&nbsp; Attendance</h1>
  <nav>
    <a href="home.html">Home</a>
    <a href="players.php">Players</a>
    <a href="attendance.php">Attendance</a>
    <a href="feedback.php">Feedback</a>
    <a href="payment.php">Payment</a>
    <a href="admin.php">Admin</a>
  </nav>
</div>

<div class="container">

  <?php if ($message): ?>
  <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card total">
      <div class="num"><?= $total_count ?></div>
      <div class="label">Total Marked (<?= $filter_date ?>)</div>
    </div>
    <div class="stat-card present">
      <div class="num"><?= $present_count ?></div>
      <div class="label">Present</div>
    </div>
    <div class="stat-card absent">
      <div class="num"><?= $absent_count ?></div>
      <div class="label">Absent</div>
    </div>
  </div>

  <!-- Mark Attendance Form -->
  <div class="card">
    <h3>&#x1F4CB; Mark / Update Attendance</h3>
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label>Select Player &amp; Sport</label>
          <select name="player_id" id="att_player" onchange="updateSportId(this)" required>
            <option value="">-- Choose Player / Sport --</option>
            <?php while($p = $players_res->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>"
                      data-sport-id="<?= $p['sport_id'] ?>">
                <?= htmlspecialchars($p['full_name']) ?> — <?= htmlspecialchars($p['sport_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <input type="hidden" name="sport_id" id="att_sport_id" value="0">
        <div class="form-group">
          <label>Date</label>
          <input type="date" name="date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" required>
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
            <option value="Leave">Leave</option>
          </select>
        </div>
        <div class="form-group" style="justify-content:flex-end;">
          <button type="submit" name="mark_attendance" class="btn-primary">Mark Attendance</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Attendance Records -->
  <div class="card">
    <h3>📅 Attendance Records</h3>

    <!-- Filter -->
    <form method="GET" style="margin-bottom:20px;">
      <div class="filter-bar">
        <div class="form-group">
          <label>Date</label>
          <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Sport</label>
          <select name="sport">
            <option value="">All Sports</option>
            <?php while($s = $sports_res->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($s['sport_name']) ?>" <?= $filter_sport==$s['sport_name']?'selected':'' ?>>
                <?= htmlspecialchars($s['sport_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="">All</option>
            <option value="Present" <?= $filter_status=='Present'?'selected':'' ?>>Present</option>
            <option value="Absent"  <?= $filter_status=='Absent' ?'selected':'' ?>>Absent</option>
            <option value="Leave"   <?= $filter_status=='Leave'  ?'selected':'' ?>>Leave</option>
          </select>
        </div>
        <button type="submit" class="btn-filter">🔍 Filter</button>
        <a href="attendance.php" class="btn-reset">Reset</a>
      </div>
    </form>

    <div class="table-wrap">
      <?php if ($records_res && $records_res->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>#</th><th>Player Name</th><th>Sport</th><th>Date</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row=$records_res->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['sport']) ?></td>
            <td><?= $row['date'] ?></td>
            <td>
              <span class="badge badge-<?= strtolower($row['status']) ?>">
                <?= $row['status'] ?>
              </span>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="no-data">📭 No attendance records found for the selected filters.</div>
      <?php endif; ?>
    </div>
  </div>

</div>
<script>
function updateSportId(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('att_sport_id').value = opt.dataset.sportId || 0;
}
</script>
</body>
</html>
<?php $conn->close(); ?>
