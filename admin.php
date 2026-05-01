<?php
session_start();

$ADMIN_USER = "root";
$ADMIN_PASS = "";

if(isset($_POST['username']) && isset($_POST['password'])){
    if($_POST['username']==$ADMIN_USER && $_POST['password']==$ADMIN_PASS){
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = "Invalid username or password!";
    }
}
if(isset($_GET['logout'])){ session_destroy(); header("Location: admin.php"); exit(); }
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
body{background:linear-gradient(135deg,#1e3c72,#2a5298);min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-wrap{width:100%;max-width:400px;padding:20px;}
.login-card{background:white;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
.login-head{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:32px;text-align:center;}
.login-head h2{margin:0 0 6px;font-size:1.5rem;}
.login-body{padding:30px;}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-head">
      <div style="font-size:2.5rem;margin-bottom:10px;">&#x1F512;</div>
      <h2>Admin Login</h2>
      <p style="opacity:.85;font-size:.88rem;margin:0;">Sports Academy Dashboard</p>
    </div>
    <div class="login-body">
      <?php if(isset($login_error)): ?>
      <div class="alert alert-error">&#x274C; <?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" placeholder="Enter username" required>
        </div>
        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">&#x1F511; Login</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
<?php exit(); }

// ── DB ────────────────────────────────────────────────────────────
$conn = new mysqli("localhost","root","","sports_academy");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$msg = ''; $msgType = '';

// ════════════════════════════════════════════════════════════════
//  SPORT ACTIONS
// ════════════════════════════════════════════════════════════════

// Add Sport
$sport_errors = []; $sport_success = '';
if(isset($_POST['add_sport'])){
    $sport_name = trim($conn->real_escape_string($_POST['sport_name']));
    $fees       = trim($_POST['fees']);
    $timings    = trim($conn->real_escape_string($_POST['timings']));
    $allowed    = ['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'];

    if(!in_array($sport_name,$allowed))           $sport_errors[]="Please select a valid sport.";
    if(!is_numeric($fees)||floatval($fees)<=0||floatval($fees)>100000) $sport_errors[]="Fees must be between ₹1 and ₹1,00,000.";
    if(strlen($timings)<3||strlen($timings)>100)  $sport_errors[]="Timings must be 3–100 characters.";

    if(empty($sport_errors)){
        $check=$conn->query("SELECT id FROM sports WHERE sport_name='$sport_name'");
        if($check->num_rows>0) $sport_errors[]="This sport already exists.";
    }
    if(empty($sport_errors)){
        $f=floatval($fees);
        $conn->query("INSERT INTO sports (sport_name,fees,timings) VALUES ('$sport_name','$f','$timings')");
        $sport_success="Sport '$sport_name' added successfully!";
    }
}

// Edit Sport
if(isset($_POST['edit_sport'])){
    $id      = intval($_POST['sport_id']);
    $fees    = floatval($_POST['fees']);
    $timings = $conn->real_escape_string(trim($_POST['timings']));
    $errors  = [];
    if($fees<=0||$fees>100000)    $errors[]="Fees must be between ₹1 and ₹1,00,000.";
    if(strlen($timings)<3)         $errors[]="Timings too short.";
    if(empty($errors)){
        $conn->query("UPDATE sports SET fees='$fees',timings='$timings' WHERE id=$id");
        $msg="Sport updated successfully!"; $msgType='success';
    } else {
        $msg=implode(' ',$errors); $msgType='error';
    }
    header("Location: admin.php?tab=sports&msg=".urlencode($msg)."&type=$msgType"); exit();
}

// Delete Sport
if(isset($_GET['delete_sport'])){
    $id=intval($_GET['delete_sport']);
    $conn->query("DELETE FROM sports WHERE id=$id");
    header("Location: admin.php?tab=sports&msg=Sport+deleted.&type=success"); exit();
}

// ════════════════════════════════════════════════════════════════
//  COACH ACTIONS
// ════════════════════════════════════════════════════════════════

// Add Coach
$coach_errors=[]; $coach_success='';
if(isset($_POST['add_coach'])){
    $coach_name = trim($conn->real_escape_string($_POST['coach_name']));
    $sport      = trim($conn->real_escape_string($_POST['sport']));
    $experience = trim($conn->real_escape_string($_POST['experience']));
    $photo      = trim($conn->real_escape_string($_POST['photo']));
    $allowed    = ['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'];

    if(!preg_match("/^[A-Za-z\s]{3,100}$/",$coach_name)) $coach_errors[]="Coach name must be 3+ letters only.";
    if(!in_array($sport,$allowed))                         $coach_errors[]="Please select a valid sport.";
    if(strlen($experience)<2||!preg_match("/\d/",$experience)) $coach_errors[]="Experience must include a number (e.g. 5 years).";
    if(!empty($photo)&&!filter_var($photo,FILTER_VALIDATE_URL)&&!preg_match('/^images\//',$photo))
        $coach_errors[]="Photo must be a valid URL or images/coaches/... path.";

    if(empty($coach_errors)){
        $check=$conn->query("SELECT id FROM coaches WHERE coach_name='$coach_name' AND sport='$sport'");
        if($check->num_rows>0) $coach_errors[]="This coach is already assigned to $sport.";
    }
    if(empty($coach_errors)){
        $conn->query("INSERT INTO coaches (coach_name,sport,experience,photo) VALUES ('$coach_name','$sport','$experience','$photo')");
        $coach_success="Coach '$coach_name' added successfully!";
    }
}

// Edit Coach
if(isset($_POST['edit_coach'])){
    $id         = intval($_POST['coach_id']);
    $coach_name = $conn->real_escape_string(trim($_POST['coach_name']));
    $sport      = $conn->real_escape_string(trim($_POST['sport']));
    $experience = $conn->real_escape_string(trim($_POST['experience']));
    $photo      = $conn->real_escape_string(trim($_POST['photo']));
    $errors     = [];
    $allowed    = ['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'];

    if(!preg_match("/^[A-Za-z\s]{3,100}$/",$coach_name)) $errors[]="Coach name must be 3+ letters only.";
    if(!in_array($sport,$allowed))                         $errors[]="Please select a valid sport.";
    if(strlen($experience)<2||!preg_match("/\d/",$experience)) $errors[]="Experience must include a number.";

    if(empty($errors)){
        $conn->query("UPDATE coaches SET coach_name='$coach_name',sport='$sport',experience='$experience',photo='$photo' WHERE id=$id");
        $msg="Coach updated successfully!"; $msgType='success';
    } else {
        $msg=implode(' ',$errors); $msgType='error';
    }
    header("Location: admin.php?tab=coaches&msg=".urlencode($msg)."&type=$msgType"); exit();
}

// Delete Coach
if(isset($_GET['delete_coach'])){
    $id=intval($_GET['delete_coach']);
    $conn->query("DELETE FROM coaches WHERE id=$id");
    header("Location: admin.php?tab=coaches&msg=Coach+deleted.&type=success"); exit();
}

// ════════════════════════════════════════════════════════════════
//  PLAYER ACTIONS
// ════════════════════════════════════════════════════════════════

// Edit Player
if(isset($_POST['edit_player'])){
    $id      = intval($_POST['player_id']);
    $name    = $conn->real_escape_string(trim($_POST['full_name']));
    $age     = intval($_POST['age']);
    $gender  = $conn->real_escape_string($_POST['gender']);
    $phone   = $conn->real_escape_string(trim($_POST['phone']));
    $email   = $conn->real_escape_string(trim($_POST['email']));
    $sports  = $_POST['sports'] ?? [];
    $errors  = [];

    if(!preg_match("/^[A-Za-z\s]{3,}$/",$name))         $errors[]="Name must be 3+ letters only.";
    if($age<5||$age>60)                                  $errors[]="Age must be between 5 and 60.";
    if(!in_array($gender,['Male','Female','Other']))      $errors[]="Invalid gender.";
    if(empty($sports))                                   $errors[]="Please select at least one sport.";
    if(!preg_match('/^[6-9][0-9]{9}$/',$phone))         $errors[]="Phone must be 10 digits starting with 6-9.";
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))        $errors[]="Invalid email address.";

    // Check duplicate email (exclude current player)
    if(empty($errors)){
        $check=$conn->query("SELECT id FROM players WHERE email='$email' AND id!=$id");
        if($check->num_rows>0) $errors[]="This email is already registered to another player.";
    }

    if(empty($errors)){
        $conn->query("UPDATE players SET full_name='$name',age=$age,gender='$gender',phone='$phone',email='$email' WHERE id=$id");
        // Update player_sports: delete old, insert new
        $conn->query("DELETE FROM player_sports WHERE player_id=$id");
        $allowed=['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'];
        foreach($sports as $sn){
            if(!in_array($sn,$allowed)) continue;
            $sn_esc=$conn->real_escape_string($sn);
            $sr=$conn->query("SELECT id FROM sports WHERE sport_name='$sn_esc' LIMIT 1");
            if($sr&&$sr->num_rows>0){
                $sid=$sr->fetch_assoc()['id'];
                $conn->query("INSERT IGNORE INTO player_sports (player_id,sport_id) VALUES ($id,$sid)");
            }
        }
        $msg="Player updated successfully!"; $msgType='success';
    } else {
        $msg=implode(' | ',$errors); $msgType='error';
    }
    header("Location: admin.php?tab=players&msg=".urlencode($msg)."&type=$msgType"); exit();
}

// Delete Player
if(isset($_GET['delete_player'])){
    $id=intval($_GET['delete_player']);
    $conn->query("DELETE FROM players WHERE id=$id");
    header("Location: admin.php?tab=players&msg=Player+deleted.&type=success"); exit();
}

// Fee Status Update
if(isset($_GET['update_fee'])&&isset($_GET['id'])){
    $id=$_GET['id']; $status=$_GET['update_fee']=='paid'?'Paid':'Unpaid';
    $conn->query("UPDATE players SET fee_status='$status' WHERE id=$id");
    header("Location: admin.php?tab=players"); exit();
}

// ── Read flash message from redirect ─────────────────────────────
if(isset($_GET['msg'])){ $msg=htmlspecialchars($_GET['msg']); $msgType=$_GET['type']??'success'; }

// ── Active tab ────────────────────────────────────────────────────
$activeTab = $_GET['tab'] ?? 'dashboard';

// ── Fetch all data ────────────────────────────────────────────────
$sports_data  = $conn->query("SELECT * FROM sports ORDER BY sport_name");
$coaches_data = $conn->query("SELECT * FROM coaches ORDER BY coach_name");
$players_data = $conn->query("
    SELECT p.*,
           GROUP_CONCAT(s.sport_name ORDER BY s.sport_name SEPARATOR ', ') as sports_list,
           GROUP_CONCAT(s.id ORDER BY s.sport_name SEPARATOR ',') as sport_ids
    FROM players p
    LEFT JOIN player_sports ps ON p.id = ps.player_id
    LEFT JOIN sports s ON ps.sport_id = s.id
    GROUP BY p.id
    ORDER BY p.full_name
");

// ── Dashboard stats ───────────────────────────────────────────────
$total_players  = $conn->query("SELECT COUNT(*) as c FROM players")->fetch_assoc()['c'];
$paid_players   = $conn->query("SELECT COUNT(*) as c FROM players WHERE fee_status='Paid'")->fetch_assoc()['c'];
$unpaid_players = $total_players - $paid_players;
$total_coaches  = $conn->query("SELECT COUNT(*) as c FROM coaches")->fetch_assoc()['c'];
$total_sports   = $conn->query("SELECT COUNT(*) as c FROM sports")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM payments")->fetch_assoc()['t'];
$month_revenue  = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE MONTH(payment_date)=MONTH(NOW()) AND YEAR(payment_date)=YEAR(NOW())")->fetch_assoc()['t'];
$total_feedback = $conn->query("SELECT COUNT(*) as c FROM feedback")->fetch_assoc()['c'];
$avg_rating_res = $conn->query("SELECT COALESCE(ROUND(AVG(rating),1),0) as r FROM feedback");
$avg_rating     = $avg_rating_res->fetch_assoc()['r'];

// Sport-wise player counts (for pie chart)
$sport_counts_res = $conn->query("
    SELECT s.sport_name as sport, COUNT(ps.player_id) as c
    FROM sports s
    LEFT JOIN player_sports ps ON s.id = ps.sport_id
    GROUP BY s.id, s.sport_name
    ORDER BY c DESC
");
$sport_labels = []; $sport_counts = [];
while($r=$sport_counts_res->fetch_assoc()){ $sport_labels[]=$r['sport']; $sport_counts[]=$r['c']; }

// Monthly revenue for last 6 months (for bar chart)
$monthly_res = $conn->query("
    SELECT DATE_FORMAT(payment_date,'%b %Y') as month,
           DATE_FORMAT(payment_date,'%Y-%m') as ym,
           COALESCE(SUM(amount),0) as total
    FROM payments
    WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym, month ORDER BY ym ASC
");
$rev_labels=[]; $rev_data=[];
while($r=$monthly_res->fetch_assoc()){ $rev_labels[]=$r['month']; $rev_data[]=$r['total']; }
if(empty($rev_labels)){ $rev_labels=['No Data']; $rev_data=[0]; }

// Fee status breakdown
$paid_pct   = $total_players>0 ? round(($paid_players/$total_players)*100) : 0;
$unpaid_pct = 100 - $paid_pct;

// Recent 5 registrations
$recent_players = $conn->query("
    SELECT p.full_name,
           GROUP_CONCAT(s.sport_name ORDER BY s.sport_name SEPARATOR ', ') as sport,
           p.registration_date
    FROM players p
    LEFT JOIN player_sports ps ON p.id = ps.player_id
    LEFT JOIN sports s ON ps.sport_id = s.id
    GROUP BY p.id
    ORDER BY p.registration_date DESC LIMIT 5
");

// Recent 5 payments
$recent_payments = $conn->query("SELECT pl.full_name, py.amount, py.method, py.payment_date
                                  FROM payments py JOIN players pl ON py.player_id=pl.id
                                  ORDER BY py.payment_date DESC LIMIT 5");

// Attendance today
$today = date('Y-m-d');
$att_today = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$today'")->fetch_assoc()['c'];
$att_present= $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='$today' AND status='Present'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
body{background:#f0f4f8;padding:0;}
.admin-wrap{max-width:1200px;margin:28px auto;padding:0 20px;}

/* Tab bar */
.admin-tabs{display:flex;background:white;border-radius:12px;overflow:hidden;box-shadow:var(--shadow);margin-bottom:22px;}
.admin-tab{flex:1;padding:13px 8px;text-align:center;cursor:pointer;border:none;background:none;font-size:.92rem;font-weight:600;color:var(--muted);transition:all .2s;border-right:1px solid var(--border);}
.admin-tab:last-child{border-right:none;}
.admin-tab:hover{background:#f0f4ff;color:var(--blue);}
.admin-tab.active{background:var(--blue);color:white;}
.tab-pane{display:none;} .tab-pane.active{display:block;}

/* Add form card */
.add-card{background:white;border-radius:12px;padding:22px 24px;box-shadow:var(--shadow);margin-bottom:22px;}
.add-card-title{color:var(--blue);font-size:1rem;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--border);display:flex;align-items:center;gap:8px;}
.fg{display:flex;flex-direction:column;gap:5px;}
.fg label{font-size:.8rem;font-weight:700;color:#555;}
.fg input,.fg select{padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:.9rem;outline:none;transition:border .2s;}
.fg input:focus,.fg select:focus{border-color:var(--blue2);}
.fg input.err,.fg select.err{border-color:var(--red);background:#fff8f8;}
.fg input.ok,.fg select.ok{border-color:var(--green);}
.ferr{font-size:.73rem;color:var(--red);display:none;}
.ferr.show{display:block;}
.err-box{background:#fdf0f0;border-left:4px solid var(--red);border-radius:8px;padding:11px 16px;margin-bottom:14px;}
.err-box ul{margin:0;padding-left:16px;color:#721c24;font-size:.85rem;line-height:1.7;}
.ok-box{background:#d4edda;border-left:4px solid var(--green);border-radius:8px;padding:11px 16px;margin-bottom:14px;color:#155724;font-weight:600;font-size:.9rem;}
.form-row{display:grid;gap:14px;margin-bottom:14px;}
.form-row.c2{grid-template-columns:1fr 1fr;}
.form-row.c3{grid-template-columns:1fr 1fr 1fr;}
.form-row.c4{grid-template-columns:1fr 1fr 1fr 1fr;}
@media(max-width:700px){.form-row.c2,.form-row.c3,.form-row.c4{grid-template-columns:1fr;}}

/* Table */
.tbl-card{background:white;border-radius:12px;box-shadow:var(--shadow);overflow:hidden;}
.tbl-head{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);}
.tbl-head h3{color:var(--blue);font-size:1rem;font-weight:700;margin:0;}
.data-table{width:100%;border-collapse:collapse;font-size:.85rem;}
.data-table th{background:var(--blue);color:white;padding:11px 12px;text-align:left;font-weight:600;}
.data-table td{padding:10px 12px;border-bottom:1px solid #f0f0f0;vertical-align:middle;}
.data-table tbody tr:hover td{background:#f7f9ff;}
.action-btns{display:flex;gap:6px;flex-wrap:wrap;}
.btn-edit{background:#2a5298;color:white;border:none;padding:5px 12px;border-radius:6px;font-size:.75rem;font-weight:700;cursor:pointer;}
.btn-edit:hover{background:#1e3c72;}
.btn-del{background:var(--red);color:white;border:none;padding:5px 12px;border-radius:6px;font-size:.75rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-del:hover{background:#c0392b;}
.btn-paid-sm{background:var(--green);color:white;border:none;padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-unpaid-sm{background:#e67e22;color:white;border:none;padding:5px 10px;border-radius:6px;font-size:.72rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700;}
.badge-paid{background:#d4edda;color:#155724;}
.badge-unpaid{background:#f8d7da;color:#721c24;}
.badge-sport{background:#dbeafe;color:#1d4ed8;}
.player-cell{display:flex;align-items:center;gap:9px;}
.p-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;}
.coach-thumb{width:40px;height:40px;border-radius:8px;object-fit:cover;}
.no-data{text-align:center;padding:40px;color:var(--muted);}

/* ── DASHBOARD ── */
.dash-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
@media(max-width:900px){.dash-stats{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.dash-stats{grid-template-columns:1fr;}}
.ds-card{background:white;border-radius:14px;padding:20px 22px;box-shadow:var(--shadow);display:flex;align-items:center;gap:16px;border-left:5px solid;}
.ds-card.blue{border-color:#2a5298;} .ds-card.green{border-color:#27ae60;} .ds-card.red{border-color:#e74c3c;} .ds-card.gold{border-color:#f5a623;} .ds-card.purple{border-color:#8e44ad;} .ds-card.teal{border-color:#16a085;} .ds-card.orange{border-color:#e67e22;} .ds-card.navy{border-color:#1e3c72;}
.ds-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;}
.ds-card.blue .ds-icon{background:#dbeafe;} .ds-card.green .ds-icon{background:#d4edda;} .ds-card.red .ds-icon{background:#f8d7da;} .ds-card.gold .ds-icon{background:#fff3cd;} .ds-card.purple .ds-icon{background:#f0e6ff;} .ds-card.teal .ds-icon{background:#d1f0ea;} .ds-card.orange .ds-icon{background:#fde8d0;} .ds-card.navy .ds-icon{background:#dbeafe;}
.ds-num{font-size:1.7rem;font-weight:800;color:var(--blue);line-height:1;}
.ds-lbl{font-size:.78rem;color:var(--muted);margin-top:3px;}
.ds-sub{font-size:.72rem;color:var(--green);margin-top:2px;font-weight:600;}
.charts-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:800px){.charts-row{grid-template-columns:1fr;}}
.chart-card{background:white;border-radius:14px;padding:20px;box-shadow:var(--shadow);}
.chart-card h4{color:var(--blue);font-size:.95rem;font-weight:700;margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid var(--border);display:flex;align-items:center;gap:8px;}
.chart-wrap{position:relative;height:220px;}
.bottom-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:800px){.bottom-row{grid-template-columns:1fr;}}
.recent-list{list-style:none;padding:0;margin:0;}
.recent-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5;}
.recent-item:last-child{border:none;}
.ri-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.82rem;flex-shrink:0;}
.ri-name{font-weight:700;font-size:.87rem;color:var(--text);}
.ri-sub{font-size:.75rem;color:var(--muted);}
.ri-right{margin-left:auto;text-align:right;}
.progress-bar-wrap{margin-bottom:14px;}
.progress-label{display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:5px;}
.progress-label span:first-child{font-weight:600;color:var(--text);}
.progress-label span:last-child{color:var(--muted);}
.progress-bg{background:#f0f0f0;border-radius:50px;height:10px;overflow:hidden;}
.progress-fill{height:100%;border-radius:50px;transition:width .6s ease;}
.quick-links{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;}
@media(max-width:700px){.quick-links{grid-template-columns:repeat(2,1fr);}}
.ql-card{background:white;border-radius:12px;padding:16px;text-align:center;box-shadow:var(--shadow);text-decoration:none;color:var(--text);transition:all .2s;border:2px solid transparent;}
.ql-card:hover{border-color:var(--blue2);transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.ql-icon{font-size:1.8rem;display:block;margin-bottom:6px;}
.ql-label{font-size:.8rem;font-weight:700;color:var(--blue);}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px;}
.modal-bg.open{display:flex;}
.modal-box{background:white;border-radius:16px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
.modal-header{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;border-radius:16px 16px 0 0;}
.modal-header h3{margin:0;font-size:1.05rem;}
.modal-close{background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;opacity:.8;line-height:1;}
.modal-close:hover{opacity:1;}
.modal-body{padding:24px;}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end;}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="admin-wrap">

  <!-- Flash message -->
  <?php if($msg): ?>
  <div class="alert alert-<?= $msgType==='success'?'success':'error' ?>" style="margin-bottom:18px;">
    <?= $msgType==='success'?'&#x2705;':'&#x274C;' ?> <?= $msg ?>
  </div>
  <?php endif; ?>

  <!-- Admin Tab Bar -->
  <div class="admin-tabs">
    <button class="admin-tab <?= $activeTab==='dashboard'?'active':'' ?>" onclick="switchTab('dashboard')">&#x1F4CA; Dashboard</button>
    <button class="admin-tab <?= $activeTab==='sports' ?'active':'' ?>"  onclick="switchTab('sports')">&#x26BD; Sports</button>
    <button class="admin-tab <?= $activeTab==='coaches'?'active':'' ?>" onclick="switchTab('coaches')">&#x1F3C6; Coaches</button>
    <button class="admin-tab <?= $activeTab==='players'?'active':'' ?>" onclick="switchTab('players')">&#x1F3C5; Players</button>
    <button class="admin-tab" onclick="window.location='admin.php?logout=true'" style="color:var(--red);flex:0.5;">&#x1F512; Logout</button>
  </div>

  <!-- ══ DASHBOARD TAB ═════════════════════════════════════════ -->
  <div class="tab-pane <?= $activeTab==='dashboard'?'active':'' ?>" id="tab-dashboard">

    <!-- Quick links -->
    <div class="quick-links">
      <a href="players.php" class="ql-card"><span class="ql-icon">&#x1F3C5;</span><span class="ql-label">View Players</span></a>
      <a href="attendance.php" class="ql-card"><span class="ql-icon">&#x1F4CB;</span><span class="ql-label">Attendance</span></a>
      <a href="payment.php" class="ql-card"><span class="ql-icon">&#x1F4B3;</span><span class="ql-label">Payments</span></a>
      <a href="feedback.php" class="ql-card"><span class="ql-icon">&#x1F4AC;</span><span class="ql-label">Feedback</span></a>
    </div>

    <!-- Stats row 1 -->
    <div class="dash-stats">
      <div class="ds-card blue">
        <div class="ds-icon">&#x1F465;</div>
        <div>
          <div class="ds-num"><?= $total_players ?></div>
          <div class="ds-lbl">Total Players</div>
          <div class="ds-sub">&#x2B06; Registered</div>
        </div>
      </div>
      <div class="ds-card green">
        <div class="ds-icon">&#x1F4B0;</div>
        <div>
          <div class="ds-num">&#8377;<?= number_format($total_revenue,0) ?></div>
          <div class="ds-lbl">Total Revenue</div>
          <div class="ds-sub">&#x1F4C5; All time</div>
        </div>
      </div>
      <div class="ds-card gold">
        <div class="ds-icon">&#x1F4C5;</div>
        <div>
          <div class="ds-num">&#8377;<?= number_format($month_revenue,0) ?></div>
          <div class="ds-lbl">This Month</div>
          <div class="ds-sub">&#x1F4C8; Current month</div>
        </div>
      </div>
      <div class="ds-card red">
        <div class="ds-icon">&#x23F3;</div>
        <div>
          <div class="ds-num"><?= $unpaid_players ?></div>
          <div class="ds-lbl">Fee Pending</div>
          <div class="ds-sub"><?= $paid_pct ?>% paid so far</div>
        </div>
      </div>
    </div>

    <!-- Stats row 2 -->
    <div class="dash-stats" style="margin-top:-8px;">
      <div class="ds-card purple">
        <div class="ds-icon">&#x1F3C6;</div>
        <div>
          <div class="ds-num"><?= $total_coaches ?></div>
          <div class="ds-lbl">Total Coaches</div>
        </div>
      </div>
      <div class="ds-card teal">
        <div class="ds-icon">&#x26BD;</div>
        <div>
          <div class="ds-num"><?= $total_sports ?></div>
          <div class="ds-lbl">Sports Offered</div>
        </div>
      </div>
      <div class="ds-card orange">
        <div class="ds-icon">&#x1F4AC;</div>
        <div>
          <div class="ds-num"><?= $total_feedback ?></div>
          <div class="ds-lbl">Feedback Received</div>
          <div class="ds-sub">&#x2B50; <?= $avg_rating ?>/5 avg rating</div>
        </div>
      </div>
      <div class="ds-card navy">
        <div class="ds-icon">&#x1F4CB;</div>
        <div>
          <div class="ds-num"><?= $att_present ?></div>
          <div class="ds-lbl">Present Today</div>
          <div class="ds-sub">of <?= $att_today ?> marked</div>
        </div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="charts-row">

      <!-- Sport-wise Enrollment Pie Chart -->
      <div class="chart-card">
        <h4>&#x1F967; Sport-wise Enrollment</h4>
        <div class="chart-wrap">
          <canvas id="pieChart"></canvas>
        </div>
      </div>

      <!-- Monthly Revenue Bar Chart -->
      <div class="chart-card">
        <h4>&#x1F4B8; Monthly Revenue (Last 6 Months)</h4>
        <div class="chart-wrap">
          <canvas id="barChart"></canvas>
        </div>
      </div>

    </div>

    <!-- Bottom row: Recent activity + Fee status -->
    <div class="bottom-row">

      <!-- Recent Registrations -->
      <div class="chart-card">
        <h4>&#x1F195; Recent Registrations</h4>
        <?php if($recent_players && $recent_players->num_rows>0): ?>
        <ul class="recent-list">
          <?php while($r=$recent_players->fetch_assoc()): ?>
          <li class="recent-item">
            <div class="ri-avatar"><?= strtoupper(substr($r['full_name'],0,1)) ?></div>
            <div>
              <div class="ri-name"><?= htmlspecialchars($r['full_name']) ?></div>
              <div class="ri-sub">&#x26BD; <?= htmlspecialchars($r['sport']) ?></div>
            </div>
            <div class="ri-right">
              <div style="font-size:.75rem;color:var(--muted);"><?= date('d M Y',strtotime($r['registration_date'])) ?></div>
            </div>
          </li>
          <?php endwhile; ?>
        </ul>
        <?php else: ?>
        <div style="text-align:center;color:var(--muted);padding:20px;">No registrations yet.</div>
        <?php endif; ?>
      </div>

      <!-- Recent Payments + Fee Status -->
      <div class="chart-card">
        <h4>&#x1F4B3; Recent Payments</h4>
        <?php if($recent_payments && $recent_payments->num_rows>0): ?>
        <ul class="recent-list">
          <?php while($r=$recent_payments->fetch_assoc()): ?>
          <li class="recent-item">
            <div class="ri-avatar" style="background:linear-gradient(135deg,#27ae60,#2ecc71);">&#x1F4B5;</div>
            <div>
              <div class="ri-name"><?= htmlspecialchars($r['full_name']) ?></div>
              <div class="ri-sub"><?= htmlspecialchars($r['method']) ?> &bull; <?= date('d M',strtotime($r['payment_date'])) ?></div>
            </div>
            <div class="ri-right">
              <div style="font-weight:700;color:var(--green);">&#8377;<?= number_format($r['amount'],0) ?></div>
            </div>
          </li>
          <?php endwhile; ?>
        </ul>
        <?php else: ?>
        <div style="text-align:center;color:var(--muted);padding:20px;">No payments yet.</div>
        <?php endif; ?>

        <!-- Fee status progress bars -->
        <div style="margin-top:18px;padding-top:16px;border-top:2px solid var(--border);">
          <div style="font-weight:700;color:var(--blue);font-size:.88rem;margin-bottom:12px;">&#x1F4CA; Fee Collection Status</div>
          <div class="progress-bar-wrap">
            <div class="progress-label"><span>&#x2714; Paid</span><span><?= $paid_players ?> players (<?= $paid_pct ?>%)</span></div>
            <div class="progress-bg"><div class="progress-fill" style="width:<?= $paid_pct ?>%;background:var(--green);"></div></div>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-label"><span>&#x23F3; Unpaid</span><span><?= $unpaid_players ?> players (<?= $unpaid_pct ?>%)</span></div>
            <div class="progress-bg"><div class="progress-fill" style="width:<?= $unpaid_pct ?>%;background:var(--red);"></div></div>
          </div>
        </div>
      </div>

    </div>

    <!-- Sport enrollment bars -->
    <?php if(!empty($sport_labels)): ?>
    <div class="chart-card" style="margin-bottom:20px;">
      <h4>&#x1F4CA; Players per Sport</h4>
      <?php
      $max = max($sport_counts) ?: 1;
      $colors=['#2a5298','#27ae60','#e74c3c','#f39c12','#8e44ad','#16a085','#e67e22'];
      foreach($sport_labels as $i=>$s):
        $pct=round(($sport_counts[$i]/$max)*100);
        $col=$colors[$i%count($colors)];
      ?>
      <div class="progress-bar-wrap">
        <div class="progress-label">
          <span><?= htmlspecialchars($s) ?></span>
          <span><?= $sport_counts[$i] ?> players</span>
        </div>
        <div class="progress-bg">
          <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $col ?>;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div><!-- /dashboard tab -->

  <!-- ══ SPORTS TAB ══════════════════════════════════════════════ -->
  <div class="tab-pane <?= $activeTab==='sports'?'active':'' ?>" id="tab-sports">

    <!-- Add Sport -->
    <div class="add-card">
      <div class="add-card-title">&#x2795; Add New Sport</div>
      <?php if(!empty($sport_errors)): ?>
      <div class="err-box"><ul><?php foreach($sport_errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
      <?php endif; ?>
      <?php if($sport_success): ?>
      <div class="ok-box">&#x2705; <?= htmlspecialchars($sport_success) ?></div>
      <?php endif; ?>
      <form method="POST" id="sportAddForm" novalidate>
        <div class="form-row c3">
          <div class="fg">
            <label>Sport *</label>
            <select name="sport_name" id="as_sport" class="<?= !empty($sport_errors)?'err':'' ?>">
              <option value="">-- Choose Sport --</option>
              <?php foreach(['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'] as $s): ?>
              <option value="<?=$s?>" <?= (isset($_POST['add_sport'])&&$_POST['sport_name']==$s)?'selected':'' ?>><?=$s?></option>
              <?php endforeach; ?>
            </select>
            <span class="ferr" id="as_err_sport">Please select a sport.</span>
          </div>
          <div class="fg">
            <label>Monthly Fees (&#8377;) *</label>
            <input type="number" name="fees" id="as_fees" step="0.01" min="1" max="100000" placeholder="e.g. 2000"
                   value="<?= isset($_POST['add_sport'])?htmlspecialchars($_POST['fees']):'' ?>">
            <span class="ferr" id="as_err_fees">Enter a valid fee (₹1–₹1,00,000).</span>
          </div>
          <div class="fg">
            <label>Timings *</label>
            <input type="text" name="timings" id="as_timings" placeholder="e.g. 6AM - 8AM" maxlength="100"
                   value="<?= isset($_POST['add_sport'])?htmlspecialchars($_POST['timings']):'' ?>">
            <span class="ferr" id="as_err_timings">Timings must be at least 3 characters.</span>
          </div>
        </div>
        <button type="submit" name="add_sport" class="btn btn-primary" onclick="return validateAddSport()">&#x2795; Add Sport</button>
      </form>
    </div>

    <!-- Sports Table -->
    <div class="tbl-card">
      <div class="tbl-head">
        <h3>&#x26BD; All Sports (<?= $sports_data->num_rows ?>)</h3>
      </div>
      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>#</th><th>Sport</th><th>Monthly Fee</th><th>Timings</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $i=1; $sports_data->data_seek(0); while($row=$sports_data->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($row['sport_name']) ?></strong></td>
          <td><strong>&#8377;<?= number_format($row['fees'],2) ?></strong></td>
          <td><?= htmlspecialchars($row['timings']) ?></td>
          <td>
            <div class="action-btns">
              <button class="btn-edit" onclick="openEditSport(<?= $row['id'] ?>,'<?= addslashes($row['sport_name']) ?>',<?= $row['fees'] ?>,'<?= addslashes($row['timings']) ?>')">&#x270F; Edit</button>
              <a class="btn-del" href="admin.php?delete_sport=<?= $row['id'] ?>&tab=sports"
                 onclick="return confirm('Delete <?= addslashes($row['sport_name']) ?>? This cannot be undone.')">&#x1F5D1; Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if($sports_data->num_rows===0): ?>
        <tr><td colspan="5" class="no-data">No sports added yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>

  <!-- ══ COACHES TAB ════════════════════════════════════════════ -->
  <div class="tab-pane <?= $activeTab==='coaches'?'active':'' ?>" id="tab-coaches">

    <!-- Add Coach -->
    <div class="add-card">
      <div class="add-card-title">&#x2795; Add New Coach</div>
      <?php if(!empty($coach_errors)): ?>
      <div class="err-box"><ul><?php foreach($coach_errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
      <?php endif; ?>
      <?php if($coach_success): ?>
      <div class="ok-box">&#x2705; <?= htmlspecialchars($coach_success) ?></div>
      <?php endif; ?>
      <form method="POST" id="coachAddForm" novalidate>
        <div class="form-row c2">
          <div class="fg">
            <label>Coach Name *</label>
            <input type="text" name="coach_name" id="ac_name" placeholder="e.g. Rahul Sharma" maxlength="100"
                   value="<?= isset($_POST['add_coach'])?htmlspecialchars($_POST['coach_name']):'' ?>">
            <span class="ferr" id="ac_err_name">Letters and spaces only, min 3 characters.</span>
          </div>
          <div class="fg">
            <label>Sport *</label>
            <select name="sport" id="ac_sport">
              <option value="">-- Choose Sport --</option>
              <?php foreach(['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'] as $s): ?>
              <option value="<?=$s?>" <?= (isset($_POST['add_coach'])&&$_POST['sport']==$s)?'selected':'' ?>><?=$s?></option>
              <?php endforeach; ?>
            </select>
            <span class="ferr" id="ac_err_sport">Please select a sport.</span>
          </div>
          <div class="fg">
            <label>Experience *</label>
            <input type="text" name="experience" id="ac_exp" placeholder="e.g. 5 years" maxlength="50"
                   value="<?= isset($_POST['add_coach'])?htmlspecialchars($_POST['experience']):'' ?>">
            <span class="ferr" id="ac_err_exp">Must include a number, e.g. "5 years".</span>
          </div>
          <div class="fg">
            <label>Photo <span style="font-weight:400;color:#aaa;">(optional)</span></label>
            <input type="text" name="photo" id="ac_photo" placeholder="images/coaches/coach1.jpg"
                   value="<?= isset($_POST['add_coach'])?htmlspecialchars($_POST['photo']):'' ?>">
            <span class="ferr" id="ac_err_photo">Must be a valid URL or images/coaches/... path.</span>
          </div>
        </div>
        <button type="submit" name="add_coach" class="btn btn-primary" onclick="return validateAddCoach()">&#x2795; Add Coach</button>
      </form>
    </div>

    <!-- Coaches Table -->
    <div class="tbl-card">
      <div class="tbl-head">
        <h3>&#x1F3C6; All Coaches (<?= $coaches_data->num_rows ?>)</h3>
      </div>
      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead><tr><th>#</th><th>Photo</th><th>Name</th><th>Sport</th><th>Experience</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $i=1; $coaches_data->data_seek(0); while($row=$coaches_data->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><img src="<?= htmlspecialchars($row['photo']?:'images/coaches/coach1.jpg') ?>"
                   class="coach-thumb" onerror="this.src='images/coaches/coach1.jpg'"></td>
          <td><strong><?= htmlspecialchars($row['coach_name']) ?></strong></td>
          <td><span class="badge badge-sport"><?= htmlspecialchars($row['sport']) ?></span></td>
          <td><?= htmlspecialchars($row['experience']) ?></td>
          <td>
            <div class="action-btns">
              <button class="btn-edit" onclick="openEditCoach(<?= $row['id'] ?>,'<?= addslashes($row['coach_name']) ?>','<?= addslashes($row['sport']) ?>','<?= addslashes($row['experience']) ?>','<?= addslashes($row['photo']) ?>')">&#x270F; Edit</button>
              <a class="btn-del" href="admin.php?delete_coach=<?= $row['id'] ?>&tab=coaches"
                 onclick="return confirm('Delete coach <?= addslashes($row['coach_name']) ?>?')">&#x1F5D1; Delete</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if($coaches_data->num_rows===0): ?>
        <tr><td colspan="6" class="no-data">No coaches added yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>

  <!-- ══ PLAYERS TAB ════════════════════════════════════════════ -->
  <div class="tab-pane <?= $activeTab==='players'?'active':'' ?>" id="tab-players">
    <div class="tbl-card">
      <div class="tbl-head">
        <h3>&#x1F3C5; Registered Players (<?= $players_data->num_rows ?>)</h3>
      </div>
      <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Player</th><th>Age</th><th>Gender</th><th>Sport</th><th>Phone</th><th>Email</th><th>Fee</th><th>Amount</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php $i=1; $players_data->data_seek(0); while($row=$players_data->fetch_assoc()):
          $status=$row['fee_status']??'Unpaid';
          $init=strtoupper(substr($row['full_name'],0,1));
        ?>
        <tr>
          <td><?= $i++ ?></td>
          <td>
            <div class="player-cell">
              <div class="p-avatar"><?= $init ?></div>
              <div>
                <div style="font-weight:700;font-size:.87rem;"><?= htmlspecialchars($row['full_name']) ?></div>
                <div style="font-size:.73rem;color:var(--muted);">#<?= $row['id'] ?></div>
              </div>
            </div>
          </td>
          <td><?= $row['age'] ?></td>
          <td><?= htmlspecialchars($row['gender']) ?></td>
          <td style="font-size:.82rem;"><?= htmlspecialchars($row['sports_list'] ?? '—') ?></td>
          <td style="font-size:.82rem;"><?= htmlspecialchars($row['phone']) ?></td>
          <td style="font-size:.78rem;"><?= htmlspecialchars($row['email']) ?></td>
          <td>
            <?php if($status==='Paid'): ?>
              <span class="badge badge-paid">&#x2714; Paid</span>
            <?php else: ?>
              <span class="badge badge-unpaid">&#x23F3; Unpaid</span>
            <?php endif; ?>
          </td>
          <td><?= $row['amount_paid']>0?'₹'.number_format($row['amount_paid'],2):'—' ?></td>
          <td>
            <div class="action-btns">
              <button class="btn-edit" onclick="openEditPlayerFull(
                <?= $row['id'] ?>,
                '<?= addslashes($row['full_name']) ?>',
                <?= $row['age'] ?>,
                '<?= addslashes($row['gender']) ?>',
                '<?= addslashes($row['sports_list'] ?? '') ?>',
                '<?= addslashes($row['phone']) ?>',
                '<?= addslashes($row['email']) ?>'
              )">&#x270F; Edit</button>
              <?php if($status==='Paid'): ?>
              <a class="btn-unpaid-sm" href="admin.php?update_fee=unpaid&id=<?= $row['id'] ?>&tab=players">Unpaid</a>
              <?php else: ?>
              <a class="btn-paid-sm" href="admin.php?update_fee=paid&id=<?= $row['id'] ?>&tab=players">Mark Paid</a>
              <?php endif; ?>
              <a class="btn-del" href="admin.php?delete_player=<?= $row['id'] ?>&tab=players"
                 onclick="return confirm('Delete player <?= addslashes($row['full_name']) ?>?')">&#x1F5D1;</a>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if($players_data->num_rows===0): ?>
        <tr><td colspan="10" class="no-data">No players registered yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>

</div><!-- /admin-wrap -->

<!-- ══ EDIT SPORT MODAL ══════════════════════════════════════════ -->
<div class="modal-bg" id="modal-sport">
  <div class="modal-box">
    <div class="modal-header">
      <h3>&#x270F; Edit Sport</h3>
      <button class="modal-close" onclick="closeModal('modal-sport')">&times;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="sport_id" id="es_id">
        <div class="form-row c2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Sport Name</label>
            <input type="text" id="es_name" readonly style="background:#f7f9fc;color:var(--muted);">
          </div>
          <div class="fg">
            <label>Monthly Fees (&#8377;) *</label>
            <input type="number" name="fees" id="es_fees" step="0.01" min="1" max="100000" required>
          </div>
        </div>
        <div class="fg">
          <label>Timings *</label>
          <input type="text" name="timings" id="es_timings" maxlength="100" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-sport')">Cancel</button>
        <button type="submit" name="edit_sport" class="btn btn-primary">&#x2714; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT COACH MODAL ══════════════════════════════════════════ -->
<div class="modal-bg" id="modal-coach">
  <div class="modal-box">
    <div class="modal-header">
      <h3>&#x270F; Edit Coach</h3>
      <button class="modal-close" onclick="closeModal('modal-coach')">&times;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="coach_id" id="ec_id">
        <div class="form-row c2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Coach Name *</label>
            <input type="text" name="coach_name" id="ec_name" maxlength="100" required>
          </div>
          <div class="fg">
            <label>Sport *</label>
            <select name="sport" id="ec_sport" required>
              <?php foreach(['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'] as $s): ?>
              <option value="<?=$s?>"><?=$s?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label>Experience *</label>
            <input type="text" name="experience" id="ec_exp" maxlength="50" required>
          </div>
          <div class="fg">
            <label>Photo URL / Path</label>
            <input type="text" name="photo" id="ec_photo" placeholder="images/coaches/coach1.jpg">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-coach')">Cancel</button>
        <button type="submit" name="edit_coach" class="btn btn-primary">&#x2714; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT PLAYER MODAL ══════════════════════════════════════════ -->
<div class="modal-bg" id="modal-player">
  <div class="modal-box">
    <div class="modal-header">
      <h3>&#x270F; Edit Player</h3>
      <button class="modal-close" onclick="closeModal('modal-player')">&times;</button>
    </div>
    <form method="POST" id="editPlayerForm" novalidate>
      <div class="modal-body">
        <input type="hidden" name="player_id" id="ep_id">
        <div class="form-row c2" style="margin-bottom:14px;">
          <div class="fg">
            <label>Full Name *</label>
            <input type="text" name="full_name" id="ep_name" maxlength="100" required>
            <span class="ferr" id="ep_err_name">Letters and spaces only, min 3 chars.</span>
          </div>
          <div class="fg">
            <label>Age *</label>
            <input type="number" name="age" id="ep_age" min="5" max="60" required>
            <span class="ferr" id="ep_err_age">Age must be between 5 and 60.</span>
          </div>
          <div class="fg">
            <label>Gender *</label>
            <select name="gender" id="ep_gender" required>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="fg" style="grid-column:1/-1;">
            <label>Sports <span style="color:var(--red)">*</span> <span style="font-weight:400;color:var(--muted);font-size:.78rem;">— select one or more</span></label>
            <div id="ep_sports_wrap" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:4px;">
              <?php foreach(['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'] as $s): ?>
              <label style="display:flex;align-items:center;gap:6px;background:#f7f9fc;border:1.5px solid var(--border);border-radius:8px;padding:8px 10px;cursor:pointer;font-size:.82rem;font-weight:600;transition:all .2s;">
                <input type="checkbox" name="sports[]" value="<?=$s?>" class="ep_sport_cb" style="width:16px;height:16px;accent-color:var(--blue);">
                <?=$s?>
              </label>
              <?php endforeach; ?>
            </div>
            <span class="ferr" id="ep_err_sports">Please select at least one sport.</span>
          </div>
          <div class="fg">
            <label>Phone *</label>
            <input type="text" name="phone" id="ep_phone" maxlength="10" required>
            <span class="ferr" id="ep_err_phone">10-digit number starting with 6–9.</span>
          </div>
          <div class="fg">
            <label>Email *</label>
            <input type="email" name="email" id="ep_email" required>
            <span class="ferr" id="ep_err_email">Enter a valid email address.</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-player')">Cancel</button>
        <button type="submit" name="edit_player" class="btn btn-primary" onclick="return validateEditPlayer()">&#x2714; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<footer class="site-footer">
  <p>&copy; 2025 <strong>Sports Academy</strong> — All Rights Reserved</p>
</footer>

<?php $conn->close(); ?>

<script>
// ── Tab switching ─────────────────────────────────────────────────
function switchTab(name) {
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.admin-tab').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  event.target.classList.add('active');
  history.replaceState(null,'','admin.php?tab='+name);
}

// ── Modal open/close ──────────────────────────────────────────────
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(m=>{
  m.addEventListener('click', e=>{ if(e.target===m) m.classList.remove('open'); });
});

// ── Open Edit Sport modal ─────────────────────────────────────────
function openEditSport(id, name, fees, timings) {
  document.getElementById('es_id').value      = id;
  document.getElementById('es_name').value    = name;
  document.getElementById('es_fees').value    = fees;
  document.getElementById('es_timings').value = timings;
  document.getElementById('modal-sport').classList.add('open');
}

// ── Open Edit Coach modal ─────────────────────────────────────────
function openEditCoach(id, name, sport, exp, photo) {
  document.getElementById('ec_id').value    = id;
  document.getElementById('ec_name').value  = name;
  document.getElementById('ec_exp').value   = exp;
  document.getElementById('ec_photo').value = photo;
  const sel = document.getElementById('ec_sport');
  for(let i=0;i<sel.options.length;i++) {
    if(sel.options[i].value===sport){ sel.selectedIndex=i; break; }
  }
  document.getElementById('modal-coach').classList.add('open');
}

// ── Open Edit Player modal ────────────────────────────────────────
function openEditPlayer(id, name, age, gender, sportIds, phone, email) {
  document.getElementById('ep_id').value     = id;
  document.getElementById('ep_name').value   = name;
  document.getElementById('ep_age').value    = age;
  document.getElementById('ep_email').value  = email;
  document.getElementById('ep_phone').value  = phone;
  const gs = document.getElementById('ep_gender');
  for(let i=0;i<gs.options.length;i++) if(gs.options[i].value===gender){ gs.selectedIndex=i; break; }
  // Checkboxes — sportIds is comma-separated sport IDs from DB
  // We pass sport names via sports_list instead — let's use name matching
  document.querySelectorAll('.ep_sport_cb').forEach(cb => cb.checked = false);
  // sportIds here is actually sport_ids (comma separated IDs) — we match by checking all
  // We re-open using sport names from the button onclick
  document.querySelectorAll('#editPlayerForm .ferr').forEach(e=>e.classList.remove('show'));
  document.getElementById('modal-player').classList.add('open');
}

// ── Open Edit Player with sport names (called from table) ─────────
function openEditPlayerFull(id, name, age, gender, sportsJson, phone, email) {
  document.getElementById('ep_id').value     = id;
  document.getElementById('ep_name').value   = name;
  document.getElementById('ep_age').value    = age;
  document.getElementById('ep_email').value  = email;
  document.getElementById('ep_phone').value  = phone;
  const gs = document.getElementById('ep_gender');
  for(let i=0;i<gs.options.length;i++) if(gs.options[i].value===gender){ gs.selectedIndex=i; break; }
  // Set sport checkboxes
  const selectedSports = sportsJson ? sportsJson.split(',') : [];
  document.querySelectorAll('.ep_sport_cb').forEach(cb => {
    cb.checked = selectedSports.includes(cb.value);
    const lbl = cb.parentElement;
    lbl.style.borderColor = cb.checked ? 'var(--blue)' : '';
    lbl.style.background  = cb.checked ? '#e8effd' : '';
  });
  document.querySelectorAll('#editPlayerForm .ferr').forEach(e=>e.classList.remove('show'));
  document.getElementById('modal-player').classList.add('open');
}

// ── Validation helpers ────────────────────────────────────────────
function setF(elId, errId, ok, msg) {
  const el=document.getElementById(elId), err=document.getElementById(errId);
  el.classList.toggle('err',!ok); el.classList.toggle('ok',ok);
  if(err){ err.textContent=msg||''; err.classList.toggle('show',!ok); }
  return ok;
}

// ── Add Sport validation ──────────────────────────────────────────
document.getElementById('as_sport').addEventListener('change',function(){setF('as_sport','as_err_sport',this.value!=='','');});
document.getElementById('as_fees').addEventListener('input',function(){const v=parseFloat(this.value);setF('as_fees','as_err_fees',!isNaN(v)&&v>0&&v<=100000,'');});
document.getElementById('as_timings').addEventListener('input',function(){setF('as_timings','as_err_timings',this.value.trim().length>=3,'');});
function validateAddSport(){
  const s1=setF('as_sport','as_err_sport',document.getElementById('as_sport').value!=='','Please select a sport.');
  const v=parseFloat(document.getElementById('as_fees').value);
  const s2=setF('as_fees','as_err_fees',!isNaN(v)&&v>0&&v<=100000,'Enter a valid fee (₹1–₹1,00,000).');
  const s3=setF('as_timings','as_err_timings',document.getElementById('as_timings').value.trim().length>=3,'Timings must be at least 3 characters.');
  return s1&&s2&&s3;
}

// ── Add Coach validation ──────────────────────────────────────────
document.getElementById('ac_name').addEventListener('input',function(){setF('ac_name','ac_err_name',/^[A-Za-z\s]{3,}$/.test(this.value.trim()),'');});
document.getElementById('ac_sport').addEventListener('change',function(){setF('ac_sport','ac_err_sport',this.value!=='','');});
document.getElementById('ac_exp').addEventListener('input',function(){const v=this.value.trim();setF('ac_exp','ac_err_exp',v.length>=2&&/\d/.test(v),'');});
function validateAddCoach(){
  const n=document.getElementById('ac_name').value.trim();
  const v1=setF('ac_name','ac_err_name',/^[A-Za-z\s]{3,}$/.test(n),'Letters and spaces only, min 3 characters.');
  const v2=setF('ac_sport','ac_err_sport',document.getElementById('ac_sport').value!=='','Please select a sport.');
  const exp=document.getElementById('ac_exp').value.trim();
  const v3=setF('ac_exp','ac_err_exp',exp.length>=2&&/\d/.test(exp),'Must include a number, e.g. "5 years".');
  return v1&&v2&&v3;
}

// ── Edit Player validation ────────────────────────────────────────
function validateEditPlayer(){
  const name=document.getElementById('ep_name').value.trim();
  const age=parseInt(document.getElementById('ep_age').value);
  const phone=document.getElementById('ep_phone').value.trim();
  const email=document.getElementById('ep_email').value.trim();
  const checked=[...document.querySelectorAll('.ep_sport_cb:checked')];
  const v1=setF('ep_name','ep_err_name',/^[A-Za-z\s]{3,}$/.test(name),'Letters and spaces only, min 3 chars.');
  const v2=setF('ep_age','ep_err_age',!isNaN(age)&&age>=5&&age<=60,'Age must be between 5 and 60.');
  const v3=setF('ep_phone','ep_err_phone',/^[6-9][0-9]{9}$/.test(phone),'10-digit number starting with 6–9.');
  const v4=setF('ep_email','ep_err_email',/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email),'Enter a valid email address.');
  const v5=checked.length>0;
  const se=document.getElementById('ep_err_sports');
  if(se){se.classList.toggle('show',!v5);}
  return v1&&v2&&v3&&v4&&v5;
}
// Live validation in edit player modal
document.getElementById('ep_name').addEventListener('input',function(){const ok=/^[A-Za-z\s]{3,}$/.test(this.value.trim());this.classList.toggle('err',!ok);this.classList.toggle('ok',ok);});
document.getElementById('ep_phone').addEventListener('input',function(){this.value=this.value.replace(/\D/g,'').substring(0,10);const ok=/^[6-9][0-9]{9}$/.test(this.value);this.classList.toggle('err',!ok);this.classList.toggle('ok',ok);});
document.getElementById('ep_email').addEventListener('input',function(){const ok=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());this.classList.toggle('err',!ok);this.classList.toggle('ok',ok);});
document.getElementById('ep_age').addEventListener('input',function(){const v=parseInt(this.value);const ok=!isNaN(v)&&v>=5&&v<=60;this.classList.toggle('err',!ok);this.classList.toggle('ok',ok);});

// Sport checkbox highlight in edit modal
document.querySelectorAll('.ep_sport_cb').forEach(cb=>{
  cb.addEventListener('change',function(){
    const lbl=this.parentElement;
    lbl.style.borderColor=this.checked?'var(--blue)':'';
    lbl.style.background =this.checked?'#e8effd':'';
    const se=document.getElementById('ep_err_sports');
    if(se) se.classList.toggle('show',[...document.querySelectorAll('.ep_sport_cb:checked')].length===0);
  });
});
</script>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Sport Enrollment Pie Chart ────────────────────────────────────
const pieLabels = <?= json_encode($sport_labels) ?>;
const pieCounts = <?= json_encode($sport_counts) ?>;
const pieColors = ['#2a5298','#27ae60','#e74c3c','#f39c12','#8e44ad','#16a085','#e67e22'];

if (pieLabels.length > 0 && document.getElementById('pieChart')) {
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
      labels: pieLabels,
      datasets: [{
        data: pieCounts,
        backgroundColor: pieColors,
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right', labels: { font: { size: 12 }, padding: 14 } },
        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} players` } }
      },
      cutout: '60%'
    }
  });
}

// ── Monthly Revenue Bar Chart ─────────────────────────────────────
const revLabels = <?= json_encode($rev_labels) ?>;
const revData   = <?= json_encode($rev_data) ?>;

if (document.getElementById('barChart')) {
  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: revLabels,
      datasets: [{
        label: 'Revenue (₹)',
        data: revData,
        backgroundColor: 'rgba(42,82,152,0.85)',
        borderRadius: 8,
        borderSkipped: false,
        hoverBackgroundColor: '#1e3c72'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ` ₹${ctx.raw.toLocaleString('en-IN')}` } }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#f0f0f0' },
          ticks: { callback: v => '₹'+v.toLocaleString('en-IN'), font: { size: 11 } }
        },
        x: { grid: { display: false }, ticks: { font: { size: 11 } } }
      }
    }
  });
}
</script>
</body>
</html>
