<?php
declare(strict_types=1);
// Run only by the key holder. Secret arrives on stdin, never command arguments or output.
require dirname(__DIR__).'/vendor/autoload.php';
if (($argv[1] ?? '')==='--help') {echo "Usage: php tools/sign-protected-mission.php PAYLOAD_FILE RESPONSE_FILE\nSupply the separately held base64 Ed25519 secret on stdin. Exit 0 success, 2 refusal.\n";exit(0);}
$secret=null;
try {
    if ($argc!==3 || !is_file($argv[1]) || is_file($argv[2])) throw new RuntimeException('PMA_SIGN_USAGE_INVALID');
    $bytes=file_get_contents($argv[1]);
    if (strlen($bytes)>2000000) throw new RuntimeException('PMA_SIGN_INPUT_TOO_LARGE');
    $payload=json_decode($bytes,true,128,JSON_THROW_ON_ERROR);
    if (App\Bootstrap\CanonicalJson::encode($payload)!==$bytes || ($payload['schema'] ?? '')!=='imperium.protected-approval/v1') throw new RuntimeException('PMA_SIGN_CANONICAL_BYTES_REQUIRED');
    $line=fgets(STDIN,200); $secret=base64_decode(trim($line ?: ''),true); if (is_string($line)) sodium_memzero($line);
    if (!is_string($secret) || strlen($secret)!==64 || hash('sha256',sodium_crypto_sign_publickey_from_secretkey($secret))!==$payload['trust_fingerprint']) throw new RuntimeException('PMA_SIGN_KEY_INVALID');
    $response=['challenge_id'=>$payload['challenge_id'],'signature'=>base64_encode(sodium_crypto_sign_detached($bytes,$secret))];
    $out=fopen($argv[2],'xb'); if ($out===false) throw new RuntimeException('PMA_SIGN_OUTPUT_EXISTS');
    fwrite($out,json_encode($response,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n"); fclose($out);
    echo "Signed exact canonical bytes. Approval still requires authenticated submission.\n";
} catch (Throwable $e) {fwrite(STDERR,"PMA_SIGN_REFUSED\n");exit(2);}
finally {if (is_string($secret)) sodium_memzero($secret);}
