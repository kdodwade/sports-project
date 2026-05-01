<?php
ini_set('display_errors',1); error_reporting(E_ALL);
$conn = new mysqli("localhost","root","","sports_academy");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$sports = $conn->query("
  SELECT s.*, c.coach_name, c.photo as coach_photo
  FROM sports s
  LEFT JOIN coaches c ON LOWER(s.sport_name)=LOWER(c.sport)
  ORDER BY s.id
");

$sport_icons = [
  'cricket'=>'&#x1F3CF;','football'=>'&#x26BD;','hockey'=>'&#x1F3D1;',
  'basketball'=>'&#x1F3C0;','table tennis'=>'&#x1F3D3;','chess'=>'&#x265F;','carrom'=>'&#x1F3AF;'
];
$sport_colors = [
  'cricket'=>'#27ae60','football'=>'#2980b9','hockey'=>'#8e44ad',
  'basketball'=>'#e67e22','table tennis'=>'#e74c3c','chess'=>'#34495e','carrom'=>'#f39c12'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sports Academy — Sports</title>
<link rel="stylesheet" href="style.css">
<style>
.sports-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;}
.sport-card{background:white;border-radius:16px;overflow:hidden;box-shadow:var(--shadow);transition:all .25s;}
.sport-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-lg);}
.sport-card-top{padding:28px 24px 20px;text-align:center;color:white;position:relative;}
.sport-icon{font-size:3rem;display:block;margin-bottom:12px;}
.sport-card-top h3{font-size:1.25rem;font-weight:700;margin:0;}
.sport-card-body{padding:20px 24px;}
.sport-detail{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:.88rem;}
.sport-detail:last-child{border:none;}
.sport-detail .key{color:var(--muted);font-weight:600;min-width:80px;font-size:.8rem;}
.sport-detail .val{font-weight:700;color:var(--text);}
.fee-badge{display:inline-block;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:4px 14px;border-radius:50px;font-size:.82rem;font-weight:700;margin-top:4px;}
.sport-card-footer{padding:14px 24px;border-top:1px solid #f0f0f0;}
.no-sports{text-align:center;padding:60px;color:var(--muted);}
.no-sports .icon{font-size:3rem;margin-bottom:12px;}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="page-hero">
  <h1>&#x26BD; Sports We Offer</h1>
  <p>World-class coaching across 7 sports — indoor and outdoor</p>
</div>

<div class="container">
  <?php if ($sports && $sports->num_rows > 0): ?>
  <div class="sports-grid">
  <?php while($row=$sports->fetch_assoc()):
    $key   = strtolower($row['sport_name']);
    $icon  = $sport_icons[$key]  ?? '&#x1F3C5;';
    $color = $sport_colors[$key] ?? '#1e3c72';
  ?>
    <div class="sport-card">
      <a href="sport_details.php?id=<?= $row['id'] ?>" style="text-decoration:none;">
      <div class="sport-card-top" style="background:linear-gradient(135deg,<?=$color?>,<?=$color?>cc);">
        <span class="sport-icon"><?=$icon?></span>
        <h3><?= htmlspecialchars($row['sport_name']) ?></h3>
      </div>
      </a>
      <div class="sport-card-body">
        <div class="sport-detail">
          <span class="key">&#x1F4B0; Fees</span>
          <span class="val"><span class="fee-badge">&#8377;<?= number_format($row['fees']) ?>/month</span></span>
        </div>
        <div class="sport-detail">
          <span class="key">&#x23F0; Timings</span>
          <span class="val"><?= htmlspecialchars($row['timings']) ?></span>
        </div>
        <div class="sport-detail">
          <span class="key">&#x1F3C6; Coach</span>
          <span class="val"><?= htmlspecialchars($row['coach_name'] ?? 'To Be Assigned') ?></span>
        </div>
      </div>
      <div class="sport-card-footer">
        <a href="sport_details.php?id=<?= $row['id'] ?>" class="btn btn-outline" style="width:100%;justify-content:center;margin-bottom:8px;">
          &#x1F50D; View Details
        </a>
        <a href="registration.html" class="btn btn-primary" style="width:100%;justify-content:center;">
          &#x270F; Register for <?= htmlspecialchars($row['sport_name']) ?>
        </a>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="no-sports card">
    <div class="icon">&#x26BD;</div>
    <h3>No Sports Added Yet</h3>
    <p>Ask the admin to add sports from the admin dashboard.</p>
    <br><a href="admin.php" class="btn btn-primary">Go to Admin</a>
  </div>
  <?php endif; ?>
  <?php $conn->close(); ?>
</div>

<footer class="site-footer">
  <p>&copy; 2025 <strong>Sports Academy</strong> — All Rights Reserved</p>
</footer>
</body>
</html>
