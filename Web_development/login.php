<?php
require_once __DIR__ . '/config/init.php';
if (Auth::isLoggedIn()) {
    header('Location: ' . ($_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'user/index.php'));
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $auth   = new Auth();
        $result = $auth->login($email, $password);
        if ($result['success']) {
            header('Location: ' . ($result['role'] === 'admin' ? 'admin/index.php' : 'user/index.php'));
            exit;
        }
        $error = $result['message'];
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – DSRRM Clinic</title>
<link rel="stylesheet" href="css/frontpage.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Nunito',Arial,sans-serif}
  body{min-height:100vh;background:url('img/bestlink.jpg') no-repeat center center/cover;display:flex;flex-direction:column}
  .login-overlay{position:fixed;inset:0;background:rgba(0,20,50,.55);backdrop-filter:blur(3px)}
  .login-page{flex:1;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;z-index:1}
  .login-card{background:#fff;border-radius:24px;box-shadow:0 25px 60px rgba(0,0,0,.35);padding:50px 45px;width:100%;max-width:440px;animation:popIn .6s cubic-bezier(.22,1,.36,1) both}
  @keyframes popIn{from{opacity:0;transform:scale(.88) translateY(30px)}to{opacity:1;transform:scale(1) translateY(0)}}
  .login-logo{text-align:center;margin-bottom:30px}
  .login-logo img{height:64px;border-radius:50%;border:3px solid #0056b3;padding:2px}
  .login-logo h2{color:#003366;font-size:22px;font-weight:800;margin-top:10px}
  .login-logo p{color:#888;font-size:13px;margin-top:4px}
  .form-group{margin-bottom:18px}
  .form-group label{display:block;color:#444;font-size:13px;font-weight:700;margin-bottom:7px;text-transform:uppercase;letter-spacing:.6px}
  .input-wrap{position:relative}
  .input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aaa;font-size:15px}
  .input-wrap input{width:100%;padding:13px 14px 13px 42px;border:2px solid #e8eaf0;border-radius:12px;font-size:15px;color:#333;transition:.2s;background:#fafbff}
  .input-wrap input:focus{outline:none;border-color:#0056b3;background:#fff;box-shadow:0 0 0 4px rgba(0,86,179,.08)}
  .toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;font-size:15px;background:none;border:none;padding:0}
  .error-msg{background:#fff0f0;border:1px solid #ffcccc;border-radius:10px;padding:10px 14px;color:#c0392b;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
  .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,#0056b3,#003d80);color:#fff;border:none;border-radius:14px;font-size:16px;font-weight:800;cursor:pointer;transition:.3s;letter-spacing:.3px;margin-top:4px}
  .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,86,179,.35)}
  .login-footer{text-align:center;margin-top:22px;font-size:13px;color:#888}
  .login-footer a{color:#0056b3;font-weight:700;text-decoration:none}
  .back-link{display:inline-flex;align-items:center;gap:6px;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:10px 20px;background:rgba(255,255,255,.18);backdrop-filter:blur(8px);border-radius:30px;transition:.3s;position:relative;z-index:2;margin:20px;align-self:flex-start}
  .back-link:hover{background:rgba(255,255,255,.3)}
  .role-hint{text-align:center;padding:10px;background:#f0f7ff;border-radius:10px;font-size:12px;color:#0056b3;margin-bottom:16px}
</style>
</head>
<body>
<div class="login-overlay"></div>
<a href="frontpage.html" class="back-link"><i class="fa fa-arrow-left"></i> Back to Home</a>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="img/logo clinic.jpg" alt="DSRRM Logo">
      <h2>DSRRM CLINIC</h2>
      <p>Bestlink College of the Philippines</p>
    </div>
    <?php if ($error): ?>
    <div class="error-msg"><i class="fa fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="role-hint"><i class="fa fa-info-circle"></i> Default Admin — admin@dsrrm.edu.ph / password</div>
    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <div class="input-wrap">
          <i class="fa fa-envelope"></i>
          <input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fa fa-lock"></i>
          <input type="password" name="password" id="pwd" placeholder="Enter password" required>
          <button type="button" class="toggle-pass" onclick="togglePwd()"><i class="fa fa-eye" id="eyeIcon"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-login"><i class="fa fa-sign-in-alt"></i> &nbsp; Log In</button>
    </form>
    <div class="login-footer">Don't have an account? Contact the <a href="#">clinic admin</a>.</div>
  </div>
</div>
<script>
function togglePwd(){
  const p=document.getElementById('pwd'),i=document.getElementById('eyeIcon');
  p.type=p.type==='password'?'text':'password';
  i.className=p.type==='password'?'fa fa-eye':'fa fa-eye-slash';
}
</script>
</body>
</html>
