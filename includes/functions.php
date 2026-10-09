<?php
declare(strict_types=1);

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function app_url(string $path=''): string { global $config; return $config['app']['url'].'/'.ltrim($path,'/'); }
function redirect(string $path): never { header('Location: '.app_url($path)); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if (!hash_equals($_SESSION['csrf']??'', (string)($_POST['csrf']??''))) { http_response_code(419); exit('Invalid security token. Refresh and try again.'); } }
function flash(string $type, string $message): void { $_SESSION['flash'][]=['type'=>$type,'message'=>$message]; }
function take_flashes(): array { $f=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $f; }
function user(): ?array { return $_SESSION['user']??null; }
function require_auth(): void { if (!user()) redirect('auth/manage-admin/login/'); }
function json_response(array $payload, int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($payload,JSON_UNESCAPED_SLASHES); exit; }
function b64url_encode(string $value): string { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
function b64url_decode(string $value): string|false { $padding=strlen($value)%4; if($padding)$value.=str_repeat('=',4-$padding); return base64_decode(strtr($value,'-_','+/'),true); }
function load_user_session(PDO $pdo, array $row): void {
    $q=$pdo->prepare('SELECT p.slug FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=?');
    $q->execute([$row['role_id']]);
    $row['permissions']=$q->fetchAll(PDO::FETCH_COLUMN);
    unset($row['password_hash']);
    session_regenerate_id(true);
    $_SESSION['user']=$row;
    $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$row['id']]);
}
function remember_cookie_options(int $expires): array { return ['expires'=>$expires,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']; }
function issue_remember_token(PDO $pdo,int $userId): void {
    $selector=bin2hex(random_bytes(16));$validator=bin2hex(random_bytes(32));$expires=time()+60*60*24*30;
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id=? AND expires_at<NOW()')->execute([$userId]);
    $pdo->prepare('INSERT INTO remember_tokens(user_id,selector,token_hash,expires_at) VALUES(?,?,?,FROM_UNIXTIME(?))')->execute([$userId,$selector,hash('sha256',$validator),$expires]);
    setcookie('HFO_REMEMBER',$selector.':'.$validator,remember_cookie_options($expires));
}
function clear_remember_token(PDO $pdo): void {
    $cookie=(string)($_COOKIE['HFO_REMEMBER']??'');$parts=explode(':',$cookie,2);
    if(count($parts)===2&&preg_match('/^[a-f0-9]{32}$/',$parts[0]))$pdo->prepare('DELETE FROM remember_tokens WHERE selector=?')->execute([$parts[0]]);
    setcookie('HFO_REMEMBER','',remember_cookie_options(time()-3600));unset($_COOKIE['HFO_REMEMBER']);
}
function restore_remembered_user(PDO $pdo): bool {
    $cookie=(string)($_COOKIE['HFO_REMEMBER']??'');$parts=explode(':',$cookie,2);if(count($parts)!==2)return false;[$selector,$validator]=$parts;
    if(!preg_match('/^[a-f0-9]{32}$/',$selector)||!preg_match('/^[a-f0-9]{64}$/',$validator))return false;
    $s=$pdo->prepare('SELECT t.id token_id,t.token_hash,u.*,r.name role_name,r.slug role_slug FROM remember_tokens t JOIN users u ON u.id=t.user_id JOIN roles r ON r.id=u.role_id WHERE t.selector=? AND t.expires_at>NOW() AND u.status="active" AND u.deleted_at IS NULL LIMIT 1');$s->execute([$selector]);$row=$s->fetch();
    if(!$row||!hash_equals($row['token_hash'],hash('sha256',$validator))){if($row)$pdo->prepare('DELETE FROM remember_tokens WHERE id=?')->execute([$row['token_id']]);return false;}
    $newValidator=bin2hex(random_bytes(32));$expires=time()+60*60*24*30;$pdo->prepare('UPDATE remember_tokens SET token_hash=?,expires_at=FROM_UNIXTIME(?),last_used_at=NOW() WHERE id=?')->execute([hash('sha256',$newValidator),$expires,$row['token_id']]);
    unset($row['token_id'],$row['token_hash']);load_user_session($pdo,$row);setcookie('HFO_REMEMBER',$selector.':'.$newValidator,remember_cookie_options($expires));return true;
}
function can(string $permission): bool { $u=user(); return $u && (($u['role_slug']??'')==='super-admin' || in_array($permission,$u['permissions']??[],true)); }
function require_permission(string $permission): void { require_auth(); if (!can($permission)) { http_response_code(403); require dirname(__DIR__).'/403.php'; exit; } }
function enforce_branch_scope(int $branchId): void { $assigned=(int)(user()['branch_id']??0); if($assigned && $assigned!==$branchId && (user()['role_slug']??'')!=='super-admin') throw new RuntimeException('This account is restricted to its assigned branch.'); }
function money(float|int|string $value): string { global $config; return $config['app']['currency'].' '.number_format((float)$value,2); }
function audit(PDO $pdo, string $action, string $module, ?int $recordId=null, mixed $old=null, mixed $new=null): void {
    $s=$pdo->prepare('INSERT INTO audit_logs (user_id,action,module,record_id,old_values,new_values,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?)');
    $s->execute([user()['id']??null,$action,$module,$recordId,$old?json_encode($old):null,$new?json_encode($new):null,$_SERVER['REMOTE_ADDR']??null,substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);
}
function next_reference(PDO $pdo, string $prefix, string $table): string {
    $year=date('Y'); $stmt=$pdo->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=".$pdo->quote($table));
    $next=(int)($stmt->fetchColumn()?:1); return sprintf('%s-%s-%06d',$prefix,$year,$next);
}
function stock_balance(PDO $pdo,int $productId,int $branchId,int $brandId): float {
    $s=$pdo->prepare('SELECT COALESCE(SUM(quantity_in-quantity_out),0) FROM inventory_transactions WHERE product_id=? AND branch_id=? AND brand_id=?');
    $s->execute([$productId,$branchId,$brandId]); return (float)$s->fetchColumn();
}
function ledger(PDO $pdo,array $d): void {
    $balance=stock_balance($pdo,(int)$d['product_id'],(int)$d['branch_id'],(int)$d['brand_id'])+(float)$d['quantity_in']-(float)$d['quantity_out'];
    if ($balance < 0 && empty($d['allow_negative'])) throw new RuntimeException('Insufficient stock for this operation.');
    $s=$pdo->prepare('INSERT INTO inventory_transactions (product_id,branch_id,brand_id,transaction_type,reference_type,reference_id,reference_no,quantity_in,quantity_out,balance,user_id,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $s->execute([$d['product_id'],$d['branch_id'],$d['brand_id'],$d['transaction_type'],$d['reference_type'],$d['reference_id'],$d['reference_no'],$d['quantity_in'],$d['quantity_out'],$balance,user()['id']??null,$d['notes']??null]);
}
function selected(string|int $a,string|int $b): string { return (string)$a===(string)$b?' selected':''; }
