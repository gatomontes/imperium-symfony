<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Console;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class PublicResult
{
    public static function empty(?string $sequence, ?string $command, string $mode): array
    {
        $facts = [];
        foreach (['configuration'=>'absent','credential'=>'unknown','authentication'=>'unverified',
            'base_selection'=>'unselected','augur_authority'=>'missing','assessment'=>'not_authorized','assignment'=>'absent'] as $name=>$state) {
            $facts[$name] = ['state'=>$state, 'refs'=>[]];
        }
        return ['schema'=>'imperium.provider-onboarding-status/v2','sequence_id'=>$sequence,'command_id'=>$command,
            'mode'=>$mode,'status'=>'REFUSED','reason_codes'=>[],'head'=>['generation'=>0,'digest'=>null],
            'sequence_head'=>null,'result_ref'=>null,'facts'=>$facts,
            'next_action'=>['code'=>'READ_STATUS','step_id'=>null,'explanation'=>'Inspect retained evidence before submitting another command.'],
            'evidence_refs'=>[],'effects'=>['claim_refs'=>[],'exposure'=>null,'assignment_receipt_refs'=>[],'new_effects_this_command'=>false],
            'operational_flags'=>array_fill_keys(['deployment_approved','enrollment_authorized','live_ready','activation','execution_authority'],false)];
    }

    public static function refusal(\Throwable $error, ?array $result = null): array
    {
        $result ??= self::empty(null,null,'status');
        $result['status'] = 'REFUSED';
        $result['reason_codes'] = [ReasonCodes::public($error)];
        $result['next_action'] = ['code'=>'RESOLVE_REFUSAL','step_id'=>null,'explanation'=>'Resolve the reported prerequisite through its existing owner.'];
        return $result;
    }

    public static function exitCode(array $result): int
    {
        return match ($result['status']) {
            'ASSIGNMENT_APPLIED'=>0, 'REFUSED'=>1, 'OUTCOME_UNKNOWN'=>3,
            'CONFIGURED','MISSING_CREDENTIAL','AUTHENTICATION_PENDING','BASE_SELECTED',
            'MISSING_AUGUR_AUTHORITY','ASSESSMENT_AUTHORIZED','RESULT_PENDING'=>2,
            default=>throw new \LogicException('O5_PUBLIC_STATUS_INVALID'),
        };
    }

    public static function ref(array $ref): array { return ['id'=>$ref['id'],'digest'=>$ref['digest']]; }
    public static function fact(string $state, array $refs = []): array
    {
        $unique = [];
        foreach ($refs as $ref) { $public = self::ref($ref); $unique[json_encode($public,JSON_THROW_ON_ERROR)] = $public; }
        ksort($unique,SORT_STRING);
        return ['state'=>$state,'refs'=>array_values($unique)];
    }
}
