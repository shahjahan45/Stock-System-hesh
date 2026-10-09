<?php
declare(strict_types=1);

function passkey_origin(): string {
    global $config;
    $parts=parse_url($config['app']['url']);
    $scheme=$parts['scheme']??(!empty($_SERVER['HTTPS'])?'https':'http');
    $host=$parts['host']??($_SERVER['HTTP_HOST']??'localhost');
    $port=isset($parts['port'])?':'.$parts['port']:'';
    return $scheme.'://'.$host.$port;
}

function passkey_rp_id(): string {
    $host=(string)(parse_url(passkey_origin(),PHP_URL_HOST)?:'localhost');
    return strtolower($host);
}

function passkey_challenge(string $purpose): string {
    $challenge=b64url_encode(random_bytes(32));
    $_SESSION['passkey_challenge']=['value'=>$challenge,'purpose'=>$purpose,'expires'=>time()+300];
    return $challenge;
}

function passkey_take_challenge(string $purpose): string {
    $stored=$_SESSION['passkey_challenge']??null;
    unset($_SESSION['passkey_challenge']);
    if(!is_array($stored)||($stored['purpose']??'')!==$purpose||(int)($stored['expires']??0)<time()) throw new RuntimeException('Biometric request expired. Please try again.');
    return (string)$stored['value'];
}

function passkey_client_data(string $encoded,string $type,string $challenge): array {
    $raw=b64url_decode($encoded);
    if($raw===false) throw new RuntimeException('Invalid biometric response.');
    $data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
    if(($data['type']??'')!==$type) throw new RuntimeException('Unexpected biometric response type.');
    if(!hash_equals($challenge,(string)($data['challenge']??''))) throw new RuntimeException('Biometric challenge did not match.');
    if(!hash_equals(rtrim(passkey_origin(),'/'),rtrim((string)($data['origin']??''),'/'))) throw new RuntimeException('Biometric origin did not match this website.');
    return [$raw,$data];
}

function cbor_length(string $data,int &$offset,int $info): int {
    if($info<24)return $info;
    if($info===24)return ord($data[$offset++]);
    if($info===25){$v=unpack('n',substr($data,$offset,2))[1];$offset+=2;return $v;}
    if($info===26){$v=unpack('N',substr($data,$offset,4))[1];$offset+=4;return (int)$v;}
    throw new RuntimeException('Unsupported biometric data length.');
}

function cbor_decode_value(string $data,int &$offset): mixed {
    if($offset>=strlen($data))throw new RuntimeException('Incomplete biometric data.');
    $head=ord($data[$offset++]);$major=$head>>5;$info=$head&31;
    if($major===0)return cbor_length($data,$offset,$info);
    if($major===1)return -1-cbor_length($data,$offset,$info);
    if($major===2||$major===3){$len=cbor_length($data,$offset,$info);$value=substr($data,$offset,$len);$offset+=$len;return $value;}
    if($major===4){$len=cbor_length($data,$offset,$info);$out=[];for($i=0;$i<$len;$i++)$out[]=cbor_decode_value($data,$offset);return $out;}
    if($major===5){$len=cbor_length($data,$offset,$info);$out=[];for($i=0;$i<$len;$i++){$key=cbor_decode_value($data,$offset);$out[$key]=cbor_decode_value($data,$offset);}return $out;}
    if($major===6){cbor_length($data,$offset,$info);return cbor_decode_value($data,$offset);}
    if($major===7){return match($info){20=>false,21=>true,22,23=>null,default=>throw new RuntimeException('Unsupported biometric data value.')};}
    throw new RuntimeException('Unsupported biometric data.');
}

function passkey_parse_registration(string $attestationEncoded): array {
    $raw=b64url_decode($attestationEncoded);if($raw===false)throw new RuntimeException('Invalid registration response.');
    $offset=0;$object=cbor_decode_value($raw,$offset);$authData=$object['authData']??null;
    if(!is_string($authData)||strlen($authData)<55)throw new RuntimeException('Registration response is incomplete.');
    if(!hash_equals(hash('sha256',passkey_rp_id(),true),substr($authData,0,32)))throw new RuntimeException('This passkey belongs to another website.');
    $flags=ord($authData[32]);if(($flags&0x01)===0||($flags&0x04)===0||($flags&0x40)===0)throw new RuntimeException('Fingerprint or Face ID verification is required.');
    $signCount=unpack('N',substr($authData,33,4))[1];$cursor=53;$credentialLength=unpack('n',substr($authData,$cursor,2))[1];$cursor+=2;
    $credentialId=substr($authData,$cursor,$credentialLength);$cursor+=$credentialLength;$coseOffset=$cursor;$cose=cbor_decode_value($authData,$coseOffset);
    if(($cose[1]??null)!==2||($cose[3]??null)!==-7||($cose[-1]??null)!==1)throw new RuntimeException('This device passkey format is not supported. Please use a modern phone or computer.');
    $x=$cose[-2]??'';$y=$cose[-3]??'';if(!is_string($x)||!is_string($y)||strlen($x)!==32||strlen($y)!==32)throw new RuntimeException('Invalid passkey public key.');
    $der=hex2bin('3059301306072a8648ce3d020106082a8648ce3d03010703420004').$x.$y;
    $pem="-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END PUBLIC KEY-----\n";
    return ['credential_id'=>b64url_encode($credentialId),'public_key'=>$pem,'sign_count'=>(int)$signCount];
}

function passkey_verify_assertion(array $credential,string $clientDataEncoded,string $authenticatorEncoded,string $signatureEncoded,string $challenge): int {
    [$clientRaw]=passkey_client_data($clientDataEncoded,'webauthn.get',$challenge);
    $auth=b64url_decode($authenticatorEncoded);$signature=b64url_decode($signatureEncoded);
    if($auth===false||$signature===false||strlen($auth)<37)throw new RuntimeException('Invalid biometric sign-in response.');
    if(!hash_equals(hash('sha256',passkey_rp_id(),true),substr($auth,0,32)))throw new RuntimeException('This passkey belongs to another website.');
    $flags=ord($auth[32]);if(($flags&0x01)===0||($flags&0x04)===0)throw new RuntimeException('Fingerprint or Face ID verification was not completed.');
    $count=(int)unpack('N',substr($auth,33,4))[1];$signed=$auth.hash('sha256',$clientRaw,true);
    if(openssl_verify($signed,$signature,$credential['public_key_pem'],OPENSSL_ALGO_SHA256)!==1)throw new RuntimeException('Biometric signature could not be verified.');
    $saved=(int)$credential['sign_count'];if($saved>0&&$count>0&&$count<=$saved)throw new RuntimeException('This passkey may have been copied. Use account recovery.');
    return $count;
}
