<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

use App\Imperium\Runtime\Citadel\Formation\SharedExposure;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** The approved per-cognition envelope, not a replacement for a map's supported limits. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class MappingLimits
{
    public const MAXIMUM=['calls'=>1,'input_tokens'=>16384,'output_tokens'=>4096,'cost_microusd'=>100000,'milliseconds'=>60000];

    public static function resolve(array $mapping,array $candidate,array $config,callable $load,int $now,int $expiry):array
    {
        R::object($mapping,['snapshot_ref','provider','mappings','adapter_ref','expires_at']);
        R::require($mapping['provider']===$candidate['provider'] && R::same($mapping['adapter_ref'],$candidate['adapter_ref'])
            && $now<R::time($mapping['expires_at']) && $mapping['expires_at']<=$expiry,'BASE_MAPPING_SCOPE');
        $load(R::ref($mapping['snapshot_ref']));
        R::require(is_array($mapping['mappings']) && array_is_list($mapping['mappings']) && count($mapping['mappings'])>0 && count($mapping['mappings'])<=256,'BASE_MAPPING_SCOPE');
        $matches=[];$seen=[];
        foreach($mapping['mappings'] as $m){
            R::object($m,['model_ref','dispatch_id','configuration_schema_digest','supported_limits','revision_pin_limitation']);
            $load(R::ref($m['model_ref']));$key=R::key($m['model_ref']);R::require(!isset($seen[$key]),'BASE_MAPPING_SCOPE');$seen[$key]=true;
            SharedExposure::meters($m['supported_limits'],false);
            if(R::same($m['model_ref'],$candidate['binding_ref'])){$matches[]=$m;}
        }
        R::require(count($matches)===1 && $matches[0]['dispatch_id']===$candidate['model_id']
            && $matches[0]['configuration_schema_digest']===R::hash($config)
            && $matches[0]['revision_pin_limitation']===$candidate['revision_pin'],'BASE_MAPPING_SCOPE');
        return $matches[0]['supported_limits'];
    }

    public static function supports(array $supported,array $maximum=self::MAXIMUM):bool
    {
        SharedExposure::meters($supported,false);SharedExposure::meters($maximum,false);
        foreach($maximum as $meter=>$value){if($value>$supported[$meter]){return false;}}
        return true;
    }
}
