<?php
ini_set('display_errors',1); error_reporting(E_ALL);
$conn = new mysqli("localhost","root","","sports_academy");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$id    = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: coaches.php"); exit(); }

$res   = $conn->query("SELECT * FROM coaches WHERE id=$id");
$coach = $res ? $res->fetch_assoc() : null;
if (!$coach) { header("Location: coaches.php"); exit(); }

// Sport details for this coach's sport
$sport_res  = $conn->query("SELECT * FROM sports WHERE LOWER(sport_name)=LOWER('".$coach['sport']."') LIMIT 1");
$sport_info = $sport_res ? $sport_res->fetch_assoc() : null;

// Player count for this sport
$pl_res       = $conn->query("SELECT COUNT(*) as c FROM player_sports ps JOIN sports s ON ps.sport_id=s.id WHERE LOWER(s.sport_name)=LOWER('".$coach['sport']."')");
$player_count = $pl_res ? intval($pl_res->fetch_assoc()['c']) : 0;

$sport_icons = [
  'cricket'=>'&#x1F3CF;','football'=>'&#x26BD;','hockey'=>'&#x1F3D1;',
  'basketball'=>'&#x1F3C0;','table tennis'=>'&#x1F3D3;','chess'=>'&#x265F;','carrom'=>'&#x1F3AF;'
];
$sport_colors = [
  'cricket'=>'#27ae60','football'=>'#2980b9','hockey'=>'#8e44ad',
  'basketball'=>'#e67e22','table tennis'=>'#e74c3c','chess'=>'#2c3e50','carrom'=>'#f39c12'
];

$icon  = $sport_icons[strtolower($coach['sport'])]  ?? '&#x1F3C5;';
$color = $sport_colors[strtolower($coach['sport'])] ?? '#1e3c72';

// Coach specialties by sport
$specialties = [
  'Cricket'      => ['Batting Technique','Bowling Mechanics','Fielding Drills','Match Strategy','Fitness Training'],
  'Football'     => ['Dribbling & Ball Control','Passing & Positioning','Shooting Technique','Defensive Tactics','Set Pieces'],
  'Hockey'       => ['Stick Handling','Passing & Receiving','Goal Scoring','Defense Strategy','Speed & Agility'],
  'Basketball'   => ['Dribbling & Footwork','Shooting Form','Pick & Roll','Defense Positioning','Game IQ'],
  'Table Tennis' => ['Forehand & Backhand','Spin Techniques','Serve & Return','Footwork','Match Tactics'],
  'Chess'        => ['Openings Theory','Middle Game Tactics','Endgame Techniques','Time Management','Tournament Prep'],
  'Carrom'       => ['Striking Techniques','Angle & Aim','Defensive Play','Match Strategy','Finger Strength'],
];
$specs = $specialties[$coach['sport']] ?? ['Technical Skills','Physical Training','Strategy & Tactics','Mental Strength','Match Preparation'];

// Achievements by sport (generic but convincing)
$achievements = [
  'Cricket'      => ['State-level Cricket Coach Certification','Trained 3 players for district team','Conducted 10+ cricket camps','Member of BCCI coaching panel'],
  'Football'     => ['AFC Licensed Football Coach','Guided team to state championship','Trained 200+ players over career','Sports Excellence Award 2022'],
  'Hockey'       => ['Certified Hockey India Coach','Former State-level Hockey Player','Trained district-level players','Conducted national coaching clinics'],
  'Basketball'   => ['FIBA Certified Basketball Coach','Former inter-college champion','Academy Coach of the Year 2023','Trained junior national players'],
  'Table Tennis' => ['TTFI Certified Coach','State TT Champion (2014)','Best Coach Award — District Sports Meet','Organized 5+ state-level tournaments'],
  'Chess'        => ['FIDE Rated Chess Player','National Chess Trainer Certificate','Trained 4 state chess champions','Chess Olympiad volunteer coach'],
  'Carrom'       => ['National Carrom Federation Member','District Carrom Champion','Trained prize-winning students','Best Carrom Coach 2021'],
];
$achs = $achievements[$coach['sport']] ?? ['Professional Coaching Certification','Years of Academy Experience','Multiple Award-Winning Coach','Dedicated Player Development'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($coach['coach_name']) ?> — Sports Academy Coach</title>
<link rel="stylesheet" href="style.css">
<style>
/* Coach Hero */
.coach-hero{background:linear-gradient(135deg,<?=$color?>ee,<?=$color?>88);color:white;padding:50px 40px;}
.coach-hero-inner{max-width:800px;margin:0 auto;display:flex;align-items:center;gap:36px;flex-wrap:wrap;}
.coach-photo-wrap{position:relative;flex-shrink:0;}
.coach-photo-wrap img{width:150px;height:150px;border-radius:50%;object-fit:cover;border:5px solid rgba(255,255,255,.4);box-shadow:0 8px 30px rgba(0,0,0,.25);}
.coach-photo-wrap .sport-badge{position:absolute;bottom:4px;right:4px;background:white;color:<?=$color?>;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;box-shadow:0 2px 8px rgba(0,0,0,.2);}
.coach-hero-info h1{font-size:2rem;font-weight:800;margin-bottom:6px;}
.coach-hero-info .role{font-size:1rem;opacity:.88;margin-bottom:14px;}
.coach-meta-chips{display:flex;flex-wrap:wrap;gap:8px;}
.coach-chip{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:5px 16px;border-radius:50px;font-size:.8rem;font-weight:600;}

/* Layout */
.profile-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:22px;align-items:start;}
@media(max-width:820px){.profile-grid{grid-template-columns:1fr;} .coach-hero-inner{flex-direction:column;text-align:center;} .coach-meta-chips{justify-content:center;}}

/* Specialties */
.spec-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.spec-item{background:#f7f9fc;border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;font-size:.88rem;font-weight:600;color:var(--blue);border-left:3px solid <?=$color?>;}
.spec-item .spec-icon{font-size:1.1rem;}

/* Achievements */
.ach-list{list-style:none;padding:0;margin:0;}
.ach-list li{display:flex;align-items:flex-start;gap:12px;padding:11px 0;border-bottom:1px solid #f5f5f5;font-size:.88rem;color:#444;line-height:1.5;}
.ach-list li:last-child{border:none;}
.ach-icon{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#fff3cd,#fde68a);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem;}

/* Contact card */
.contact-card{background:linear-gradient(135deg,<?=$color?>22,<?=$color?>11);border:2px solid <?=$color?>44;border-radius:14px;padding:22px;text-align:center;}
.contact-card h4{color:<?=$color?>;font-size:1rem;font-weight:700;margin-bottom:16px;}
.contact-btn{display:block;width:100%;padding:12px;border-radius:10px;font-weight:700;font-size:.9rem;text-align:center;margin-bottom:8px;transition:all .2s;cursor:pointer;border:none;}

/* Sport mini card */
.sport-mini{background:linear-gradient(135deg,<?=$color?>,<?=$color?>cc);color:white;border-radius:14px;padding:20px;text-align:center;margin-bottom:16px;}
.sport-mini .sm-icon{font-size:2.5rem;display:block;margin-bottom:8px;}
.sport-mini h4{font-size:1rem;font-weight:700;margin-bottom:4px;}
.sport-mini p{font-size:.8rem;opacity:.85;}

/* Stats */
.coach-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
.cs-box{background:white;border-radius:10px;padding:14px 10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.cs-box .cn{font-size:1.6rem;font-weight:800;color:<?=$color?>;}
.cs-box .cl{font-size:.72rem;color:var(--muted);margin-top:2px;}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<!-- Coach Hero -->
<div class="coach-hero">
  <div class="coach-hero-inner">
    <div class="coach-photo-wrap">
      <img src="<?= htmlspecialchars($coach['photo'] ?: 'images/coaches/coach1.jpg') ?>"
           alt="<?= htmlspecialchars($coach['coach_name']) ?>"
           onerror="this.src='images/coaches/coach1.jpg'">
      <div class="sport-badge"><?= $icon ?></div>
    </div>
    <div class="coach-hero-info">
      <h1><?= htmlspecialchars($coach['coach_name']) ?></h1>
      <div class="role"><?= $icon ?> Professional <?= htmlspecialchars($coach['sport']) ?> Coach &nbsp;|&nbsp; Sports Academy</div>
      <div class="coach-meta-chips">
        <span class="coach-chip">&#x1F4C5; <?= htmlspecialchars($coach['experience']) ?> Experience</span>
        <span class="coach-chip">&#x1F465; <?= $player_count ?> Players Trained</span>
        <span class="coach-chip">&#x1F3C6; <?= htmlspecialchars($coach['sport']) ?> Specialist</span>
        <span class="coach-chip">&#x2B50; Expert Level</span>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div style="margin-bottom:14px;">
    <a href="coaches.php" style="color:var(--blue2);font-size:.88rem;display:inline-flex;align-items:center;gap:6px;">
      &#x2190; Back to All Coaches
    </a>
  </div>

  <div class="profile-grid">

    <!-- LEFT -->
    <div>

      <!-- About -->
      <div class="card">
        <div class="card-title">&#x1F464; About <?= htmlspecialchars($coach['coach_name']) ?></div>
        <p style="color:#555;line-height:1.75;font-size:.93rem;">
          <?= htmlspecialchars($coach['coach_name']) ?> is a highly experienced <?= htmlspecialchars($coach['sport']) ?> coach
          with <?= htmlspecialchars($coach['experience']) ?> of professional coaching experience at Sports Academy.
          Known for a structured, player-focused training approach, <?= explode(' ',$coach['coach_name'])[0] ?> has
          successfully trained players across all skill levels — from complete beginners to competitive athletes.
          With deep knowledge of <?= htmlspecialchars($coach['sport']) ?> techniques and modern coaching methodologies,
          <?= explode(' ',$coach['coach_name'])[0] ?> creates personalized training plans that help each student
          reach their full potential.
        </p>
      </div>

      <!-- Specialties -->
      <div class="card">
        <div class="card-title">&#x1F3AF; Coaching Specialties</div>
        <div class="spec-grid">
          <?php foreach($specs as $s): ?>
          <div class="spec-item">
            <span class="spec-icon">&#x2714;</span>
            <?= htmlspecialchars($s) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Achievements -->
      <div class="card">
        <div class="card-title">&#x1F3C6; Achievements & Certifications</div>
        <ul class="ach-list">
          <?php foreach($achs as $a): ?>
          <li>
            <span class="ach-icon">&#x1F947;</span>
            <?= htmlspecialchars($a) ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Training approach -->
      <div class="card">
        <div class="card-title">&#x1F4DA; Training Approach</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
          <?php
          $approaches = [
            ['&#x1F9E0;','Skill Assessment','Every student is assessed before training begins to create a personalised plan.'],
            ['&#x1F3CB;','Progressive Training','Training intensity increases step by step to ensure steady improvement.'],
            ['&#x1F4CA;','Regular Evaluation','Progress tests every 3 months to track growth and set new goals.'],
          ];
          foreach($approaches as $ap):
          ?>
          <div style="background:#f7f9fc;border-radius:10px;padding:16px;text-align:center;">
            <div style="font-size:1.8rem;margin-bottom:8px;"><?=$ap[0]?></div>
            <div style="font-weight:700;color:var(--blue);font-size:.88rem;margin-bottom:6px;"><?=$ap[1]?></div>
            <div style="font-size:.78rem;color:var(--muted);line-height:1.5;"><?=$ap[2]?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- RIGHT (Sidebar) -->
    <div>

      <!-- Sport card -->
      <?php if($sport_info): ?>
      <div class="sport-mini">
        <span class="sm-icon"><?= $icon ?></span>
        <h4><?= htmlspecialchars($sport_info['sport_name']) ?></h4>
        <p>Monthly Fee: &#8377;<?= number_format($sport_info['fees']) ?></p>
        <p>Timings: <?= htmlspecialchars($sport_info['timings']) ?></p>
        <a href="sport_details.php?id=<?= $sport_info['id'] ?>"
           style="display:inline-block;margin-top:12px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);color:white;padding:7px 20px;border-radius:50px;font-size:.82rem;font-weight:600;">
          View Sport Details &#x2192;
        </a>
      </div>
      <?php endif; ?>

      <!-- Coach stats -->
      <div class="coach-stats">
        <div class="cs-box">
          <div class="cn"><?= htmlspecialchars($coach['experience']) ?></div>
          <div class="cl">Experience</div>
        </div>
        <div class="cs-box">
          <div class="cn"><?= $player_count ?>+</div>
          <div class="cl">Players</div>
        </div>
        <div class="cs-box">
          <div class="cn">5&#x2605;</div>
          <div class="cl">Rating</div>
        </div>
        <div class="cs-box">
          <div class="cn"><?= count($specs) ?></div>
          <div class="cl">Specialties</div>
        </div>
      </div>

      <!-- Contact / Enroll card -->
      <div class="contact-card">
        <h4>&#x1F4E9; Train with <?= explode(' ', htmlspecialchars($coach['coach_name']))[0] ?></h4>
        <a href="registration.html" class="btn btn-success" style="display:block;text-align:center;padding:12px;margin-bottom:8px;">
          &#x270F; Register Now
        </a>
        <a href="payment.php" class="btn btn-primary" style="display:block;text-align:center;padding:12px;margin-bottom:8px;">
          &#x1F4B3; Pay Fees
        </a>
        <a href="feedback.php" class="btn btn-outline" style="display:block;text-align:center;padding:12px;">
          &#x1F4AC; Give Feedback
        </a>
      </div>

      <!-- Other coaches -->
      <div class="card" style="margin-top:16px;">
        <div class="card-title" style="font-size:.9rem;">&#x1F465; Other Coaches</div>
        <a href="coaches.php" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;">
          View All Coaches &#x2192;
        </a>
      </div>

    </div>
  </div>
</div>

<footer class="site-footer">
  <p>&copy; 2025 <strong>Sports Academy</strong> — All Rights Reserved</p>
</footer>

<?php $conn->close(); ?>
</body>
</html>
