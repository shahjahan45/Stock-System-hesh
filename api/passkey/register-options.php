<?php
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_auth();
require dirname(__DIR__,2).'/includes/webauthn.php';
$s=$pdo->prepare('SELECT credential_id FROM webauthn_credentials WHERE user_id=? AND revoked_at IS NULL');
$s->execute([user()['id']]);
$exclude=array_map(fn($id)=>['type'=>'public-key','id'=>$id],$s->fetchAll(PDO::FETCH_COLUMN));
json_response(['ok'=>true,'publicKey'=>[
    'challenge'=>passkey_challenge('register'),
    'rp'=>['name'=>$config['app']['name'],'id'=>passkey_rp_id()],
    'user'=>['id'=>b64url_encode((string)user()['id']),'name'=>user()['email'],'displayName'=>user()['name']],
    'pubKeyCredParams'=>[['type'=>'public-key','alg'=>-7]],
    'timeout'=>60000,
    'attestation'=>'none',
    'authenticatorSelection'=>['authenticatorAttachment'=>'platform','residentKey'=>'preferred','requireResidentKey'=>false,'userVerification'=>'required'],
    'excludeCredentials'=>$exclude,
]]);
