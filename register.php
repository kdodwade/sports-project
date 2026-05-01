<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: registration.html"); exit();
}

$conn = new mysqli("localhost","root","","sports_academy");
if ($conn->connect_error) die("DB Error: ".$conn->connect_error);

// ── Collect inputs ────────────────────────────────────────────────
$full_name = trim($_POST['full_name'] ?? '');
$age       = intval($_POST['age']       ?? 0);
$gender    = trim($_POST['gender']      ?? '');
$phone     = trim($_POST['phone']       ?? '');
$email     = trim($_POST['email']       ?? '');
$sports    = $_POST['sports']           ?? [];  // array of selected sports

// ── Server-side validation ────────────────────────────────────────
$errors = [];
$allowed_sports = ['Cricket','Football','Hockey','Basketball','Carrom','Chess','Table Tennis'];

if (!preg_match('/^[A-Za-z\s]{3,100}$/', $full_name))
    $errors[] = "Full name must be 3+ letters, no numbers or symbols.";
if ($age < 5 || $age > 60)
    $errors[] = "Age must be between 5 and 60.";
if (!in_array($gender, ['Male','Female','Other']))
    $errors[] = "Please select a valid gender.";
if (!preg_match('/^[6-9][0-9]{9}$/', $phone))
    $errors[] = "Phone must be 10 digits starting with 6, 7, 8 or 9.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email,'@gmail.com'))
    $errors[] = "Email must be a valid @gmail.com address.";
if (empty($sports))
    $errors[] = "Please select at least one sport.";
else {
    foreach ($sports as $s) {
        if (!in_array($s, $allowed_sports))
            $errors[] = "Invalid sport selected: ".htmlspecialchars($s);
    }
}

// Duplicate email check
if (empty($errors)) {
    $esc_email = $conn->real_escape_string($email);
    $check = $conn->query("SELECT id FROM players WHERE email='$esc_email'");
    if ($check && $check->num_rows > 0)
        $errors[] = "This email is already registered. Please use a different email.";
}

// ── Show errors ───────────────────────────────────────────────────
if (!empty($errors)) {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>
    <title>Registration Error</title>
    <link rel='stylesheet' href='style.css'>
    <style>body{background:linear-gradient(135deg,#1e3c72,#2a5298);min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .err-card{background:white;border-radius:16px;padding:36px;max-width:500px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.3);}</style>
    </head><body>
    <div class='err-card'>
      <h2 style='color:#e74c3c;margin-bottom:16px;'>&#x274C; Registration Error</h2>
      <ul style='padding-left:18px;color:#721c24;line-height:1.9;font-size:.92rem;'>";
    foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>";
    echo "</ul>
      <a href='registration.html' style='display:inline-block;margin-top:20px;background:#1e3c72;color:white;padding:10px 24px;border-radius:8px;font-weight:700;text-decoration:none;'>&#x2190; Go Back</a>
    </div></body></html>";
    $conn->close(); exit();
}

// ── Insert player ─────────────────────────────────────────────────
$stmt = $conn->prepare("INSERT INTO players (full_name,age,gender,phone,email) VALUES (?,?,?,?,?)");
$stmt->bind_param("sisss", $full_name, $age, $gender, $phone, $email);

if (!$stmt->execute()) {
    echo "Registration failed: ".htmlspecialchars($stmt->error);
    $conn->close(); exit();
}
$player_id = $conn->insert_id;
$stmt->close();

// ── Link each sport in player_sports ─────────────────────────────
$sports_enrolled = [];
$sports_failed   = [];

foreach ($sports as $sport_name) {
    $esc = $conn->real_escape_string($sport_name);
    $sr  = $conn->query("SELECT id FROM sports WHERE sport_name='$esc' LIMIT 1");
    if ($sr && $sr->num_rows > 0) {
        $sport_id = $sr->fetch_assoc()['id'];
        $ins = $conn->prepare("INSERT IGNORE INTO player_sports (player_id, sport_id) VALUES (?,?)");
        $ins->bind_param("ii", $player_id, $sport_id);
        $ins->execute();
        $ins->close();
        $sports_enrolled[] = $sport_name;
    } else {
        // Sport not in DB yet — store name in a fallback way
        $sports_enrolled[] = $sport_name;
    }
}

$conn->close();

// ── Success page ──────────────────────────────────────────────────
$sport_list   = implode(', ', $sports_enrolled);
$sport_count  = count($sports_enrolled);
$sport_badges = implode(' ', array_map(fn($s) => "<span style='display:inline-block;background:#1e3c72;color:white;padding:4px 14px;border-radius:50px;font-size:.8rem;font-weight:700;margin:3px;'>$s</span>", $sports_enrolled));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration Successful — Sports Academy</title>
<link rel="stylesheet" href="style.css">
<style>
body{background:linear-gradient(135deg,#1e3c72,#2a5298);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.success-card{background:white;border-radius:18px;padding:40px 36px;max-width:520px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.tick{font-size:4rem;display:block;margin-bottom:16px;animation:pop .5s ease;}
@keyframes pop{0%{transform:scale(0);}80%{transform:scale(1.2);}100%{transform:scale(1);}}
.success-card h2{color:var(--green);font-size:1.7rem;margin-bottom:8px;}
.detail-box{background:#f7f9fc;border-radius:12px;padding:18px 22px;margin:20px 0;text-align:left;}
.dr{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:.88rem;}
.dr:last-child{border:none;}
.dr .k{color:var(--muted);font-weight:600;}
.dr .v{font-weight:700;}
.sport-tags{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;}
</style>
</head>
<body>
<div class="success-card">
  <span class="tick">&#x1F389;</span>
  <h2>Registration Successful!</h2>
  <p style="color:var(--muted);margin-bottom:4px;">Welcome to Sports Academy, <strong><?= htmlspecialchars($full_name) ?></strong>!</p>
  <p style="color:var(--muted);font-size:.88rem;">You have been enrolled in <?= $sport_count ?> sport<?= $sport_count>1?'s':'' ?>.</p>

  <div class="detail-box">
    <div class="dr"><span class="k">Name</span><span class="v"><?= htmlspecialchars($full_name) ?></span></div>
    <div class="dr"><span class="k">Age</span><span class="v"><?= $age ?></span></div>
    <div class="dr"><span class="k">Gender</span><span class="v"><?= htmlspecialchars($gender) ?></span></div>
    <div class="dr"><span class="k">Phone</span><span class="v"><?= htmlspecialchars($phone) ?></span></div>
    <div class="dr"><span class="k">Email</span><span class="v"><?= htmlspecialchars($email) ?></span></div>
    <div class="dr">
      <span class="k">Enrolled Sports</span>
      <span class="v"><div class="sport-tags"><?= $sport_badges ?></div></span>
    </div>
  </div>

  <p style="font-size:.82rem;color:var(--muted);margin-bottom:20px;">
    &#x2139; Please complete your fee payment to confirm your enrollment.
  </p>

  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
    <a href="payment.php" class="btn btn-success">&#x1F4B3; Pay Fees Now</a>
    <a href="home.html"   class="btn btn-outline">&#x1F3E0; Go to Home</a>
  </div>
</div>
</body>
</html>
