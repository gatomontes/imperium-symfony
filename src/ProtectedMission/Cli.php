<?php
declare(strict_types=1);
namespace App\ProtectedMission;

use App\Bootstrap\CanonicalJson;

/** Stdio is the narrow local IPC transport, launched by the protected Runtime identity. */
final class Cli
{
    public static function run(\Closure $ownerFactory,array $args):int
    {
        try {
            $op=$args[0] ?? 'help';
            if (in_array($op,['help','--help','-h'],true)) {
                echo "Protected mission owner. Execute under the installed Runtime account.\n";
                echo "enroll FINGERPRINT < public-trust.json (deployment owner only)\n";
                echo "prepare < plan-and-disclosures.json\nexport CHALLENGE_ID\nrender CHALLENGE_ID\nsubmit < signed-response.json\nderive CHALLENGE_ID\nverify AUTHORIZATION_ID\nstatus AUTHORIZATION_ID\nchallenge-status CHALLENGE_ID\ntrust\nrequest < request.json\n";
                echo "Exit codes: 0 success/help; 2 refusal or malformed input. Approval is not execution.\n";
                return 0;
            }
            if (!in_array($op,['enroll','prepare','export','render','submit','derive','verify','status','challenge-status','trust','request'],true)) throw new \RuntimeException('PMA_COMMAND_UNKNOWN');
            $needsId=in_array($op,['enroll','export','render','derive','verify','status','challenge-status'],true);
            if (count($args)!==($needsId?2:1)) throw new \RuntimeException('PMA_USAGE_INVALID');
            $owner=$ownerFactory();
            if ($op==='enroll') $result=$owner->enroll(self::input(),$args[1]);
            else {
                $arguments=match($op) {
                    'prepare','submit'=>self::input(),
                    'export','render','derive','challenge-status'=>['challenge_id'=>$args[1]],
                    'verify','status'=>['authorization_id'=>$args[1]],
                    default=>[],
                };
                $request=$op==='request'?self::input():['operation'=>$op==='render'?'export':$op,'arguments'=>$arguments];
                $result=$owner->dispatch($request);
            }
            if ($op==='export') echo CanonicalJson::encode($result); // Exact UTF-8 bytes; no BOM/newline.
            elseif ($op==='render') {
                echo "PENDING CHALLENGE — NOT APPROVAL OR EXECUTION\n";
                foreach ($result['dossier']['lines'] as $line) echo $line['line_number'].'. '.$line['text']."\n";
                echo "\nComplete signed object (no omitted fields):\n".json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
                echo 'Canonical SHA-256: '.hash('sha256',CanonicalJson::encode($result))."\n";
            } else echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
            return 0;
        } catch (\Throwable $error) {
            $message=$error instanceof \RuntimeException && preg_match('/^[A-Z0-9_]+$/D',$error->getMessage())?$error->getMessage():'PMA_INPUT_OR_OPERATION_FAILED';
            fwrite(STDERR,$message."\n"); return 2;
        }
    }
    private static function input():array
    {
        $bytes=stream_get_contents(STDIN,2000001);
        if (!is_string($bytes) || strlen($bytes)>2000000) throw new \RuntimeException('PMA_INPUT_TOO_LARGE');
        $value=json_decode($bytes,true,128,JSON_THROW_ON_ERROR);
        if (!is_array($value)) throw new \RuntimeException('PMA_INPUT_INVALID');
        return $value;
    }
}
