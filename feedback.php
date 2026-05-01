<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "sports_academy");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$message = ''; $messageType = '';

// ── Delete feedback (admin) ───────────────────────────────────────
if (isset($_GET['delete_fb'])) {
    $id = intval($_GET['delete_fb']);
    $conn->query("DELETE FROM feedback WHERE id=$id");
    header("Location: feedback.php?tab=view");
    exit();
}

// ── Submit feedback ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $player_name = trim($conn->real_escape_string($_POST['player_name']));
    $email       = trim($conn->real_escape_string($_POST['email']));
    $sport       = trim($conn->real_escape_string($_POST['sport']));
    $rating      = intval($_POST['rating']);
    $category    = $conn->real_escape_string($_POST['category']);
    $comments    = trim($conn->real_escape_string($_POST['comments']));

    $errors = [];
    if (strlen($player_name) < 3 || !preg_match('/^[A-Za-z\s]+$/', $player_name))
        $errors[] = "Name must be at least 3 letters (no numbers or symbols).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";
    if (empty($category))
        $errors[] = "Please select a category.";
    if ($rating < 1 || $rating > 5)
        $errors[] = "Please select a star rating.";
    if (strlen($comments) < 10)
        $errors[] = "Comments must be at least 10 characters.";
    if (strlen($comments) > 1000)
        $errors[] = "Comments must be under 1000 characters.";

    if (empty($errors)) {
        $sql = "INSERT INTO feedback (player_name, email, sport, rating, category, comments)
                VALUES ('$player_name','$email','$sport','$rating','$category','$comments')";
        if ($conn->query($sql)) {
            $message = "Thank you, $player_name! Your feedback has been submitted successfully.";
            $messageType = 'success';
        } else {
            $message = "Error submitting feedback. Please try again.";
            $messageType = 'error';
        }
    } else {
        $message = implode(" | ", $errors);
        $messageType = 'error';
    }
}

// ── Stats ─────────────────────────────────────────────────────────
$avg_res      = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM feedback");
$avg_data     = $avg_res ? $avg_res->fetch_assoc() : ['avg_r'=>0,'total'=>0];
$avg_rating   = round($avg_data['avg_r'] ?? 0, 1);
$total_fb     = intval($avg_data['total']);

// Rating distribution
$rating_dist = [];
for ($r = 5; $r >= 1; $r--) {
    $res = $conn->query("SELECT COUNT(*) as c FROM feedback WHERE rating=$r");
    $rating_dist[$r] = $res ? intval($res->fetch_assoc()['c']) : 0;
}

// Category breakdown
$cat_res  = $conn->query("SELECT category, COUNT(*) as c FROM feedback GROUP BY category ORDER BY c DESC");
$cat_data = [];
while ($row = $cat_res->fetch_assoc()) $cat_data[] = $row;

// Filters
$filter_sport    = isset($_GET['sport'])    ? $conn->real_escape_string($_GET['sport'])    : '';
$filter_rating   = isset($_GET['rating'])   ? intval($_GET['rating'])                      : 0;
$filter_category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

$where = ["1=1"];
if ($filter_sport)    $where[] = "sport='$filter_sport'";
if ($filter_rating)   $where[] = "rating=$filter_rating";
if ($filter_category) $where[] = "category='$filter_category'";
$feedbacks = $conn->query("SELECT * FROM feedback WHERE ".implode(" AND ",$where)." ORDER BY submitted_at DESC");

// Sports for filter
$sports_res = $conn->query("SELECT sport_name FROM sports ORDER BY sport_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback - Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f0f4f8;color:#333;}

/* HEADER */
.header{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:18px 30px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 3px 12px rgba(0,0,0,0.2);}
.header h1{font-size:1.6rem;}
.header nav a{color:rgba(255,255,255,0.85);text-decoration:none;margin-left:18px;font-size:0.9rem;}
.header nav a:hover{color:white;}
.container{max-width:1100px;margin:30px auto;padding:0 20px;}

/* TABS */
.tabs{display:flex;margin-bottom:24px;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.tab-btn{flex:1;padding:13px;text-align:center;cursor:pointer;background:white;border:none;font-size:0.95rem;font-weight:600;color:#555;transition:all 0.2s;}
.tab-btn:hover{background:#f0f4ff;color:#1e3c72;}
.tab-btn.active{background:#1e3c72;color:white;}
.tab-content{display:none;} .tab-content.active{display:block;}

/* CARD */
.card{background:white;border-radius:12px;padding:28px;box-shadow:0 2px 10px rgba(0,0,0,0.07);margin-bottom:24px;}
.card h3{color:#1e3c72;font-size:1.1rem;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #eee;}

/* STATS */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:white;border-radius:12px;padding:20px 24px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.07);border-top:4px solid #2a5298;}
.stat-card .num{font-size:2.2rem;font-weight:700;color:#1e3c72;}
.stat-card .label{font-size:0.85rem;color:#777;margin-top:4px;}
.big-stars{color:#f39c12;font-size:1.6rem;letter-spacing:2px;}

/* FORM */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
label{font-size:0.85rem;font-weight:600;color:#555;}
input,select,textarea{padding:10px 12px;border:1.5px solid #ddd;border-radius:8px;font-size:0.95rem;outline:none;transition:border 0.2s;font-family:inherit;}
input:focus,select:focus,textarea:focus{border-color:#2a5298;}
input.err{border-color:#e74c3c;background:#fff8f8;}
input.ok{border-color:#27ae60;}
select.err{border-color:#e74c3c;background:#fff8f8;}
select.ok{border-color:#27ae60;}
textarea.err{border-color:#e74c3c;background:#fff8f8;}
textarea.ok{border-color:#27ae60;}
.field-err{font-size:0.75rem;color:#e74c3c;display:none;margin-top:2px;}
.field-err.show{display:block;}
.char-count{font-size:0.75rem;color:#aaa;text-align:right;margin-top:2px;}
textarea{resize:vertical;min-height:110px;}

/* STAR RATING */
.star-rating{display:flex;flex-direction:row-reverse;gap:6px;margin-top:4px;}
.star-rating input{display:none;}
.star-rating label{font-size:2.2rem;color:#ddd;cursor:pointer;transition:color 0.15s;}
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label{color:#f39c12;}
.star-hint{font-size:0.78rem;color:#aaa;margin-top:6px;}
#star_err{font-size:0.78rem;color:#e74c3c;display:none;margin-top:4px;}
#star_err.show{display:block;}

/* SUBMIT BUTTON */
.btn-submit{background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;border:none;padding:13px 36px;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;transition:opacity 0.2s;margin-top:8px;}
.btn-submit:hover{opacity:0.88;}

/* ALERT */
.alert{padding:14px 18px;border-radius:8px;margin-bottom:20px;font-weight:600;}
.alert-success{background:#d4edda;color:#155724;border-left:4px solid #27ae60;}
.alert-error{background:#f8d7da;color:#721c24;border-left:4px solid #e74c3c;}

/* RATING BAR CHART */
.rating-bars{margin-top:8px;}
.rating-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px;font-size:0.85rem;}
.rating-bar-row .star-label{width:28px;color:#f39c12;font-weight:700;}
.bar-bg{flex:1;background:#f0f0f0;border-radius:50px;height:10px;overflow:hidden;}
.bar-fill{height:100%;background:linear-gradient(90deg,#f39c12,#f1c40f);border-radius:50px;transition:width 0.6s ease;}
.bar-count{width:28px;text-align:right;color:#888;}

/* CATEGORY PILLS */
.cat-grid{display:flex;flex-wrap:wrap;gap:10px;margin-top:8px;}
.cat-pill{background:#e8effd;border-radius:50px;padding:6px 16px;font-size:0.82rem;font-weight:600;color:#2a5298;display:flex;align-items:center;gap:6px;}
.cat-pill .cat-count{background:#2a5298;color:white;border-radius:50px;padding:1px 8px;font-size:0.75rem;}

/* ANALYTICS ROW */
.analytics-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;}

/* FEEDBACK CARDS */
.feedback-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:16px;}
.feedback-item{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.07);border-left:4px solid;transition:transform 0.15s;}
.feedback-item:hover{transform:translateY(-2px);}
.feedback-item.r5{border-color:#27ae60;}
.feedback-item.r4{border-color:#2ecc71;}
.feedback-item.r3{border-color:#f39c12;}
.feedback-item.r2{border-color:#e67e22;}
.feedback-item.r1{border-color:#e74c3c;}
.feedback-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.feedback-name{font-weight:700;color:#1e3c72;font-size:0.95rem;}
.feedback-stars{color:#f39c12;font-size:1rem;}
.feedback-sport{font-size:0.78rem;color:#aaa;margin-bottom:6px;}
.feedback-cat{display:inline-block;background:#e8effd;color:#2a5298;font-size:0.72rem;font-weight:700;padding:2px 10px;border-radius:50px;margin-bottom:8px;}
.feedback-comment{font-size:0.88rem;color:#555;line-height:1.55;font-style:italic;}
.feedback-footer{display:flex;justify-content:space-between;align-items:center;margin-top:12px;}
.feedback-date{font-size:0.75rem;color:#ccc;}
.btn-del{background:none;border:1px solid #f5c6cb;color:#e74c3c;padding:3px 10px;border-radius:6px;font-size:0.75rem;cursor:pointer;}
.btn-del:hover{background:#f8d7da;}

/* FILTER */
.filter-bar{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;margin-bottom:20px;}
.filter-bar .form-group{min-width:140px;}
.btn-filter{background:#2a5298;color:white;border:none;padding:10px 22px;border-radius:8px;cursor:pointer;font-size:0.9rem;font-weight:600;}
.btn-reset{background:#eee;color:#555;border:1px solid #ddd;padding:10px 18px;border-radius:8px;font-size:0.9rem;text-decoration:none;font-weight:600;}
.no-data{text-align:center;color:#999;padding:40px;font-size:1rem;}

/* PROGRESS RING for satisfaction */
.ring-wrap{display:flex;flex-direction:column;align-items:center;justify-content:center;}
.ring-label{font-size:0.85rem;color:#777;margin-top:8px;}

@media(max-width:700px){
  .form-grid{grid-template-columns:1fr;}
  .stats{grid-template-columns:1fr;}
  .analytics-row{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<div class="header">
  <h1>&#x1F3C5; Sports Academy &nbsp;|&nbsp; Feedback</h1>
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

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn active" id="tab_submit_btn" onclick="showTab('submit-tab',this)">&#x270D; Submit Feedback</button>
    <button class="tab-btn" id="tab_view_btn" onclick="showTab('view-tab',this)">&#x1F4CA; View All Feedback (<?= $total_fb ?>)</button>
  </div>

  <!-- ══ SUBMIT TAB ══════════════════════════════════════════════ -->
  <div id="submit-tab" class="tab-content active">
    <div class="card">
      <h3>&#x270D; Share Your Feedback</h3>
      <form method="POST" id="feedbackForm" novalidate>
        <div class="form-grid">

          <!-- Name -->
          <div class="form-group">
            <label for="f_name">Your Name *</label>
            <input type="text" id="f_name" name="player_name"
                   placeholder="e.g. Rahul Sharma"
                   value="<?= isset($_POST['player_name']) ? htmlspecialchars($_POST['player_name']) : '' ?>">
            <span class="field-err" id="err_name">Name must be at least 3 letters (no numbers).</span>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label for="f_email">Email *</label>
            <input type="email" id="f_email" name="email"
                   placeholder="you@example.com"
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            <span class="field-err" id="err_email">Please enter a valid email address.</span>
          </div>

          <!-- Sport -->
          <div class="form-group">
            <label for="f_sport">Sport</label>
            <select id="f_sport" name="sport">
              <option value="">-- Select Sport --</option>
              <?php
              $sp_list = ['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'];
              foreach($sp_list as $sp):
              ?>
              <option value="<?=$sp?>" <?= (isset($_POST['sport'])&&$_POST['sport']==$sp)?'selected':'' ?>><?=$sp?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Category -->
          <div class="form-group">
            <label for="f_cat">Category *</label>
            <select id="f_cat" name="category">
              <option value="">-- Select Category --</option>
              <?php foreach(['Coaching','Facilities','Management','Schedule','General'] as $cat): ?>
              <option value="<?=$cat?>" <?= (isset($_POST['category'])&&$_POST['category']==$cat)?'selected':'' ?>><?=$cat?></option>
              <?php endforeach; ?>
            </select>
            <span class="field-err" id="err_cat">Please select a category.</span>
          </div>

          <!-- Star Rating -->
          <div class="form-group full">
            <label>Your Rating *</label>
            <div class="star-rating">
              <?php for($r=5;$r>=1;$r--): ?>
              <input type="radio" id="s<?=$r?>" name="rating" value="<?=$r?>"
                     <?= (isset($_POST['rating'])&&intval($_POST['rating'])==$r)?'checked':'' ?>
                     onchange="updateStarHint(<?=$r?>)">
              <label for="s<?=$r?>">&#x2605;</label>
              <?php endfor; ?>
            </div>
            <div class="star-hint" id="star_hint">Click a star to rate</div>
            <span class="field-err" id="star_err">Please select a star rating.</span>
          </div>

          <!-- Comments -->
          <div class="form-group full">
            <label for="f_comments">Your Comments * <span style="font-weight:400;color:#aaa;">(min 10 characters)</span></label>
            <textarea id="f_comments" name="comments"
                      placeholder="Write your feedback here — what did you like? What can be improved?"
                      oninput="updateCharCount()"><?= isset($_POST['comments']) ? htmlspecialchars($_POST['comments']) : '' ?></textarea>
            <div class="char-count"><span id="char_num">0</span> / 1000 characters</div>
            <span class="field-err" id="err_comments">Comments must be at least 10 characters.</span>
          </div>

        </div>
        <button type="submit" name="submit_feedback" class="btn-submit"
                onclick="return validateFeedback()">
          &#x1F4AC; Submit Feedback
        </button>
      </form>
    </div>
  </div>

  <!-- ══ VIEW TAB ════════════════════════════════════════════════ -->
  <div id="view-tab" class="tab-content">

    <!-- STATS -->
    <div class="stats">
      <div class="stat-card">
        <div class="num"><?= $total_fb ?></div>
        <div class="label">Total Responses</div>
      </div>
      <div class="stat-card">
        <div class="num"><?= $avg_rating ?> <span style="font-size:1rem;">/ 5</span></div>
        <div class="label">Average Rating</div>
        <div class="big-stars">
          <?php
          $full  = floor($avg_rating);
          $empty = 5 - $full;
          echo str_repeat('&#x2605;', $full) . str_repeat('&#x2606;', $empty);
          ?>
        </div>
      </div>
      <div class="stat-card">
        <div class="num"><?= $total_fb > 0 ? round(($avg_rating/5)*100) : 0 ?>%</div>
        <div class="label">Satisfaction Rate</div>
      </div>
    </div>

    <!-- ANALYTICS ROW -->
    <div class="analytics-row">

      <!-- Rating Distribution -->
      <div class="card">
        <h3>&#x2B50; Rating Distribution</h3>
        <div class="rating-bars">
          <?php for($r=5;$r>=1;$r--): ?>
          <?php $pct = $total_fb > 0 ? round(($rating_dist[$r]/$total_fb)*100) : 0; ?>
          <div class="rating-bar-row">
            <span class="star-label"><?=$r?>&#x2605;</span>
            <div class="bar-bg">
              <div class="bar-fill" style="width:<?=$pct?>%"></div>
            </div>
            <span class="bar-count"><?=$rating_dist[$r]?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div class="card">
        <h3>&#x1F4C1; Feedback by Category</h3>
        <?php if(!empty($cat_data)): ?>
        <div class="cat-grid">
          <?php foreach($cat_data as $cd): ?>
          <div class="cat-pill">
            <?= htmlspecialchars($cd['category']) ?>
            <span class="cat-count"><?= $cd['c'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
          <div class="no-data" style="padding:16px;">No data yet.</div>
        <?php endif; ?>
      </div>

    </div>

    <!-- ALL FEEDBACK -->
    <div class="card">
      <h3>&#x1F4CB; All Feedback</h3>

      <!-- Filter -->
      <form method="GET">
        <input type="hidden" name="tab" value="view">
        <div class="filter-bar">
          <div class="form-group">
            <label>Sport</label>
            <select name="sport">
              <option value="">All Sports</option>
              <?php while($s=$sports_res->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($s['sport_name']) ?>" <?= $filter_sport==$s['sport_name']?'selected':'' ?>>
                <?= htmlspecialchars($s['sport_name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Rating</label>
            <select name="rating">
              <option value="">All Ratings</option>
              <?php for($r=5;$r>=1;$r--): ?>
              <option value="<?=$r?>" <?= $filter_rating==$r?'selected':'' ?>>
                <?= str_repeat('&#x2605;',$r).' ('.$r.')' ?>
              </option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category">
              <option value="">All Categories</option>
              <?php foreach(['Coaching','Facilities','Management','Schedule','General'] as $cat): ?>
              <option value="<?=$cat?>" <?= $filter_category==$cat?'selected':'' ?>><?=$cat?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn-filter">&#x1F50D; Filter</button>
          <a href="feedback.php?tab=view" class="btn-reset">Reset</a>
        </div>
      </form>

      <?php if ($feedbacks && $feedbacks->num_rows > 0): ?>
      <div class="feedback-grid">
        <?php while($fb=$feedbacks->fetch_assoc()): ?>
        <div class="feedback-item r<?= $fb['rating'] ?>">
          <div class="feedback-meta">
            <span class="feedback-name">&#x1F464; <?= htmlspecialchars($fb['player_name']) ?></span>
            <span class="feedback-stars">
              <?= str_repeat('&#x2605;', $fb['rating']) . str_repeat('&#x2606;', 5-$fb['rating']) ?>
            </span>
          </div>
          <div class="feedback-sport">
            <?= $fb['sport'] ? '&#x1F3C5; '.htmlspecialchars($fb['sport']) : '&#x1F310; General' ?>
          </div>
          <span class="feedback-cat"><?= htmlspecialchars($fb['category']) ?></span>
          <div class="feedback-comment">"<?= htmlspecialchars($fb['comments']) ?>"</div>
          <div class="feedback-footer">
            <span class="feedback-date">&#x1F4C5; <?= date('d M Y, h:i A', strtotime($fb['submitted_at'])) ?></span>
            <button class="btn-del"
              onclick="if(confirm('Delete this feedback?')) window.location='feedback.php?delete_fb=<?= $fb['id'] ?>'">
              &#x1F5D1; Delete
            </button>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php else: ?>
        <div class="no-data">&#x1F4AC; No feedback found for the selected filters.</div>
      <?php endif; ?>
    </div>

  </div><!-- /view-tab -->

</div><!-- /container -->

<script>
// ── Tab switching ─────────────────────────────────────────────────
function showTab(id, btn) {
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  btn.classList.add('active');
}
<?php if(isset($_GET['tab']) && $_GET['tab']=='view'): ?>
showTab('view-tab', document.getElementById('tab_view_btn'));
<?php endif; ?>
<?php if($messageType==='success'): ?>
// After successful submit, stay on submit tab but could switch to view
<?php endif; ?>

// ── Star hint ─────────────────────────────────────────────────────
const starHints = {5:'Excellent! &#x1F929;', 4:'Good &#x1F60A;', 3:'Average &#x1F610;', 2:'Poor &#x1F614;', 1:'Very Poor &#x1F622;'};
function updateStarHint(r) {
  const hint = document.getElementById('star_hint');
  hint.innerHTML = starHints[r];
  hint.style.color = r >= 4 ? '#27ae60' : r === 3 ? '#f39c12' : '#e74c3c';
  document.getElementById('star_err').classList.remove('show');
}

// ── Char counter ──────────────────────────────────────────────────
function updateCharCount() {
  const ta  = document.getElementById('f_comments');
  const num = document.getElementById('char_num');
  num.textContent = ta.value.length;
  num.style.color = ta.value.length > 900 ? '#e74c3c' : ta.value.length >= 10 ? '#27ae60' : '#aaa';
  const ok = ta.value.trim().length >= 10;
  ta.classList.toggle('err', !ok && ta.value.length > 0);
  ta.classList.toggle('ok', ok);
  document.getElementById('err_comments').classList.toggle('show', !ok && ta.value.length > 0);
}
// Init char count if value present
document.addEventListener('DOMContentLoaded', updateCharCount);

// ── Live field validation ─────────────────────────────────────────
document.getElementById('f_name').addEventListener('input', function() {
  const ok = /^[A-Za-z\s]{3,}$/.test(this.value.trim());
  this.classList.toggle('err', !ok && this.value.length > 0);
  this.classList.toggle('ok', ok);
  document.getElementById('err_name').classList.toggle('show', !ok && this.value.length > 0);
});
document.getElementById('f_email').addEventListener('input', function() {
  const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());
  this.classList.toggle('err', !ok && this.value.length > 0);
  this.classList.toggle('ok', ok);
  document.getElementById('err_email').classList.toggle('show', !ok && this.value.length > 0);
});
document.getElementById('f_cat').addEventListener('change', function() {
  const ok = this.value !== '';
  this.classList.toggle('err', !ok);
  this.classList.toggle('ok', ok);
  document.getElementById('err_cat').classList.toggle('show', !ok);
});

// ── Submit validation ─────────────────────────────────────────────
function validateFeedback() {
  let valid = true;

  // Name
  const name = document.getElementById('f_name').value.trim();
  const nameOk = /^[A-Za-z\s]{3,}$/.test(name);
  document.getElementById('f_name').classList.toggle('err', !nameOk);
  document.getElementById('f_name').classList.toggle('ok', nameOk);
  document.getElementById('err_name').classList.toggle('show', !nameOk);
  if (!nameOk) valid = false;

  // Email
  const email = document.getElementById('f_email').value.trim();
  const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  document.getElementById('f_email').classList.toggle('err', !emailOk);
  document.getElementById('f_email').classList.toggle('ok', emailOk);
  document.getElementById('err_email').classList.toggle('show', !emailOk);
  if (!emailOk) valid = false;

  // Category
  const cat = document.getElementById('f_cat').value;
  const catOk = cat !== '';
  document.getElementById('f_cat').classList.toggle('err', !catOk);
  document.getElementById('f_cat').classList.toggle('ok', catOk);
  document.getElementById('err_cat').classList.toggle('show', !catOk);
  if (!catOk) valid = false;

  // Rating
  const rated = document.querySelector('input[name="rating"]:checked');
  document.getElementById('star_err').classList.toggle('show', !rated);
  if (!rated) valid = false;

  // Comments
  const comments = document.getElementById('f_comments').value.trim();
  const commentsOk = comments.length >= 10;
  document.getElementById('f_comments').classList.toggle('err', !commentsOk);
  document.getElementById('f_comments').classList.toggle('ok', commentsOk);
  document.getElementById('err_comments').classList.toggle('show', !commentsOk);
  if (!commentsOk) valid = false;

  if (!valid) {
    document.getElementById('feedbackForm').scrollIntoView({behavior:'smooth'});
  }
  return valid;
}
</script>
</body>
</html>
<?php $conn->close(); ?>
