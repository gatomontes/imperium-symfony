<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Imperium\Runtime\Citadel\Formation\SharedExposure;

/** Closed, non-authorizing attribution checks shared by publication and recovery. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ResponseEvidence
{
    public static function metadata(mixed $value,array $claim):array
    {
        $m=R::object($value,['provider_response_id','operation_digest','response_digest','usage','provenance']);
        R::text($m['provider_response_id']);R::text($m['provenance']);
        R::digest($m['operation_digest']);R::digest($m['response_digest']);
        $op=$claim['operation']['prepared'];
        R::require($m['operation_digest']===R::hash($op) && $m['provenance']===$op['adapter'],'RESPONSE_ATTRIBUTION');
        R::require(is_array($m['usage']),'RESPONSE_USAGE');SharedExposure::meters($m['usage']);
        foreach($claim['maximum'] as $field=>$maximum){R::require($m['usage'][$field]<=$maximum,'USAGE_UNTRUSTWORTHY');}
        return $m;
    }
    public static function envelope(mixed $value,array $claim,?array $originalMetadata=null):array
    {
        $e=R::object($value,['claim_ref','operation_digest','response','metadata']);R::ref($e['claim_ref']);R::digest($e['operation_digest']);
        $m=self::metadata($e['metadata'],$claim);
        R::require(R::same($e['claim_ref'],R::reference($claim['record'])) && $e['operation_digest']===$m['operation_digest'],'RESPONSE_ATTRIBUTION');
        R::require(is_string($e['response']) && strlen($e['response'])<=1048576 && R::hash($e['response'])===$m['response_digest'],'RESPONSE_BYTES');
        if($originalMetadata!==null){self::metadata($originalMetadata,$claim);R::require(R::same($m,$originalMetadata),'RESPONSE_ORIGINAL_MISMATCH');}
        return $e;
    }
}
