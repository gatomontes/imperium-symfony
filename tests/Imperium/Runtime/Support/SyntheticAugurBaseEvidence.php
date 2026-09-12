<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\Augur\BaseEvidence;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
/** Exact signed synthetic originals and numeric source comparisons; never production evidence. */
final class SyntheticAugurBaseEvidence implements BaseEvidence {
            public function __construct(private array $pins,private ?\Closure $hook=null) {}
            public function verify(array $policy,array $input,array $originals):void {
                if($this->hook!==null){($this->hook)();}
                foreach($originals as $key=>$h){if(in_array($h['body']['kind']??'', ['augur-base-facts','augur-base-observation','synthetic-provider-bytes'],true)){
                    R::require(isset($this->pins[$key]) && R::same($this->pins[$key],$h),'SYNTHETIC_PROVIDER_ORIGINAL');
                }}
                foreach($this->pins as $key=>$h){R::require(isset($originals[$key]) && R::same($originals[$key],$h),'SYNTHETIC_PROVIDER_COVERAGE');}
                foreach($input['candidates'] as $row){
                    foreach($input['evidence'] as $o){if($o['scope']['model_id']!==$row['binding']['model_id']){continue;}
                        $raw=Policy::content($originals[R::key($o['content_ref'])],'synthetic-provider-bytes');
                        R::require($raw['schema']==='imperium.synthetic-provider-contract/v1'
                            && $raw['category']===$o['category'] && $raw['model']===$row['binding']['model_id']
                            && R::same($raw['configuration_ref'],$row['binding']['configuration_ref'])
                            && $raw['account_scope']===$input['context']['account_scope'] && $raw['methods']===['POST']
                            && $raw['context_tokens']===$row['bounds']['context_tokens'] && $raw['max_generated_tokens']===$row['bounds']['max_generated_tokens']
                            && $raw['input_ceiling_with_framing']===16384 && $raw['generated_accounting']===$row['bounds']['generated_accounting']
                            && $raw['data_scope']===$row['bounds']['data_scope'] && $row['bounds']['access']==='INVOKE','SYNTHETIC_PROVIDER_FACTS');
                        foreach($row['tariffs'] as $tariff){R::require($raw['currency']===$tariff['currency'] && $raw['rounding']===$tariff['billing_model']
                            && $raw['fixed_fees_micro_usd']===$tariff['fixed_fees_micro_usd'],'SYNTHETIC_PROVIDER_TARIFF');
                            foreach($tariff['meters'] as $meter){$rate=$meter['kind']==='uncached_input'?'input_per_million_micro_usd':'generated_per_million_micro_usd';
                                R::require($meter['numerator']===$raw[$rate] && $meter['denominator']===1000000,'SYNTHETIC_PROVIDER_RATE');}
                        }
                    }
                }
            }
        }
