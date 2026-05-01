<?php
$conn = new mysqli("localhost","root","","sports_academy");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);
$coaches = $conn->query("SELECT * FROM coaches ORDER BY sport, coach_name");

$sport_icons=['cricket'=>'&#x1F3CF;','football'=>'&#x26BD;','hockey'=>'&#x1F3D1;',
              'basketball'=>'&#x1F3C0;','table tennis'=>'&#x1F3D3;','chess'=>'&#x265F;','carrom'=>'&#x1F3AF;'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sports Academy — Coaches</title>
<link rel="stylesheet" href="style.css">
<style>
.coaches-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:24px;}
.coach-card{background:white;border-radius:16px;overflow:hidden;box-shadow:var(--shadow);transition:all .25s;text-align:center;}
.coach-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-lg);}
.coach-img-wrap{position:relative;height:220px;overflow:hidden;background:#f0f4f8;}
.coach-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
.coach-card:hover .coach-img-wrap img{transform:scale(1.05);}
.coach-sport-badge{position:absolute;top:12px;right:12px;background:rgba(30,60,114,.88);color:white;padding:4px 12px;border-radius:50px;font-size:.75rem;font-weight:700;}
.coach-body{padding:18px 16px 20px;}
.coach-name{font-size:1.1rem;font-weight:700;color:var(--blue);margin-bottom:4px;}
.coach-sport{font-size:.88rem;color:var(--muted);margin-bottom:10px;}
.exp-badge{display:inline-block;background:linear-gradient(135deg,#f0f7ff,#e8effd);color:var(--blue2);padding:5px 14px;border-radius:50px;font-size:.8rem;font-weight:700;border:1px solid #c7d9f5;}
.no-coaches{text-align:center;padding:60px;color:var(--muted);}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="page-hero">
  <h1>&#x1F3C6; Meet Our Coaches</h1>
  <p>Expert coaches dedicated to bringing out the best in every player</p>
</div>

<div class="container">
  <?php if ($coaches && $coaches->num_rows > 0): ?>
  <div class="coaches-grid">
  <?php while($row=$coaches->fetch_assoc()):
    $icon  = $sport_icons[strtolower($row['sport'])] ?? '&#x1F3C5;';
    $photo = !empty($row['photo']) ? htmlspecialchars($row['photo']) : 'images/coaches/coach1.jpg';
  ?>
    <a href="coach_details.php?id=<?= $row['id'] ?>" style="text-decoration:none;color:inherit;">
    <div class="coach-card">
      <div class="coach-img-wrap">
        <img src="<?=$photo?>" alt="<?= htmlspecialchars($row['coach_name']) ?>"
             onerror="this.src='images/coaches/coach1.jpg'">
        <span class="coach-sport-badge"><?=$icon?> <?= htmlspecialchars($row['sport']) ?></span>
      </div>
      <div class="coach-body">
        <div class="coach-name"><?= htmlspecialchars($row['coach_name']) ?></div>
        <div class="coach-sport"><?=$icon?> <?= htmlspecialchars($row['sport']) ?> Coach</div>
        <span class="exp-badge">&#x1F4C5; <?= htmlspecialchars($row['experience']) ?> experience</span>
        <div style="margin-top:12px;">
          <span style="display:inline-block;background:linear-gradient(135deg,#1e3c72,#2a5298);color:white;padding:7px 20px;border-radius:50px;font-size:.8rem;font-weight:700;">
            View Profile &#x2192;
          </span>
        </div>
      </div>
    </div>
    </a>
  <?php endwhile; ?>
  </div>
  <?php else: ?>
  <div class="card no-coaches">
    <div style="font-size:3rem;margin-bottom:12px;">&#x1F3C6;</div>
    <h3>No Coaches Added Yet</h3>
    <p>Ask the admin to add coaches from the admin panel.</p>
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
