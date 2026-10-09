<?php
require dirname(__DIR__,2).'/includes/bootstrap.php';
require dirname(__DIR__,2).'/includes/webauthn.php';
try{
    $input=json_decode(file_get_contents('php://input'),true,16,JSON_THROW_ON_ERROR);
    $credentialId=(string)($input['credentialId']??'');
    $s=$pdo->prepare('SELECT c.id credential_row_id,c.public_key_pem,c.sign_count credential_sign_count,u.*,r.name role_name,r.slug role_slug FROM webauthn_credentials c JOIN users u ON u.id=c.user_id JOIN roles r ON r.id=u.role_id WHERE c.credential_id=? AND c.revoked_at IS NULL AND u.status="active" AND u.deleted_at IS NULL LIMIT 1');
    $s->execute([$credentialId]);$row=$s->fetch();if(!$row)throw new RuntimeException('This device passkey is not registered.');
    $credential=['public_key_pem'=>$row['public_key_pem'],'sign_count'=>$row['credential_sign_count']];
    $count=passkey_verify_assertion($credential,(string)($input['clientDataJSON']??''),(string)($input['authenticatorData']??''),(string)($input['signature']??''),passkey_take_challenge('login'));
    $pdo->prepare('UPDATE webauthn_credentials SET sign_count=?,last_used_at=NOW() WHERE id=?')->execute([$count,$row['credential_row_id']]);
    $userRow=$row;unset($userRow['public_key_pem'],$userRow['credential_sign_count'],$userRow['credential_row_id']);
    load_user_session($pdo,$userRow);audit($pdo,'passkey_login','authentication');
    json_response(['ok'=>true,'redirect'=>app_url('dashboard.php')]);
}catch(Throwable $e){json_response(['ok'=>false,'message'=>$e->getMessage()],422);}
