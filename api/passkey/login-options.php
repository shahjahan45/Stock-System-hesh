<?php
require dirname(__DIR__,2).'/includes/bootstrap.php';
require dirname(__DIR__,2).'/includes/webauthn.php';
$rows=$pdo->query('SELECT credential_id FROM webauthn_credentials WHERE revoked_at IS NULL ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
if(!$rows)json_response(['ok'=>false,'code'=>'not_configured','message'=>'Fingerprint / Face ID has not been activated yet.'],404);
json_response(['ok'=>true,'publicKey'=>[
    'challenge'=>passkey_challenge('login'),
    'rpId'=>passkey_rp_id(),
    'allowCredentials'=>array_map(fn($id)=>['type'=>'public-key','id'=>$id],$rows),
    'timeout'=>60000,
    'userVerification'=>'required',
]]);
