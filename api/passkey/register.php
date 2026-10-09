<?php
require dirname(__DIR__,2).'/includes/bootstrap.php';
require_auth();
require dirname(__DIR__,2).'/includes/webauthn.php';
try{
    $input=json_decode(file_get_contents('php://input'),true,16,JSON_THROW_ON_ERROR);
    if(!hash_equals(csrf_token(),(string)($input['csrf']??'')))throw new RuntimeException('Security token expired. Refresh and try again.');
    $challenge=passkey_take_challenge('register');
    passkey_client_data((string)($input['clientDataJSON']??''),'webauthn.create',$challenge);
    $registration=passkey_parse_registration((string)($input['attestationObject']??''));
    if(!hash_equals($registration['credential_id'],(string)($input['credentialId']??'')))throw new RuntimeException('Credential identity did not match.');
    $name=trim((string)($input['deviceName']??''))?:'Fingerprint / Face ID';
    $s=$pdo->prepare('INSERT INTO webauthn_credentials(user_id,credential_id,public_key_pem,sign_count,transports,device_name,last_used_at) VALUES(?,?,?,?,?,?,NOW())');
    $s->execute([user()['id'],$registration['credential_id'],$registration['public_key'],$registration['sign_count'],json_encode($input['transports']??[]),substr($name,0,100)]);
    audit($pdo,'register_passkey','authentication',(int)$pdo->lastInsertId());
    json_response(['ok'=>true,'message'=>'Fingerprint / Face ID access is ready.']);
}catch(Throwable $e){json_response(['ok'=>false,'message'=>$e->getMessage()],422);}
