<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final readonly class FoundingAugurModelAssignmentService
{
    private string $directory;

    public function __construct(string $projectDir)
    {
        $this->directory = $projectDir.'/var/imperium/imperator/founding-augur-model-assignments';
    }

    public function authorize(string $instanceId, array $request, array $imperatorAct): array
    {
        if (array_keys($request) !== ['target_seat', 'provider', 'model_id', 'model_version', 'configuration', 'clavium_access_assertion']
            || 'oracle.augur' !== $request['target_seat'] || !$this->identifier($request['provider'])
            || !$this->identifier($request['model_id']) || !$this->identifier($request['model_version'])
            || !is_array($request['configuration']) || [] === $request['configuration']
            || $this->containsSecretField($request['configuration'])
            || !$this->validClaviumAssertion($request['clavium_access_assertion'], $request['provider'])
        ) throw new \InvalidArgumentException('I220_FOUNDING_AUGUR_REQUEST_INVALID');

        if (!$this->digestMatches($imperatorAct)
            || 'imperium.imperator-founding-augur-model-act/v1' !== ($imperatorAct['schema'] ?? null)
            || $instanceId !== ($imperatorAct['instance_id'] ?? null)
            || 'imperator' !== ($imperatorAct['actor']['kind'] ?? null)
            || 'imperator-development-root' !== ($imperatorAct['actor']['id'] ?? null)
            || 'APPROVED' !== ($imperatorAct['disposition'] ?? null)
            || true !== ($imperatorAct['founding_augur_model_assignment_authority'] ?? null)
            || !is_array($imperatorAct['charter_ref'] ?? null)
            || !preg_match('/^sha256:[a-f0-9]{64}$/', (string) ($imperatorAct['charter_ref']['digest'] ?? ''))
            || CanonicalJson::encode($request) !== CanonicalJson::encode($imperatorAct['request'] ?? null)
        ) throw new \RuntimeException('I221_FOUNDING_AUGUR_AUTHORITY_INVALID');

        $binding = [
            'provider' => $request['provider'], 'model_id' => $request['model_id'], 'model_version' => $request['model_version'],
            'configuration' => $request['configuration'], 'access_assertion' => [
                'id' => $request['clavium_access_assertion']['assertion_id'],
                'digest' => $request['clavium_access_assertion']['record_digest'],
                'credential_ref' => $request['clavium_access_assertion']['credential_ref'],
            ],
        ];
        $id = 'founding-augur-model-assignment-'.substr(hash('sha256', CanonicalJson::encode([$instanceId, $imperatorAct['record_digest'], $binding])), 0, 20);
        return $this->persist($id, [
            'schema' => 'imperium.imperator-founding-augur-model-assignment/v1', 'assignment_id' => $id,
            'instance_id' => $instanceId, 'target_seat' => 'oracle.augur',
            'actor' => $imperatorAct['actor'], 'authority_basis' => ['charter_ref' => $imperatorAct['charter_ref'], 'act_digest' => $imperatorAct['record_digest']],
            'model_binding' => $binding, 'assignment_class' => 'PROVISIONAL_FOUNDING_EXCEPTION',
            'replacement_requires_governed_oracle_evaluation' => true, 'silent_substitution_permitted' => false,
            'status' => 'FOUNDING_AUGUR_MODEL_ASSIGNED_PROVISIONAL_PENDING_CONSCRIPTION_ASSEMBLY',
            'founding_assignment_authority_consumed' => true, 'recommendation_authority' => false,
            'selection_authority' => false, 'self_selection_authority' => false, 'provider_invocation_authority' => false,
            'profile_mutation_authority' => false, 'seat_binding_authority' => false, 'deployment_authority' => false, 'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validClaviumAssertion(mixed $assertion, string $provider): bool
    {
        if (!is_array($assertion) || 'imperium.clavium-provider-access-assertion/v1' !== ($assertion['schema'] ?? null)
            || 'clavium' !== ($assertion['issuer']['office'] ?? null) || 'locksmith' !== ($assertion['issuer']['officer'] ?? null)
            || $provider !== ($assertion['provider'] ?? null) || 'ACCESS_AVAILABLE' !== ($assertion['status'] ?? null)
            || !str_starts_with((string) ($assertion['credential_ref'] ?? ''), 'clavium://') || isset($assertion['secret']) || isset($assertion['token']) || isset($assertion['api_key'])
        ) return false;
        return $this->digestMatchesPrefixed($assertion);
    }

    private function identifier(mixed $value): bool { return is_string($value) && 1 === preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:@\/-]*$/', $value); }
    private function containsSecretField(array $value): bool { foreach($value as $key=>$item){if(is_string($key)&&in_array(strtolower($key),['secret','token','api_key','apikey','password','credential'],true))return true;if(is_array($item)&&$this->containsSecretField($item))return true;}return false; }
    private function digestMatches(array $record): bool { $digest=$record['record_digest']??null;unset($record['record_digest']);return is_string($digest)&&hash_equals($digest,hash('sha256',CanonicalJson::encode($record))); }
    private function digestMatchesPrefixed(array $record): bool { $digest=$record['record_digest']??null;unset($record['record_digest']);return is_string($digest)&&hash_equals($digest,'sha256:'.hash('sha256',CanonicalJson::encode($record))); }
    private function read(string $path, string $error): array { if(!is_file($path))throw new \RuntimeException($error);return json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR); }
    private function persist(string $id,array $record):array{$record['record_digest']=hash('sha256',CanonicalJson::encode($record));if(!is_dir($this->directory)&&!mkdir($this->directory,0770,true)&&!is_dir($this->directory))throw new \RuntimeException('I222_FOUNDING_AUGUR_ASSIGNMENT_FAILED');$path=$this->directory.'/'.$id.'.json';if(is_file($path)){$existing=$this->read($path,'I223_FOUNDING_AUGUR_ASSIGNMENT_CONFLICT');if(CanonicalJson::encode($existing)!==CanonicalJson::encode($record))throw new \RuntimeException('I223_FOUNDING_AUGUR_ASSIGNMENT_CONFLICT');return$existing;}if(false===file_put_contents($path,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX))throw new \RuntimeException('I222_FOUNDING_AUGUR_ASSIGNMENT_FAILED');return$record;}
}
