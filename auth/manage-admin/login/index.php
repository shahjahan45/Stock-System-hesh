<?php
require dirname(__DIR__,3).'/includes/bootstrap.php';
if(user()) redirect('dashboard.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$email=strtolower(trim($_POST['email']??''));$password=(string)($_POST['password']??'');$ip=$_SERVER['REMOTE_ADDR']??'unknown';
    $s=$pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address=? AND success=0 AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$s->execute([$ip]);
    if((int)$s->fetchColumn()>=5){$error='Too many attempts. Try again in 15 minutes.';}else{
        $s=$pdo->prepare('SELECT u.*,r.name role_name,r.slug role_slug FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.status="active" AND u.deleted_at IS NULL LIMIT 1');$s->execute([$email]);$row=$s->fetch();$ok=$row&&password_verify($password,$row['password_hash']);
        $pdo->prepare('INSERT INTO login_attempts(email,ip_address,success,user_agent) VALUES(?,?,?,?)')->execute([$email,$ip,$ok?1:0,substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);
        if($ok){load_user_session($pdo,$row);if(!empty($_POST['remember_me']))issue_remember_token($pdo,(int)$row['id']);audit($pdo,'password_login','authentication');$q=$pdo->prepare('SELECT COUNT(*) FROM webauthn_credentials WHERE user_id=? AND revoked_at IS NULL');$q->execute([$row['id']]);redirect((int)$q->fetchColumn()?'dashboard.php':'profile.php?setup=biometric');}
        $error='Incorrect recovery email or password.';
    }
}
$passkeyCount=(int)$pdo->query('SELECT COUNT(*) FROM webauthn_credentials WHERE revoked_at IS NULL')->fetchColumn();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#6d5dfc"><title>Unlock · HESH / FRN</title><link rel="manifest" href="<?=app_url('manifest.json')?>"><link rel="stylesheet" href="<?=app_url('assets/css/app.css?v=1.9.0')?>"><link rel="stylesheet" href="<?=app_url('assets/css/mobile.css?v=1.9.0')?>"><link rel="stylesheet" href="<?=app_url('assets/css/v17.css?v=1.9.0')?>"><script>window.APP_URL=<?=json_encode($config['app']['url'])?>;</script></head>
<body class="auth-page biometric-auth"><main class="auth-card biometric-card">
<div class="auth-brand"><span class="brand-mark">HF</span><div><b>HESH / FRN</b><small>Stock & Wastage App</small></div></div>
<div class="biometric-visual"><span class="biometric-ring"><i data-icon="fingerprint"></i></span><span class="secure-dot"></span></div>
<span class="eyebrow">PRIVATE WORKSPACE</span><h1>Unlock your app</h1><p>Use the fingerprint, Face ID or screen lock already secured on this device.</p>
<?php if($error):?><div class="form-alert"><?=e($error)?></div><?php endif;?>
<div class="form-alert biometric-message" data-passkey-message hidden></div>
<button class="btn primary biometric-button" type="button" data-passkey-login <?=$passkeyCount?'':'disabled'?>>
  <i data-icon="fingerprint"></i><span><?=$passkeyCount?'Unlock with fingerprint / Face ID':'Biometric access not activated'?></span>
</button>
<?php if(!$passkeyCount):?><p class="setup-note">For the first device only, open secure setup below once. After activation, staff can unlock without entering login details.</p><?php endif;?>
<details class="recovery-panel" <?=$error||!$passkeyCount?'open':''?>><summary>Login with username and password</summary><form method="post" class="stack"><?=csrf_field()?>
<label>Login Email<input type="email" name="email" autocomplete="username" required value="<?=e($_POST['email']??'')?>"></label>
<label>Password<div class="password-wrap"><input type="password" name="password" autocomplete="current-password" required><button type="button" data-password>Show</button></div></label>
<label class="remember-check"><input type="checkbox" name="remember_me" value="1" checked><span><b>Keep me signed in</b><small>Do not ask for username and password on this device for 30 days.</small></span></label>
<button class="btn" type="submit">Continue secure setup</button></form><a class="recovery-link" href="<?=app_url('auth/manage-admin/forgot-password/')?>">Forgot recovery password?</a></details>
<div class="auth-meta"><span>Device-protected access</span><span>HTTPS required</span></div>
</main><script src="<?=app_url('assets/js/app.js?v=1.9.0')?>"></script></body></html>
