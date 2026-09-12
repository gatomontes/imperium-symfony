<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\Augur\{AugurAdapter,AugurMigration,CognitionEvidence,CognitionResources};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\DeepSeek\{Runtime,TokenEvidence,Tariff};
use App\Imperium\Runtime\Onboarding\ResponseValidation\{ParsedResponse,CandidateClaim};
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};

final class SyntheticAssignmentCognitionEvidence implements CognitionEvidence
{

            public function __construct(private array $pins,private array $credential){}
            private function original(array $originals,array $ref,string $kind):array{R::require(isset($originals[R::key($ref)],$this->pins[R::key($ref)]) && R::same($originals[R::key($ref)],$this->pins[R::key($ref)]),'SYNTHETIC_COGNITION_ORIGINAL');return Policy::content($originals[R::key($ref)],$kind);}
            public function resources(array $c,array $originals,string $wire,string $model,int $now):CognitionResources{
                $a=$this->original($originals,$c['account_ref'],'synthetic-cognition-account');$t=$this->original($originals,$c['token_ref'],'synthetic-cognition-tokenizer');$rates=$this->original($originals,$c['tariff_ref'],'synthetic-cognition-tariff');
                R::require(R::same($a['credential_ref'],$this->credential) && $a['account_scope']===$c['account_scope'] && $a['methods']===['POST'] && in_array($model,$a['models'],true) && $a['not_before']<=$now && $c['expires_at']<=$a['expires_at'],'SYNTHETIC_INVOKE_SCOPE');
                R::require($t['algorithm']==='fixed-four-octet-blocks/v1' && $t['frame_tokens']===8,'SYNTHETIC_TOKENIZER');
                // Exact tokenizer of this fictional mock protocol. No claim about DeepSeek tokenization.
                $tokens=count(str_split($wire,4))+$t['frame_tokens'];
                return new CognitionResources(new TokenEvidence('sha256:'.hash('sha256',$wire),$t['algorithm'],'synthetic-eight-token-frame/v1',$tokens,$t['generated_tokens'],$t['context_tokens']),
                    new Tariff($rates['account_scope'],$rates['model'],$rates['not_before'],$rates['expires_at'],$rates['miss_rate'],$rates['hit_rate'],$rates['output_rate'],$rates['rounding'],$rates['fees']));
            }
            public function response(ParsedResponse $response,array $c,array $originals):void{
                $f=$this->original($originals,$c['evidence_refs'][0],'synthetic-cognition-findings');
                R::require(count(array_filter($f['profiles'],static fn(array $profile):bool=>R::same($profile,$c['profile_ref'])))===1,'SYNTHETIC_RESULT_PROFILE');
                foreach($response->candidateRows as $row){$ref=['schema'=>$row->bindingRef->schema,'id'=>$row->bindingRef->id,'digest'=>$row->bindingRef->digest];
                    R::require(count(array_filter($f['models'],static fn(array $model):bool=>R::same($model,$ref)))===1 && $row->fit===$f['fit'],'SYNTHETIC_RESULT_FIT');
                    foreach($row->predicates as $name=>$claim){R::require($claim->disposition===$f['predicates'][$name] && count($claim->evidenceRefs)===1 && $claim->evidenceRefs[0]->digest===$c['evidence_refs'][0]['digest'],'SYNTHETIC_RESULT_PREDICATE');}
                }
                R::require($response->contradictions===[] && $response->unknowns===[],'SYNTHETIC_RESULT_CONTRADICTION');
                if($response->groupId!=='W1'){
                    if($f['capacity_tiers']===[]){R::require($response->capacityOrder->kind==='unknown','SYNTHETIC_CAPACITY_EVIDENCE');}
                    else{$tiers=[];foreach($response->capacityOrder->tiers as $tier){$refs=[];foreach($tier['binding_refs'] as $ref){$refs[]=['schema'=>$ref->schema,'id'=>$ref->id,'digest'=>$ref->digest];}$tiers[]=R::refs($refs);}
                        R::require($response->capacityOrder->kind==='ranked' && R::same($tiers,array_map(R::refs(...),$f['capacity_tiers'])),'SYNTHETIC_CAPACITY_EVIDENCE');}
                }
            }
        }
