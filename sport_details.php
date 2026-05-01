<?php
ini_set('display_errors',1); error_reporting(E_ALL);
$conn = new mysqli("localhost","root","","sports_academy");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: sports.php"); exit(); }

$res   = $conn->query("SELECT * FROM sports WHERE id=$id");
$sport = $res ? $res->fetch_assoc() : null;
if (!$sport) { header("Location: sports.php"); exit(); }

$coach_res    = $conn->query("SELECT * FROM coaches WHERE LOWER(sport)=LOWER('".$sport['sport_name']."') LIMIT 1");
$coach        = $coach_res ? $coach_res->fetch_assoc() : null;
$players_res  = $conn->query("SELECT COUNT(*) as c FROM player_sports ps JOIN sports s ON ps.sport_id=s.id WHERE LOWER(s.sport_name)=LOWER('".$sport['sport_name']."')");
$player_count = $players_res ? intval($players_res->fetch_assoc()['c']) : 0;

$sport_data = [
  'Cricket'      => ['icon'=>'&#x1F3CF;','color'=>'#27ae60','desc'=>'Cricket is a bat-and-ball sport played between two teams of eleven players. Our program covers batting, bowling, fielding, and match strategy for all skill levels.','benefits'=>['Improves hand-eye coordination','Builds team spirit and strategy','Enhances physical fitness','Develops patience and concentration','Improves decision-making under pressure'],'batch'=>['Morning Batch — 6:00 AM to 8:00 AM','Evening Batch — 5:00 PM to 7:00 PM'],'level'=>['Beginner','Intermediate','Advanced']],
  'Football'     => ['icon'=>'&#x26BD;','color'=>'#2980b9','desc'=>'Football is the world\'s most popular sport. Our program focuses on dribbling, passing, shooting, defending, and tactical gameplay for players of all ages.','benefits'=>['Full body cardiovascular workout','Develops coordination and agility','Builds teamwork and communication','Improves speed and endurance','Enhances strategic thinking'],'batch'=>['Morning Batch — 6:00 AM to 8:00 AM','Evening Batch — 5:00 PM to 7:00 PM'],'level'=>['Beginner','Intermediate','Advanced']],
  'Hockey'       => ['icon'=>'&#x1F3D1;','color'=>'#8e44ad','desc'=>'Field hockey is a fast-paced team sport requiring skill, speed, and tactics. Our coaches train players in stick handling, passing, and competitive gameplay.','benefits'=>['Improves reflexes and reaction time','Enhances lower body strength','Builds discipline and focus','Develops strategic thinking','Boosts cardiovascular health'],'batch'=>['Morning Batch — 6:00 AM to 8:00 AM','Evening Batch — 5:00 PM to 7:00 PM'],'level'=>['Beginner','Intermediate','Advanced']],
  'Basketball'   => ['icon'=>'&#x1F3C0;','color'=>'#e67e22','desc'=>'Basketball is a high-energy court sport that builds athleticism, coordination, and teamwork. We train players in dribbling, shooting, passing, and defense.','benefits'=>['Improves jumping and vertical leap','Builds upper and lower body strength','Enhances spatial awareness','Develops quick decision-making','Promotes team collaboration'],'batch'=>['Morning Batch — 6:00 AM to 8:00 AM','Evening Batch — 5:00 PM to 7:00 PM'],'level'=>['Beginner','Intermediate','Advanced']],
  'Table Tennis' => ['icon'=>'&#x1F3D3;','color'=>'#e74c3c','desc'=>'Table Tennis is a precision sport that sharpens reflexes, focus, and coordination. Training covers grip, strokes, spin techniques, and match tactics.','benefits'=>['Improves reflexes and reaction speed','Sharpens mental focus','Low-impact, joint-friendly exercise','Develops hand-eye coordination','Increases concentration span'],'batch'=>['Morning Batch — 8:00 AM to 10:00 AM','Evening Batch — 4:00 PM to 6:00 PM'],'level'=>['Beginner','Intermediate','Advanced']],
  'Chess'        => ['icon'=>'&#x265F;','color'=>'#2c3e50','desc'=>'Chess is the ultimate game of strategy and intelligence. Our program covers openings, middle game tactics, endgame techniques, and competitive tournament preparation.','benefits'=>['Improves logical and critical thinking','Enhances memory and concentration','Develops patience and planning skills','Boosts academic performance','Builds problem-solving ability'],'batch'=>['Morning Batch — 9:00 AM to 11:00 AM','Evening Batch — 4:00 PM to 6:00 PM'],'level'=>['Beginner','Intermediate','Advanced']],
  'Carrom'       => ['icon'=>'&#x1F3AF;','color'=>'#f39c12','desc'=>'Carrom is a popular indoor precision sport played across India. Training focuses on striking techniques, angles, board positioning, and match tactics.','benefits'=>['Improves precision and aim','Enhances concentration and patience','Develops strategic thinking','Social and family-friendly sport','Low physical impact, suitable for all ages'],'batch'=>['Morning Batch — 9:00 AM to 11:00 AM','Evening Batch — 4:00 PM to 6:00 PM'],'level'=>['Beginner','Intermediate']],
];
$meta = $sport_data[$sport['sport_name']] ?? ['icon'=>'&#x1F3C5;','color'=>'#1e3c72','desc'=>'Professional coaching and structured training for this sport is available at our academy.','benefits'=>['Physical fitness','Team spirit','Skill development','Discipline'],'batch'=>['Morning Batch','Evening Batch'],'level'=>['Beginner','Intermediate','Advanced']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($sport['sport_name']) ?> — Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
.sport-hero{background:linear-gradient(135deg,<?=$meta['color']?>ee,<?=$meta['color']?>99);color:white;padding:52px 40px;text-align:center;position:relative;overflow:hidden;}
.sport-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}
.sport-hero .big-icon{font-size:5rem;display:block;margin-bottom:14px;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.3));}
.sport-hero h1{font-size:2.4rem;font-weight:800;margin-bottom:10px;text-shadow:0 2px 10px rgba(0,0,0,0.2);}
.sport-hero .desc{font-size:1rem;opacity:.9;max-width:580px;margin:0 auto 20px;line-height:1.6;}
.hero-chips{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
.hero-chip{background:rgba(255,255,255,.2);backdrop-filter:blur(4px);border:1px solid rgba(255,255,255,.3);padding:6px 18px;border-radius:50px;font-size:.82rem;font-weight:600;}

.detail-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:22px;align-items:start;}
@media(max-width:820px){.detail-grid{grid-template-columns:1fr;}}

.benefit-list{list-style:none;padding:0;margin:0;}
.benefit-list li{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5;font-size:.9rem;color:#444;}
.benefit-list li:last-child{border:none;}
.benefit-icon{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#d4edda,#a8e6c0);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.85rem;}

.batch-card{background:#f7f9fc;border-radius:10px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:12px;border-left:4px solid var(--blue2);}
.batch-card .b-icon{font-size:1.4rem;}
.batch-card .b-text{font-weight:600;font-size:.9rem;color:var(--text);}
.batch-card .b-sub{font-size:.78rem;color:var(--muted);}

.level-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;}
.level-badge{padding:8px 20px;border-radius:50px;font-size:.82rem;font-weight:700;display:flex;align-items:center;gap:6px;}
.level-badge.b{background:#d4edda;color:#155724;}
.level-badge.i{background:#fff3cd;color:#856404;}
.level-badge.a{background:#f8d7da;color:#721c24;}

/* Sidebar */
.fee-hero-card{background:linear-gradient(135deg,var(--blue),var(--blue2));color:white;border-radius:16px;padding:26px;text-align:center;margin-bottom:16px;}
.fee-hero-card .f-label{font-size:.82rem;opacity:.8;letter-spacing:.5px;margin-bottom:6px;}
.fee-hero-card .f-amount{font-size:2.8rem;font-weight:800;line-height:1;}
.fee-hero-card .f-sub{font-size:.78rem;opacity:.7;margin-top:6px;}
.fee-hero-card .f-reg{background:rgba(255,255,255,.15);border-radius:8px;padding:8px 12px;margin-top:12px;font-size:.8rem;}

.mini-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
.mini-stat{background:white;border-radius:10px;padding:14px 10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.mini-stat .mn{font-size:1.6rem;font-weight:800;color:var(--blue);}
.mini-stat .ml{font-size:.72rem;color:var(--muted);margin-top:2px;}

.calc-box{background:linear-gradient(135deg,#f0f7ff,#e8effd);border:2px solid #c7d9f5;border-radius:14px;padding:20px;}
.calc-box h4{color:var(--blue);font-weight:700;margin-bottom:14px;font-size:.95rem;}
.fee-breakdown{background:white;border-radius:10px;padding:14px;margin:12px 0;display:none;}
.fee-br-row{display:flex;justify-content:space-between;padding:7px 0;font-size:.85rem;border-bottom:1px dashed #e8e8e8;}
.fee-br-row:last-child{border:none;font-weight:700;font-size:1rem;color:var(--blue);padding-top:10px;}
.fee-br-row:last-child span:last-child{color:var(--green);font-size:1.1rem;}

.coach-profile{display:flex;align-items:center;gap:14px;background:#f7f9fc;border-radius:12px;padding:16px;margin-bottom:12px;}
.coach-profile img{width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid white;box-shadow:0 3px 10px rgba(0,0,0,.12);}
.coach-profile .cp-name{font-weight:700;color:var(--blue);font-size:1rem;margin-bottom:2px;}
.coach-profile .cp-exp{font-size:.8rem;color:var(--muted);}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="sport-hero">
  <span class="big-icon"><?= $meta['icon'] ?></span>
  <h1><?= htmlspecialchars($sport['sport_name']) ?></h1>
  <p class="desc"><?= $meta['desc'] ?></p>
  <div class="hero-chips">
    <span class="hero-chip">&#x23F0; <?= htmlspecialchars($sport['timings']) ?></span>
    <span class="hero-chip">&#x1F4B0; &#8377;<?= number_format($sport['fees']) ?>/month</span>
    <span class="hero-chip">&#x1F465; <?= $player_count ?> Players</span>
    <?php if($coach): ?>
    <span class="hero-chip">&#x1F3C6; <?= htmlspecialchars($coach['coach_name']) ?></span>
    <?php endif; ?>
  </div>
</div>

<div class="container">
  <div style="margin-bottom:14px;">
    <a href="sports.php" style="color:var(--blue2);font-size:.88rem;display:inline-flex;align-items:center;gap:6px;">
      &#x2190; Back to All Sports
    </a>
  </div>

  <div class="detail-grid">

    <!-- LEFT COLUMN -->
    <div>

      <!-- Benefits -->
      <div class="card">
        <div class="card-title">&#x2705; Benefits of <?= htmlspecialchars($sport['sport_name']) ?></div>
        <ul class="benefit-list">
          <?php foreach($meta['benefits'] as $b): ?>
          <li>
            <span class="benefit-icon">&#x2714;</span>
            <?= htmlspecialchars($b) ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Batches -->
      <div class="card">
        <div class="card-title">&#x23F0; Training Batches</div>
        <?php foreach($meta['batch'] as $i=>$b): ?>
        <div class="batch-card">
          <div class="b-icon"><?= $i===0 ? '&#x1F305;' : '&#x1F307;' ?></div>
          <div>
            <div class="b-text"><?= htmlspecialchars($b) ?></div>
            <div class="b-sub"><?= $i===0 ? 'Morning session — fresh start to the day' : 'Evening session — after school / work' ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Training Levels -->
      <div class="card">
        <div class="card-title">&#x1F4CA; Training Levels Offered</div>
        <div class="level-row">
          <?php foreach($meta['level'] as $l):
            $cls = $l==='Beginner'?'b':($l==='Intermediate'?'i':'a');
            $icon= $l==='Beginner'?'&#x1F331;':($l==='Intermediate'?'&#x1F4AA;':'&#x1F525;');
          ?>
          <span class="level-badge <?=$cls?>"><?=$icon?> <?= $l ?></span>
          <?php endforeach; ?>
        </div>
        <p style="margin-top:14px;font-size:.85rem;color:var(--muted);line-height:1.6;">
          Players are assessed on joining and placed in the appropriate batch. Progress evaluations are held every 3 months to move players to the next level.
        </p>
      </div>

      <!-- Coach section -->
      <?php if($coach): ?>
      <div class="card">
        <div class="card-title">&#x1F3C6; Your Coach</div>
        <div class="coach-profile">
          <img src="<?= htmlspecialchars($coach['photo'] ?: 'images/coaches/coach1.jpg') ?>"
               alt="<?= htmlspecialchars($coach['coach_name']) ?>"
               onerror="this.src='images/coaches/coach1.jpg'">
          <div>
            <div class="cp-name"><?= htmlspecialchars($coach['coach_name']) ?></div>
            <div class="cp-exp">&#x1F4C5; <?= htmlspecialchars($coach['experience']) ?> of coaching experience</div>
            <div class="cp-exp" style="margin-top:4px;">&#x26BD; Specialisation: <?= htmlspecialchars($coach['sport']) ?></div>
            <div style="margin-top:10px;">
              <a href="coach_details.php?id=<?= $coach['id'] ?>" class="btn btn-outline btn-sm">
                &#x1F464; View Full Profile
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT COLUMN (Sidebar) -->
    <div>

      <!-- Fee card -->
      <div class="fee-hero-card">
        <div class="f-label">MONTHLY FEE</div>
        <div class="f-amount">&#8377;<?= number_format($sport['fees']) ?></div>
        <div class="f-sub">per player per month</div>
        <div class="f-reg">&#x2139; &#8377;500 one-time registration fee on joining</div>
      </div>

      <!-- Mini stats -->
      <div class="mini-stats">
        <div class="mini-stat">
          <div class="mn"><?= $player_count ?></div>
          <div class="ml">Players Enrolled</div>
        </div>
        <div class="mini-stat">
          <div class="mn"><?= count($meta['batch']) ?></div>
          <div class="ml">Daily Batches</div>
        </div>
        <div class="mini-stat">
          <div class="mn"><?= count($meta['level']) ?></div>
          <div class="ml">Skill Levels</div>
        </div>
        <div class="mini-stat">
          <div class="mn">5&#x2605;</div>
          <div class="ml">Coach Rating</div>
        </div>
      </div>

      <!-- Fee Calculator -->
      <div class="calc-box">
        <h4>&#x1F4B0; Fee Calculator</h4>
        <div class="form-group">
          <label class="form-label">Select Number of Months</label>
          <select class="form-control" id="months_sel" onchange="calcFee()">
            <option value="">-- Choose months --</option>
            <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?=$m?>"><?=$m?> Month<?=$m>1?'s':''?> <?=$m==12?'(Full Year)':''?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="fee-breakdown" id="fee_breakdown">
          <div class="fee-br-row"><span>Monthly Fee</span><span>&#8377;<?= number_format($sport['fees']) ?></span></div>
          <div class="fee-br-row"><span id="br_months">Months</span><span id="br_months_val">—</span></div>
          <div class="fee-br-row"><span>Registration (one-time)</span><span>&#8377;500</span></div>
          <div class="fee-br-row"><span>Total Payable</span><span id="br_total">—</span></div>
        </div>
        <a href="registration.html" class="btn btn-success" style="width:100%;justify-content:center;margin-top:12px;padding:12px;">
          &#x270F; Register Now
        </a>
        <a href="payment.php" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;padding:12px;">
          &#x1F4B3; Pay Fees Online
        </a>
      </div>

    </div>
  </div>
</div>

<footer class="site-footer">
  <p>&copy; 2025 <strong>Sports Academy</strong> — All Rights Reserved</p>
</footer>

<script>
const monthlyFee = <?= floatval($sport['fees']) ?>;
function calcFee() {
  const m = parseInt(document.getElementById('months_sel').value);
  const box = document.getElementById('fee_breakdown');
  if (!m) { box.style.display='none'; return; }
  const total = (monthlyFee * m) + 500;
  document.getElementById('br_months').textContent = m + (m===1?' Month':' Months');
  document.getElementById('br_months_val').textContent = '× ₹' + monthlyFee.toLocaleString('en-IN');
  document.getElementById('br_total').textContent = '₹' + total.toLocaleString('en-IN');
  box.style.display = 'block';
}
</script>
<?php $conn->close(); ?>
</body>
</html>
