<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "sports_academy");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$message = ''; $messageType = '';

// ── Save payment after simulation ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $player_id  = intval($_POST['player_id']);
    $amount     = floatval($_POST['amount']);
    $method     = $conn->real_escape_string($_POST['method']);
    $note       = $conn->real_escape_string(trim($_POST['note'] ?? ''));
    $txn_id     = $conn->real_escape_string($_POST['txn_id'] ?? '');

    if ($player_id > 0 && $amount > 0) {
        $sql = "INSERT INTO payments (player_id, amount, method, note, razorpay_order_id, razorpay_payment_id, payment_date)
                VALUES ($player_id, $amount, '$method', '$note', 'SIM_ORDER', '$txn_id', NOW())";
        if ($conn->query($sql)) {
            $conn->query("UPDATE players SET fee_status='Paid', amount_paid=$amount,
                          payment_method='$method', payment_date=NOW() WHERE id=$player_id");
            $message = "Payment of ₹" . number_format($amount,2) . " recorded successfully! Txn ID: $txn_id";
            $messageType = 'success';
        }
    }
}

// ── Cash / manual ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_cash'])) {
    $player_id = intval($_POST['player_id']);
    $amount    = floatval($_POST['amount']);
    $method    = $conn->real_escape_string($_POST['method']);
    $note      = $conn->real_escape_string(trim($_POST['note'] ?? ''));
    $txn_id    = 'CASH_' . strtoupper(substr(md5(uniqid()), 0, 10));

    if ($player_id > 0 && $amount > 0) {
        $sql = "INSERT INTO payments (player_id, amount, method, note, razorpay_order_id, razorpay_payment_id, payment_date)
                VALUES ($player_id, $amount, '$method', '$note', 'MANUAL', '$txn_id', NOW())";
        if ($conn->query($sql)) {
            $conn->query("UPDATE players SET fee_status='Paid', amount_paid=$amount,
                          payment_method='$method', payment_date=NOW() WHERE id=$player_id");
            $message = "Cash payment recorded! Txn ID: $txn_id";
            $messageType = 'success';
        }
    }
}

// ── Stats ─────────────────────────────────────────────────────────
$total_paid   = $conn->query("SELECT SUM(amount) as t FROM payments")->fetch_assoc()['t'] ?? 0;
$paid_count   = $conn->query("SELECT COUNT(*) as c FROM players WHERE fee_status='Paid'")->fetch_assoc()['c'] ?? 0;
$unpaid_count = $conn->query("SELECT COUNT(*) as c FROM players WHERE fee_status='Unpaid' OR fee_status IS NULL")->fetch_assoc()['c'] ?? 0;

// ── Players ───────────────────────────────────────────────────────
$players_res = $conn->query("
    SELECT p.id, p.full_name, p.email, p.phone, p.fee_status,
           GROUP_CONCAT(s.sport_name ORDER BY s.sport_name SEPARATOR ', ') as sport,
           COALESCE(SUM(DISTINCT s.fees), 0) as fees
    FROM players p
    LEFT JOIN player_sports ps ON p.id = ps.player_id
    LEFT JOIN sports s ON ps.sport_id = s.id
    GROUP BY p.id
    ORDER BY p.full_name");

// ── History ───────────────────────────────────────────────────────
$filter_method = isset($_GET['method']) ? $conn->real_escape_string($_GET['method']) : '';
$where = ["1=1"];
if ($filter_method) $where[] = "py.method='$filter_method'";
$history_res = $conn->query("
    SELECT py.*, pl.full_name,
           GROUP_CONCAT(s.sport_name ORDER BY s.sport_name SEPARATOR ', ') as sport
    FROM payments py
    JOIN players pl ON py.player_id = pl.id
    LEFT JOIN player_sports ps ON pl.id = ps.player_id
    LEFT JOIN sports s ON ps.sport_id = s.id
    WHERE ".implode(" AND ",$where)."
    GROUP BY py.id
    ORDER BY py.payment_date DESC LIMIT 100");

// ── Unpaid ────────────────────────────────────────────────────────
$unpaid_res = $conn->query("
    SELECT p.id, p.full_name, p.email, p.phone,
           GROUP_CONCAT(s.sport_name ORDER BY s.sport_name SEPARATOR ', ') as sport,
           COALESCE(SUM(DISTINCT s.fees), 0) as fees
    FROM players p
    LEFT JOIN player_sports ps ON p.id = ps.player_id
    LEFT JOIN sports s ON ps.sport_id = s.id
    WHERE p.fee_status='Unpaid' OR p.fee_status IS NULL
    GROUP BY p.id
    ORDER BY p.full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f4f8;color:#333;}
.header{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:18px 30px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 3px 12px rgba(0,0,0,0.2);}
.header h1{font-size:1.6rem;}
.header nav a{color:rgba(255,255,255,0.85);text-decoration:none;margin-left:18px;font-size:0.9rem;}
.header nav a:hover{color:white;}
.container{max-width:1100px;margin:30px auto;padding:0 20px;}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:white;border-radius:12px;padding:22px 24px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.07);border-top:4px solid;}
.stat-card.revenue{border-color:#27ae60;} .stat-card.paid{border-color:#2a5298;} .stat-card.unpaid{border-color:#e74c3c;}
.stat-card .num{font-size:2rem;font-weight:700;}
.stat-card .label{font-size:0.85rem;color:#777;margin-top:4px;}
.stat-card.revenue .num{color:#27ae60;} .stat-card.paid .num{color:#2a5298;} .stat-card.unpaid .num{color:#e74c3c;}
.tabs{display:flex;margin-bottom:24px;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.tab-btn{flex:1;padding:13px;text-align:center;cursor:pointer;background:white;border:none;font-size:0.95rem;font-weight:600;color:#555;transition:all 0.2s;}
.tab-btn:hover{background:#f0f4ff;color:#1e3c72;}
.tab-btn.active{background:#1e3c72;color:white;}
.tab-content{display:none;} .tab-content.active{display:block;}
.card{background:white;border-radius:12px;padding:26px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:24px;}
.card h3{color:#1e3c72;font-size:1.1rem;margin-bottom:18px;padding-bottom:10px;border-bottom:2px solid #eee;}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
label{font-size:0.85rem;font-weight:600;color:#555;}
input,select,textarea{padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;outline:none;transition:border 0.2s;font-family:inherit;}
input:focus,select:focus,textarea:focus{border-color:#2a5298;}
textarea{resize:vertical;min-height:60px;}
.btn-primary{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;border:none;padding:12px 28px;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;}
.btn-primary:hover{opacity:0.88;}
.btn-sm{padding:6px 14px;border:none;border-radius:6px;font-size:0.8rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-pay{background:#2a5298;color:white;} .btn-pay:hover{background:#1e3c72;}
.btn-green{background:#27ae60;color:white;} .btn-green:hover{background:#219a52;}
.alert{padding:14px 18px;border-radius:8px;margin-bottom:20px;font-weight:600;}
.alert-success{background:#d4edda;color:#155724;border-left:4px solid #27ae60;}
.alert-error{background:#f8d7da;color:#721c24;border-left:4px solid #e74c3c;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:0.88rem;}
th{background:#1e3c72;color:white;padding:11px;text-align:left;}
td{padding:10px 11px;border-bottom:1px solid #f0f0f0;}
tr:hover td{background:#f7f9fc;}
.badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:0.78rem;font-weight:700;}
.badge-online{background:#dbeafe;color:#1d4ed8;}
.badge-cash{background:#d4edda;color:#155724;}
.badge-other{background:#fef9c3;color:#854d0e;}
.filter-bar{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:20px;}
.filter-bar .form-group{min-width:140px;}
.btn-filter{background:#2a5298;color:white;border:none;padding:10px 22px;border-radius:8px;cursor:pointer;font-size:0.9rem;font-weight:600;}
.btn-reset{background:#eee;color:#555;border:1px solid #ddd;padding:10px 18px;border-radius:8px;cursor:pointer;font-size:0.9rem;text-decoration:none;font-weight:600;}
.no-data{text-align:center;color:#999;padding:30px;}

/* ── PAYMENT GATEWAY SIMULATOR ── */
.gateway-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:2000;align-items:center;justify-content:center;}
.gateway-overlay.open{display:flex;}
.gateway-box{background:white;border-radius:16px;width:440px;max-width:95vw;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
.gw-header{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;}
.gw-header h3{font-size:1rem;margin:0;}
.gw-header .gw-amount{font-size:1.4rem;font-weight:700;}
.gw-header .gw-sub{font-size:0.78rem;opacity:0.8;}
.gw-body{padding:22px;}
.gw-methods{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:20px;}
.gw-method{border:2px solid #eee;border-radius:10px;padding:10px 6px;text-align:center;cursor:pointer;transition:all 0.2s;font-size:0.78rem;font-weight:600;color:#555;}
.gw-method:hover{border-color:#2a5298;background:#f0f4ff;color:#1e3c72;}
.gw-method.active{border-color:#1e3c72;background:#e8effd;color:#1e3c72;}
.gw-method .gw-icon{font-size:1.4rem;display:block;margin-bottom:4px;}
.gw-panel{display:none;} .gw-panel.active{display:block;}
.gw-input{width:100%;padding:11px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:0.95rem;margin-bottom:12px;outline:none;transition:border 0.2s;}
.gw-input:focus{border-color:#2a5298;}
.gw-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.gw-label{font-size:0.78rem;font-weight:600;color:#888;margin-bottom:4px;}
.gw-pay-btn{width:100%;padding:14px;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:8px;transition:opacity 0.2s;}
.gw-pay-btn:hover{opacity:0.88;}
.gw-secure{text-align:center;font-size:0.75rem;color:#aaa;margin-top:10px;}
.gw-close-btn{background:none;border:none;color:white;font-size:1.2rem;cursor:pointer;opacity:0.8;}
.gw-close-btn:hover{opacity:1;}

/* Processing screen */
.gw-processing{display:none;text-align:center;padding:30px 20px;}
.gw-processing.active{display:block;}
.spinner{width:50px;height:50px;border:4px solid #eee;border-top:4px solid #2a5298;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 16px;}
@keyframes spin{to{transform:rotate(360deg);}}

/* Success screen */
.gw-success{display:none;text-align:center;padding:30px 20px;}
.gw-success.active{display:block;}
.success-icon{font-size:4rem;margin-bottom:12px;animation:pop 0.4s ease;}
@keyframes pop{0%{transform:scale(0);}80%{transform:scale(1.15);}100%{transform:scale(1);}}
.gw-txn{font-size:0.78rem;color:#888;margin-top:8px;word-break:break-all;}

/* Receipt Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:3000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:white;border-radius:16px;padding:0;width:400px;max-width:92vw;overflow:hidden;}
.receipt-header{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:20px;text-align:center;}
.receipt-header h3{margin:0;font-size:1.1rem;}
.receipt-header p{margin:4px 0 0;font-size:0.82rem;opacity:0.85;}
.receipt-body{padding:20px;}
.receipt-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f0f0f0;font-size:0.9rem;}
.receipt-total{display:flex;justify-content:space-between;padding:12px 0;font-size:1.1rem;font-weight:700;color:#27ae60;}
.receipt-txn{font-size:0.75rem;color:#aaa;margin-top:8px;text-align:center;word-break:break-all;}
.receipt-footer{padding:16px 20px;display:flex;gap:10px;justify-content:center;border-top:1px solid #eee;}
.btn-close{background:#1e3c72;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer;font-weight:600;}
.btn-print{background:#eee;color:#555;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-weight:600;}

@media(max-width:600px){.stats{grid-template-columns:1fr;}.gw-methods{grid-template-columns:repeat(2,1fr);}}

/* ── FEE BREAKDOWN CARD ── */
.fee-breakdown{background:linear-gradient(135deg,#f0f7ff,#e8effd);border:2px solid #c7d9f5;border-radius:12px;padding:18px 22px;margin-top:4px;}
.fee-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed #c7d9f5;font-size:0.92rem;color:#444;}
.fee-row:last-child{border-bottom:none;}
.fee-row.fee-total{font-size:1.15rem;font-weight:700;color:#1e3c72;padding-top:12px;border-top:2px solid #2a5298;border-bottom:none;margin-top:4px;}
.fee-row.fee-total span:last-child{color:#27ae60;font-size:1.3rem;}
@media print{
  .header,.tabs,.stats,.filter-bar,.btn-print,.btn-close{display:none!important;}
  .modal-overlay{position:static!important;background:none!important;}
  .modal{box-shadow:none!important;}
}
</style>
</head>
<body>

<div class="header">
  <h1>&#x1F3C5; Sports Academy &nbsp;|&nbsp; Payment</h1>
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
  <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats">
    <div class="stat-card revenue">
      <div class="num">&#8377;<?= number_format($total_paid ?? 0, 0) ?></div>
      <div class="label">Total Revenue</div>
    </div>
    <div class="stat-card paid">
      <div class="num"><?= $paid_count ?></div>
      <div class="label">Players Paid</div>
    </div>
    <div class="stat-card unpaid">
      <div class="num"><?= $unpaid_count ?></div>
      <div class="label">Pending Payments</div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn active" onclick="showTab('online-tab',this)">&#x1F4B3; Online Payment</button>
    <button class="tab-btn" onclick="showTab('cash-tab',this)">&#x1F4B5; Cash / Manual</button>
    <button class="tab-btn" onclick="showTab('pending-tab',this)">&#x26A0; Pending (<?= $unpaid_count ?>)</button>
    <button class="tab-btn" onclick="showTab('history-tab',this)">&#x1F4CB; History</button>
  </div>

  <!-- ══ ONLINE TAB ══════════════════════════════════════════════ -->
  <div id="online-tab" class="tab-content active">
    <div class="card">
      <h3>&#x1F4B3; Online Payment Gateway</h3>
      <div class="form-grid">

        <div class="form-group">
          <label>Select Player *</label>
          <select id="onl_player" onchange="onPlayerChange(this)">
            <option value="">-- Choose Player --</option>
            <?php $players_res->data_seek(0); while($p=$players_res->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"
                    data-fees="<?= $p['fees'] ?>"
                    data-name="<?= htmlspecialchars($p['full_name']) ?>"
                    data-email="<?= htmlspecialchars($p['email']) ?>"
                    data-sport="<?= htmlspecialchars($p['sport']) ?>">
              <?= htmlspecialchars($p['full_name']) ?> — <?= htmlspecialchars($p['sport']) ?>
              <?= $p['fee_status']=='Paid'?' (Paid)':'' ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Number of Months *</label>
          <select id="onl_months" onchange="calcFees()" disabled>
            <option value="">-- Select Months --</option>
            <option value="1">1 Month</option>
            <option value="2">2 Months</option>
            <option value="3">3 Months</option>
            <option value="4">4 Months</option>
            <option value="5">5 Months</option>
            <option value="6">6 Months</option>
            <option value="7">7 Months</option>
            <option value="8">8 Months</option>
            <option value="9">9 Months</option>
            <option value="10">10 Months</option>
            <option value="11">11 Months</option>
            <option value="12">12 Months (Full Year)</option>
          </select>
        </div>

        <!-- Fee Breakdown Card -->
        <div class="form-group full" id="onl_breakdown_wrap" style="display:none;">
          <div class="fee-breakdown">
            <div class="fee-row">
              <span>Monthly Fee</span>
              <span id="onl_monthly_fee">&#8377;0</span>
            </div>
            <div class="fee-row">
              <span>Number of Months</span>
              <span id="onl_months_show">0</span>
            </div>
            <div class="fee-row fee-total">
              <span>Total Amount</span>
              <span id="onl_total_show">&#8377;0</span>
            </div>
          </div>
        </div>

        <div class="form-group" id="onl_amount_wrap" style="display:none;">
          <label>Total Amount (&#8377;)</label>
          <input type="number" id="onl_amount" step="0.01" placeholder="0.00" readonly
                 style="background:#f0f7ff; font-weight:700; color:#1e3c72; font-size:1.1rem;">
        </div>

        <div class="form-group" id="onl_note_wrap" style="display:none;">
          <label>Note (optional)</label>
          <input type="text" id="onl_note" placeholder="e.g. Monthly fee April–June">
        </div>

      </div>
      <br>
      <button class="btn-primary" id="onl_btn" onclick="openGateway()" disabled
              style="background:linear-gradient(135deg,#6366f1,#4f46e5);font-size:1rem;padding:13px 32px;">
        &#x1F512; Proceed to Pay
      </button>
      <p style="font-size:0.78rem;color:#aaa;margin-top:10px;">&#x1F512; Secure Payment &nbsp;|&nbsp; UPI &bull; Card &bull; Net Banking &bull; Wallet</p>
    </div>
  </div>

  <!-- ══ CASH TAB ════════════════════════════════════════════════ -->
  <div id="cash-tab" class="tab-content">
    <div class="card">
      <h3>&#x1F4B5; Record Cash / Offline Payment</h3>
      <form method="POST" id="cashForm">
        <div class="form-grid">

          <div class="form-group">
            <label>Select Player *</label>
            <select name="player_id" id="cash_player" onchange="cashPlayerChange(this)" required>
              <option value="">-- Choose Player --</option>
              <?php $players_res->data_seek(0); while($p=$players_res->fetch_assoc()): ?>
              <option value="<?= $p['id'] ?>" data-fees="<?= $p['fees'] ?>">
                <?= htmlspecialchars($p['full_name']) ?> — <?= htmlspecialchars($p['sport']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Number of Months *</label>
            <select id="cash_months" onchange="calcCashFees()" disabled>
              <option value="">-- Select Months --</option>
              <option value="1">1 Month</option>
              <option value="2">2 Months</option>
              <option value="3">3 Months</option>
              <option value="4">4 Months</option>
              <option value="5">5 Months</option>
              <option value="6">6 Months</option>
              <option value="7">7 Months</option>
              <option value="8">8 Months</option>
              <option value="9">9 Months</option>
              <option value="10">10 Months</option>
              <option value="11">11 Months</option>
              <option value="12">12 Months (Full Year)</option>
            </select>
          </div>

          <!-- Fee Breakdown -->
          <div class="form-group full" id="cash_breakdown_wrap" style="display:none;">
            <div class="fee-breakdown">
              <div class="fee-row">
                <span>Monthly Fee</span>
                <span id="cash_monthly_fee">&#8377;0</span>
              </div>
              <div class="fee-row">
                <span>Number of Months</span>
                <span id="cash_months_show">0</span>
              </div>
              <div class="fee-row fee-total">
                <span>Total Amount</span>
                <span id="cash_total_show">&#8377;0</span>
              </div>
            </div>
          </div>

          <div class="form-group" id="cash_amount_wrap" style="display:none;">
            <label>Total Amount (&#8377;)</label>
            <input type="number" name="amount" id="cashAmt" step="0.01" placeholder="0.00" readonly required
                   style="background:#f0f7ff; font-weight:700; color:#1e3c72; font-size:1.1rem;">
          </div>

          <div class="form-group" id="cash_method_wrap" style="display:none;">
            <label>Payment Method *</label>
            <select name="method" required>
              <option value="">-- Select --</option>
              <option value="Cash">Cash</option>
              <option value="Cheque">Cheque</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>

          <div class="form-group" id="cash_note_wrap" style="display:none;">
            <label>Note (optional)</label>
            <input type="text" name="note" placeholder="e.g. Monthly fee April–June">
          </div>

        </div>
        <br>
        <button type="submit" name="process_cash" class="btn-primary" id="cash_submit_btn" style="display:none;">
          Record Payment
        </button>
      </form>
    </div>
  </div>

  <!-- ══ PENDING TAB ══════════════════════════════════════════════ -->
  <div id="pending-tab" class="tab-content">
    <div class="card">
      <h3>&#x26A0; Players with Pending Payments</h3>
      <div class="table-wrap">
        <?php if($unpaid_res && $unpaid_res->num_rows>0): ?>
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Sport</th><th>Phone</th><th>Fees Due</th><th>Online</th><th>Cash</th></tr></thead>
          <tbody>
          <?php $i=1; while($row=$unpaid_res->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['sport']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><strong>&#8377;<?= number_format($row['fees'],2) ?></strong></td>
            <td><button class="btn-sm btn-pay" onclick="quickOnline(<?= $row['id'] ?>,<?= $row['fees'] ?>)">&#x1F4B3; Pay</button></td>
            <td><button class="btn-sm btn-green" onclick="quickCash(<?= $row['id'] ?>,<?= $row['fees'] ?>)">&#x1F4B5; Cash</button></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="no-data">&#x1F389; All players are paid up!</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ HISTORY TAB ══════════════════════════════════════════════ -->
  <div id="history-tab" class="tab-content">
    <div class="card">
      <h3>&#x1F4CB; Payment History</h3>
      <form method="GET">
        <input type="hidden" name="tab" value="history">
        <div class="filter-bar">
          <div class="form-group">
            <label>Method</label>
            <select name="method">
              <option value="">All</option>
              <option value="Online" <?= $filter_method=='Online'?'selected':'' ?>>Online</option>
              <option value="Cash"   <?= $filter_method=='Cash'  ?'selected':'' ?>>Cash</option>
              <option value="Cheque" <?= $filter_method=='Cheque'?'selected':'' ?>>Cheque</option>
              <option value="Bank Transfer" <?= $filter_method=='Bank Transfer'?'selected':'' ?>>Bank Transfer</option>
            </select>
          </div>
          <button type="submit" class="btn-filter">&#x1F50D; Filter</button>
          <a href="payment.php?tab=history" class="btn-reset">Reset</a>
        </div>
      </form>
      <div class="table-wrap">
        <?php if($history_res && $history_res->num_rows>0): ?>
        <table>
          <thead><tr><th>#</th><th>Player</th><th>Sport</th><th>Amount</th><th>Method</th><th>Txn ID</th><th>Note</th><th>Date</th><th>Receipt</th></tr></thead>
          <tbody>
          <?php $i=1; while($row=$history_res->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['sport']) ?></td>
            <td><strong>&#8377;<?= number_format($row['amount'],2) ?></strong></td>
            <td>
              <?php if(in_array($row['method'],['Online','UPI','Card'])): ?>
                <span class="badge badge-online">&#x1F4B3; <?= $row['method'] ?></span>
              <?php elseif($row['method']==='Cash'): ?>
                <span class="badge badge-cash">&#x1F4B5; Cash</span>
              <?php else: ?>
                <span class="badge badge-other"><?= htmlspecialchars($row['method']) ?></span>
              <?php endif; ?>
            </td>
            <td style="font-size:0.75rem;color:#888;"><?= htmlspecialchars($row['razorpay_payment_id'] ?? '—') ?></td>
            <td><?= htmlspecialchars($row['note'] ?: '—') ?></td>
            <td><?= date('d M Y', strtotime($row['payment_date'])) ?></td>
            <td>
              <button class="btn-sm btn-pay" onclick="showReceipt(
                '<?= addslashes($row['full_name']) ?>',
                '<?= addslashes($row['sport']) ?>',
                <?= $row['amount'] ?>,
                '<?= addslashes($row['method']) ?>',
                '<?= date('d M Y, h:i A', strtotime($row['payment_date'])) ?>',
                '<?= addslashes($row['razorpay_payment_id'] ?? '') ?>'
              )">&#x1F9FE; Receipt</button>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="no-data">No records found.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /container -->

<!-- ══════════════════════════════════════════════════════════════
     PAYMENT GATEWAY SIMULATOR
══════════════════════════════════════════════════════════════ -->
<div class="gateway-overlay" id="gatewayOverlay">
  <div class="gateway-box">

    <!-- Header -->
    <div class="gw-header">
      <div>
        <div class="gw-sub">Sports Academy</div>
        <div class="gw-amount" id="gw_show_amount">&#8377;0</div>
        <div class="gw-sub" id="gw_show_name"></div>
      </div>
      <button class="gw-close-btn" onclick="closeGateway()">&#x2715;</button>
    </div>

    <!-- Main body -->
    <div class="gw-body" id="gw_main">

      <!-- Method selector -->
      <div class="gw-methods">
        <div class="gw-method active" onclick="switchMethod('upi',this)">
          <span class="gw-icon">&#x1F4F1;</span>UPI
        </div>
        <div class="gw-method" onclick="switchMethod('card',this)">
          <span class="gw-icon">&#x1F4B3;</span>Card
        </div>
        <div class="gw-method" onclick="switchMethod('netbank',this)">
          <span class="gw-icon">&#x1F3E6;</span>Net Banking
        </div>
        <div class="gw-method" onclick="switchMethod('wallet',this)">
          <span class="gw-icon">&#x1F4B0;</span>Wallet
        </div>
      </div>

      <!-- UPI Panel -->
      <div class="gw-panel active" id="panel_upi">
        <div class="gw-label">Enter UPI ID</div>
        <input class="gw-input" id="upi_id" type="text" placeholder="yourname@upi">
        <button class="gw-pay-btn" onclick="simulatePay('UPI')">&#x1F512; Pay via UPI</button>
      </div>

      <!-- Card Panel -->
      <div class="gw-panel" id="panel_card">
        <div class="gw-label">Card Number</div>
        <input class="gw-input" id="card_num" type="text" placeholder="4111 1111 1111 1111" maxlength="19" oninput="fmtCard(this)">
        <div class="gw-row">
          <div>
            <div class="gw-label">Expiry</div>
            <input class="gw-input" type="text" placeholder="MM/YY" maxlength="5" oninput="fmtExpiry(this)">
          </div>
          <div>
            <div class="gw-label">CVV</div>
            <input class="gw-input" type="password" placeholder="123" maxlength="3">
          </div>
        </div>
        <div class="gw-label">Name on Card</div>
        <input class="gw-input" type="text" placeholder="Full Name">
        <button class="gw-pay-btn" onclick="simulatePay('Card')">&#x1F512; Pay with Card</button>
      </div>

      <!-- Net Banking Panel -->
      <div class="gw-panel" id="panel_netbank">
        <div class="gw-label">Select Bank</div>
        <select class="gw-input">
          <option value="">-- Choose Bank --</option>
          <option>State Bank of India</option>
          <option>HDFC Bank</option>
          <option>ICICI Bank</option>
          <option>Axis Bank</option>
          <option>Kotak Mahindra Bank</option>
          <option>Punjab National Bank</option>
          <option>Bank of Baroda</option>
        </select>
        <button class="gw-pay-btn" onclick="simulatePay('Net Banking')">&#x1F512; Pay via Net Banking</button>
      </div>

      <!-- Wallet Panel -->
      <div class="gw-panel" id="panel_wallet">
        <div class="gw-label">Select Wallet</div>
        <select class="gw-input">
          <option value="">-- Choose Wallet --</option>
          <option>Paytm</option>
          <option>PhonePe</option>
          <option>Amazon Pay</option>
          <option>Mobikwik</option>
        </select>
        <button class="gw-pay-btn" onclick="simulatePay('Wallet')">&#x1F512; Pay via Wallet</button>
      </div>

      <div class="gw-secure">&#x1F512; 256-bit SSL Secured &nbsp;&bull;&nbsp; PCI DSS Compliant</div>
    </div>

    <!-- Processing screen -->
    <div class="gw-processing" id="gw_processing">
      <div class="spinner"></div>
      <p style="font-weight:600;color:#333;">Processing your payment...</p>
      <p style="font-size:0.82rem;color:#aaa;margin-top:6px;">Please do not close or refresh</p>
    </div>

    <!-- Success screen -->
    <div class="gw-success" id="gw_success">
      <div class="success-icon">&#x2705;</div>
      <h3 style="color:#27ae60;margin-bottom:6px;">Payment Successful!</h3>
      <p style="color:#555;" id="gw_success_amount"></p>
      <div class="gw-txn" id="gw_txn_display"></div>
      <button class="gw-pay-btn" style="margin-top:20px;" onclick="afterSuccess()">View Receipt</button>
    </div>

  </div>
</div>

<!-- HIDDEN FORM to save payment -->
<form method="POST" id="saveForm" style="display:none;">
  <input type="hidden" name="save_payment" value="1">
  <input type="hidden" name="player_id"    id="sf_player_id">
  <input type="hidden" name="amount"       id="sf_amount">
  <input type="hidden" name="method"       id="sf_method">
  <input type="hidden" name="note"         id="sf_note">
  <input type="hidden" name="txn_id"       id="sf_txn_id">
</form>

<!-- RECEIPT MODAL -->
<div class="modal-overlay" id="receiptModal">
  <div class="modal">
    <div class="receipt-header">
      <h3>&#x1F9FE; Payment Receipt</h3>
      <p>Sports Academy — Official Receipt</p>
    </div>
    <div class="receipt-body">
      <div class="receipt-row"><span>Player</span><span id="r-name"></span></div>
      <div class="receipt-row"><span>Sport</span><span id="r-sport"></span></div>
      <div class="receipt-row"><span>Method</span><span id="r-method"></span></div>
      <div class="receipt-row"><span>Date</span><span id="r-date"></span></div>
      <div class="receipt-total"><span>Amount Paid</span><span id="r-amount"></span></div>
      <div class="receipt-txn" id="r-txn"></div>
    </div>
    <div class="receipt-footer">
      <button class="btn-print" onclick="window.print()">&#x1F5A8; Print</button>
      <button class="btn-close" onclick="closeReceipt()">Close</button>
    </div>
  </div>
</div>

<script>
// ── Current payment state ─────────────────────────────────────────
let curPlayer = {id:'', name:'', sport:'', amount:0, note:'', fees:0};
let lastTxnId = '';
let lastMethod = '';

// ── Tab switching ─────────────────────────────────────────────────
function showTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
}
<?php if(isset($_GET['tab'])): ?>
const tmap={'history':3,'pending':2,'cash':1};
const ti=tmap['<?= $_GET['tab'] ?>'];
if(ti!==undefined) showTab(['online-tab','cash-tab','pending-tab','history-tab'][ti], document.querySelectorAll('.tab-btn')[ti]);
<?php endif; ?>

// ── Player selection (Online) ─────────────────────────────────────
function onPlayerChange(sel) {
  const opt = sel.options[sel.selectedIndex];
  curPlayer.id    = sel.value;
  curPlayer.name  = opt.dataset.name  || '';
  curPlayer.sport = opt.dataset.sport || '';
  curPlayer.fees  = parseFloat(opt.dataset.fees) || 0;

  const monthSel = document.getElementById('onl_months');
  if (sel.value) {
    monthSel.disabled = false;
    monthSel.value = '';
  } else {
    monthSel.disabled = true;
    monthSel.value = '';
  }

  // Hide breakdown until month selected
  document.getElementById('onl_breakdown_wrap').style.display = 'none';
  document.getElementById('onl_amount_wrap').style.display    = 'none';
  document.getElementById('onl_note_wrap').style.display      = 'none';
  document.getElementById('onl_amount').value = '';
  document.getElementById('onl_btn').disabled = true;
}

// ── Auto-calculate fees (Online) ──────────────────────────────────
function calcFees() {
  const months     = parseInt(document.getElementById('onl_months').value) || 0;
  const monthlyFee = curPlayer.fees;
  const total      = monthlyFee * months;

  if (months > 0 && monthlyFee > 0) {
    document.getElementById('onl_monthly_fee').textContent = '₹' + monthlyFee.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('onl_months_show').textContent = months + (months === 1 ? ' Month' : ' Months');
    document.getElementById('onl_total_show').textContent  = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});

    document.getElementById('onl_breakdown_wrap').style.display = 'block';
    document.getElementById('onl_amount_wrap').style.display    = 'block';
    document.getElementById('onl_note_wrap').style.display      = 'block';
    document.getElementById('onl_amount').value = total.toFixed(2);
    document.getElementById('onl_btn').disabled = false;
    curPlayer.amount = total;
  } else {
    document.getElementById('onl_breakdown_wrap').style.display = 'none';
    document.getElementById('onl_amount_wrap').style.display    = 'none';
    document.getElementById('onl_note_wrap').style.display      = 'none';
    document.getElementById('onl_amount').value = '';
    document.getElementById('onl_btn').disabled = true;
  }
}

// ── Player selection (Cash) ───────────────────────────────────────
function cashPlayerChange(sel) {
  const opt = sel.options[sel.selectedIndex];
  const monthSel = document.getElementById('cash_months');

  if (sel.value) {
    monthSel.disabled = false;
    monthSel.value = '';
    sel._fees = parseFloat(opt.dataset.fees) || 0;
  } else {
    monthSel.disabled = true;
  }

  // Hide all extra fields
  ['cash_breakdown_wrap','cash_amount_wrap','cash_method_wrap','cash_note_wrap'].forEach(id => {
    document.getElementById(id).style.display = 'none';
  });
  document.getElementById('cash_submit_btn').style.display = 'none';
  document.getElementById('cashAmt').value = '';
}

// ── Auto-calculate fees (Cash) ────────────────────────────────────
function calcCashFees() {
  const sel        = document.getElementById('cash_player');
  const months     = parseInt(document.getElementById('cash_months').value) || 0;
  const monthlyFee = sel._fees || 0;
  const total      = monthlyFee * months;

  if (months > 0 && monthlyFee > 0) {
    document.getElementById('cash_monthly_fee').textContent = '₹' + monthlyFee.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('cash_months_show').textContent = months + (months === 1 ? ' Month' : ' Months');
    document.getElementById('cash_total_show').textContent  = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('cashAmt').value = total.toFixed(2);

    ['cash_breakdown_wrap','cash_amount_wrap','cash_method_wrap','cash_note_wrap'].forEach(id => {
      document.getElementById(id).style.display = 'block';
    });
    document.getElementById('cash_submit_btn').style.display = 'inline-block';
  } else {
    ['cash_breakdown_wrap','cash_amount_wrap','cash_method_wrap','cash_note_wrap'].forEach(id => {
      document.getElementById(id).style.display = 'none';
    });
    document.getElementById('cash_submit_btn').style.display = 'none';
    document.getElementById('cashAmt').value = '';
  }
}

// ── Quick actions from pending tab ───────────────────────────────
function quickOnline(id, fees) {
  showTab('online-tab', document.querySelectorAll('.tab-btn')[0]);
  const sel = document.getElementById('onl_player');
  for (let i=0;i<sel.options.length;i++) {
    if (sel.options[i].value==id) { sel.selectedIndex=i; onPlayerChange(sel); break; }
  }
  // Auto-select 1 month and calculate
  document.getElementById('onl_months').value = '1';
  calcFees();
}
function quickCash(id, fees) {
  showTab('cash-tab', document.querySelectorAll('.tab-btn')[1]);
  const sel = document.getElementById('cash_player');
  for (let i=0;i<sel.options.length;i++) {
    if (sel.options[i].value==id) { sel.selectedIndex=i; cashPlayerChange(sel); break; }
  }
  // Auto-select 1 month and calculate
  document.getElementById('cash_months').value = '1';
  calcCashFees();
}

// ── Open Gateway ──────────────────────────────────────────────────
function openGateway() {
  const amount = parseFloat(document.getElementById('onl_amount').value);
  const months = document.getElementById('onl_months').value;
  const note   = document.getElementById('onl_note').value;
  const sel    = document.getElementById('onl_player');
  const opt    = sel.options[sel.selectedIndex];

  if (!sel.value || !months || !amount || amount<=0) {
    alert('Please select a player and number of months.');
    return;
  }

  curPlayer.id     = sel.value;
  curPlayer.name   = opt.dataset.name  || '';
  curPlayer.sport  = opt.dataset.sport || '';
  curPlayer.amount = amount;
  curPlayer.note   = note || months + ' month(s) fee';

  document.getElementById('gw_show_amount').textContent = '₹' + amount.toLocaleString('en-IN',{minimumFractionDigits:2});
  document.getElementById('gw_show_name').textContent   = curPlayer.name + ' — ' + curPlayer.sport;

  // Reset screens
  document.getElementById('gw_main').style.display       = 'block';
  document.getElementById('gw_processing').classList.remove('active');
  document.getElementById('gw_success').classList.remove('active');

  document.getElementById('gatewayOverlay').classList.add('open');
}
function closeGateway() {
  document.getElementById('gatewayOverlay').classList.remove('open');
}

// ── Switch payment method ─────────────────────────────────────────
function switchMethod(method, btn) {
  document.querySelectorAll('.gw-method').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.gw-panel').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel_'+method).classList.add('active');
}

// ── Simulate payment ──────────────────────────────────────────────
function simulatePay(method) {
  lastMethod = method;

  // Show processing
  document.getElementById('gw_main').style.display = 'none';
  document.getElementById('gw_processing').classList.add('active');

  // Simulate 2.5s processing delay
  setTimeout(() => {
    // Generate transaction ID
    lastTxnId = 'TXN' + Date.now() + Math.floor(Math.random()*9000+1000);

    document.getElementById('gw_processing').classList.remove('active');
    document.getElementById('gw_success').classList.add('active');
    document.getElementById('gw_success_amount').textContent =
      '₹' + curPlayer.amount.toLocaleString('en-IN',{minimumFractionDigits:2}) + ' paid successfully';
    document.getElementById('gw_txn_display').textContent = 'Transaction ID: ' + lastTxnId;
  }, 2500);
}

// ── After success — save to DB and show receipt ───────────────────
function afterSuccess() {
  // Fill hidden form
  document.getElementById('sf_player_id').value = curPlayer.id;
  document.getElementById('sf_amount').value    = curPlayer.amount;
  document.getElementById('sf_method').value    = lastMethod;
  document.getElementById('sf_note').value      = curPlayer.note;
  document.getElementById('sf_txn_id').value    = lastTxnId;

  // Submit to save payment
  document.getElementById('saveForm').submit();
}

// ── Card formatting helpers ───────────────────────────────────────
function fmtCard(el) {
  let v = el.value.replace(/\D/g,'').substring(0,16);
  el.value = v.replace(/(.{4})/g,'$1 ').trim();
}
function fmtExpiry(el) {
  let v = el.value.replace(/\D/g,'');
  if (v.length>=3) v = v.substring(0,2)+'/'+v.substring(2,4);
  el.value = v;
}

// ── Receipt Modal ─────────────────────────────────────────────────
function showReceipt(name, sport, amount, method, date, txnId) {
  document.getElementById('r-name').textContent   = name;
  document.getElementById('r-sport').textContent  = sport;
  document.getElementById('r-method').textContent = method;
  document.getElementById('r-date').textContent   = date;
  document.getElementById('r-amount').textContent = '₹' + parseFloat(amount).toLocaleString('en-IN',{minimumFractionDigits:2});
  const txnEl = document.getElementById('r-txn');
  txnEl.textContent = (txnId && txnId!=='MANUAL') ? 'Transaction ID: '+txnId : '';
  document.getElementById('receiptModal').classList.add('open');
}
function closeReceipt() {
  document.getElementById('receiptModal').classList.remove('open');
}
</script>
</body>
</html>
<?php $conn->close(); ?>
