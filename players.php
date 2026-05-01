<?php
session_start();

$ADMIN_USER = "admin";
$ADMIN_PASS = "admin123";

// Handle login
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] == $ADMIN_USER && $_POST['password'] == $ADMIN_PASS) {
        $_SESSION['players_logged_in'] = true;
    } else {
        $login_error = "Invalid username or password!";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: players.php");
    exit();
}

// If not logged in, show login form
if (!isset($_SESSION['players_logged_in']) || $_SESSION['players_logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Players Login — Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
body{background:linear-gradient(135deg,#1e3c72,#2a5298);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-wrap{width:100%;max-width:400px;padding:20px;}
.login-card{background:white;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
.login-head{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:30px;text-align:center;}
.login-head h2{margin:0 0 6px;font-size:1.5rem;}
.login-head p{opacity:.85;font-size:.88rem;margin:0;}
.login-body{padding:30px;}
.login-icon{font-size:2.5rem;margin-bottom:10px;}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-head">
      <div class="login-icon">&#x1F512;</div>
      <h2>Admin Access Required</h2>
      <p>Please login to view player records</p>
    </div>
    <div class="login-body">
      <?php if (isset($login_error)): ?>
      <div class="alert alert-error">&#x274C; <?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>
      <form method="POST" novalidate>
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" placeholder="Enter username" required autocomplete="username">
        </div>
        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
          &#x1F511; Login
        </button>
      </form>
      <div style="text-align:center;margin-top:18px;">
        <a href="home.html" style="color:var(--blue2);font-size:.88rem;">&#x2190; Back to Home</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<?php
    exit();
}

// ── Logged in — connect DB ────────────────────────────────────────
$conn = new mysqli("localhost", "root", "", "sports_academy");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ── Filters ───────────────────────────────────────────────────────
$filter_sport  = isset($_GET['sport'])  ? $conn->real_escape_string($_GET['sport'])  : '';
$filter_gender = isset($_GET['gender']) ? $conn->real_escape_string($_GET['gender']) : '';
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$search        = isset($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';

$where = ["1=1"];
if ($filter_sport)  $where[] = "EXISTS (SELECT 1 FROM player_sports ps2 JOIN sports s2 ON ps2.sport_id=s2.id WHERE ps2.player_id=p.id AND s2.sport_name='$filter_sport')";
if ($filter_gender) $where[] = "p.gender='$filter_gender'";
if ($filter_status === 'Paid')   $where[] = "p.fee_status='Paid'";
if ($filter_status === 'Unpaid') $where[] = "(p.fee_status='Unpaid' OR p.fee_status IS NULL)";
if ($search) $where[] = "(p.full_name LIKE '%$search%' OR p.email LIKE '%$search%' OR p.phone LIKE '%$search%')";
$where_sql = implode(" AND ", $where);

// ── Stats ─────────────────────────────────────────────────────────
$total_res   = $conn->query("SELECT COUNT(*) as c FROM players");
$total       = $total_res->fetch_assoc()['c'];
$paid_res    = $conn->query("SELECT COUNT(*) as c FROM players WHERE fee_status='Paid'");
$paid        = $paid_res->fetch_assoc()['c'];
$unpaid      = $total - $paid;
$sports_res  = $conn->query("SELECT COUNT(DISTINCT sport_id) as c FROM player_sports");
$sport_count = $sports_res->fetch_assoc()['c'];

// ── Players data ──────────────────────────────────────────────────
$players = $conn->query("
    SELECT p.*,
           GROUP_CONCAT(s.sport_name ORDER BY s.sport_name SEPARATOR ', ') as sports_list
    FROM players p
    LEFT JOIN player_sports ps ON p.id = ps.player_id
    LEFT JOIN sports s ON ps.sport_id = s.id
    WHERE $where_sql
    GROUP BY p.id
    ORDER BY p.registration_date DESC
");

// ── Sport list for filter ─────────────────────────────────────────
$sp_res = $conn->query("SELECT sport_name FROM sports ORDER BY sport_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registered Players — Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
.search-bar{display:flex;gap:10px;align-items:center;flex:1;min-width:220px;}
.search-bar input{flex:1;padding:9px 14px;border:1.5px solid var(--border);border-radius:9px;font-size:.9rem;outline:none;}
.search-bar input:focus{border-color:var(--blue2);}
.search-bar button{padding:9px 18px;background:var(--blue);color:white;border:none;border-radius:9px;cursor:pointer;font-weight:600;}
.player-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.88rem;flex-shrink:0;}
.player-name-cell{display:flex;align-items:center;gap:10px;}
.export-btn{margin-left:auto;}
.no-results{text-align:center;padding:50px;color:var(--muted);}
.no-results .icon{font-size:3rem;margin-bottom:10px;}
tr.highlight td{background:#fff8e1;}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="page-hero">
  <h1>&#x1F3C5; Registered Players</h1>
  <p>View and manage all registered players</p>
</div>

<div class="container">

  <!-- STATS -->
  <div class="stats-row cols-4">
    <div class="stat-box">
      <div class="s-num"><?= $total ?></div>
      <div class="s-lbl">Total Players</div>
    </div>
    <div class="stat-box green">
      <div class="s-num"><?= $paid ?></div>
      <div class="s-lbl">Fee Paid</div>
    </div>
    <div class="stat-box red">
      <div class="s-num"><?= $unpaid ?></div>
      <div class="s-lbl">Fee Pending</div>
    </div>
    <div class="stat-box gold">
      <div class="s-num"><?= $sport_count ?></div>
      <div class="s-lbl">Sports Enrolled</div>
    </div>
  </div>

  <!-- FILTER & SEARCH -->
  <div class="card">
    <form method="GET">
      <div class="filter-bar" style="margin-bottom:0;">

        <!-- Search -->
        <div class="search-bar">
          <input type="text" name="search" placeholder="&#x1F50D; Search name, email, phone..."
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit">Search</button>
        </div>

        <!-- Sport filter -->
        <div class="form-group">
          <label class="form-label">Sport</label>
          <select name="sport" class="form-control" style="min-width:140px;">
            <option value="">All Sports</option>
            <?php while($s=$sp_res->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($s['sport_name']) ?>"
                    <?= $filter_sport==$s['sport_name']?'selected':'' ?>>
              <?= htmlspecialchars($s['sport_name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Gender filter -->
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control" style="min-width:120px;">
            <option value="">All</option>
            <option value="Male"   <?= $filter_gender=='Male'  ?'selected':'' ?>>Male</option>
            <option value="Female" <?= $filter_gender=='Female'?'selected':'' ?>>Female</option>
            <option value="Other"  <?= $filter_gender=='Other' ?'selected':'' ?>>Other</option>
          </select>
        </div>

        <!-- Fee Status filter -->
        <div class="form-group">
          <label class="form-label">Fee Status</label>
          <select name="status" class="form-control" style="min-width:130px;">
            <option value="">All</option>
            <option value="Paid"   <?= $filter_status=='Paid'  ?'selected':'' ?>>Paid</option>
            <option value="Unpaid" <?= $filter_status=='Unpaid'?'selected':'' ?>>Unpaid</option>
          </select>
        </div>

        <div style="display:flex;gap:8px;align-items:flex-end;">
          <button type="submit" class="btn btn-primary">&#x1F50D; Filter</button>
          <a href="players.php" class="btn btn-outline">Reset</a>
          <a href="players.php?logout=true" class="btn btn-danger">&#x1F512; Logout</a>
        </div>

      </div>
    </form>
  </div>

  <!-- PLAYERS TABLE -->
  <div class="card">
    <div class="card-title">
      &#x1F4CB; Players List
      <span style="font-size:.8rem;color:var(--muted);font-weight:400;margin-left:8px;">
        Showing <?= $players ? $players->num_rows : 0 ?> of <?= $total ?> players
      </span>
    </div>

    <?php if ($players && $players->num_rows > 0): ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Player</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Sport</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Fee Status</th>
            <th>Amount Paid</th>
            <th>Registered On</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row=$players->fetch_assoc()):
          $status = $row['fee_status'] ?? 'Unpaid';
          $initials = strtoupper(substr($row['full_name'],0,1));
        ?>
          <tr>
            <td><?= $i++ ?></td>
            <td>
              <div class="player-name-cell">
                <div class="player-avatar"><?= $initials ?></div>
                <div>
                  <div style="font-weight:700;"><?= htmlspecialchars($row['full_name']) ?></div>
                  <div style="font-size:.75rem;color:var(--muted);">ID: #<?= $row['id'] ?></div>
                </div>
              </div>
            </td>
            <td><?= $row['age'] ?></td>
            <td><?= htmlspecialchars($row['gender']) ?></td>
            <td>
              <span><?= htmlspecialchars($row["sports_list"] ?? "—") ?></span>
            </td>
            <td>&#x1F4DE; <?= htmlspecialchars($row['phone']) ?></td>
            <td style="font-size:.82rem;">&#x1F4E7; <?= htmlspecialchars($row['email']) ?></td>
            <td>
              <?php if($status === 'Paid'): ?>
                <span class="badge badge-success">&#x2714; Paid</span>
              <?php else: ?>
                <span class="badge badge-danger">&#x23F3; Unpaid</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if($row['amount_paid'] > 0): ?>
                <strong>&#8377;<?= number_format($row['amount_paid'],2) ?></strong>
              <?php else: ?>
                <span style="color:var(--muted);">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:var(--muted);">
              <?= date('d M Y', strtotime($row['registration_date'])) ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="no-results">
      <div class="icon">&#x1F50D;</div>
      <h3>No Players Found</h3>
      <p>Try changing your search or filter criteria.</p>
      <br><a href="players.php" class="btn btn-outline">Clear Filters</a>
    </div>
    <?php endif; ?>
  </div>

</div>

<footer class="site-footer">
  <p>&copy; 2025 <strong>Sports Academy</strong> — All Rights Reserved</p>
</footer>

<?php $conn->close(); ?>
</body>
</html>
