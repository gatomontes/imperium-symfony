<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionModelBindingSealingService
{
    private string $decisions;
    private string $oracleCommissions;
    private string $commissions;
    private string $bindings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private StateStore $state)
    {
        $this->decisions = $root.'/var/imperium/offices/curia/delegate-mission-model-selection-decisions';
        $this->oracleCommissions = $root.'/var/imperium/offices/curia/model-requirement-commissions';
        $this->commissions = $root.'/var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions';
        $this->bindings = $root.'/var/imperium/offices/conscription/delegate-mission-model-bindings';
    }

    public function seal(string $decisionId, string $authorityId, \DateTimeImmutable $sealedAt): array
    {
        if (!preg_match('/^delegate-mission-model-selection-decision-[a-f0-9]{20}$/', $decisionId)) {
            throw new \InvalidArgumentException('R280_DELEGATE_MODEL_SELECTION_DECISION_ID_INVALID');
        }

        $decision = $this->read($this->decisions.'/'.$decisionId.'.json', 'R281_DELEGATE_MODEL_SELECTION_DECISION_ABSENT');
        foreach (glob($this->bindings.'/*.json') ?: [] as $path) {
            $existing = $this->read($path, 'R289_DELEGATE_MODEL_BINDING_CONFLICT');
            if (($existing['source_selection_decision']['id'] ?? null) === $decisionId) {
                return $existing;
            }
        }

        $oracleCommissionId = $decision['source_commission']['id'] ?? '';
        $oracleCommission = $this->read($this->oracleCommissions.'/'.$oracleCommissionId.'.json', 'R282_DELEGATE_ORACLE_COMMISSION_ABSENT');
        $commissionId = $oracleCommission['delegate_lineage']['bounded_commission']['id'] ?? '';
        $commission = $this->read($this->commissions.'/'.$commissionId.'.json', 'R282_DELEGATE_MODEL_COMMISSION_ABSENT');
        [$instanceId, $recruiter] = $this->recruiter();
        $authority = $decision['model_binding_sealing_authority'] ?? [];
        if (!$this->validDigest($decision) || !$this->validDigest($oracleCommission) || !$this->validDigest($commission)
            || 'imperium.curia-delegate-mission-model-selection-decision/v1' !== ($decision['schema'] ?? null)
            || $decisionId !== ($decision['decision_id'] ?? null)
            || $instanceId !== ($decision['instance_id'] ?? null)
            || 'DELEGATE_MISSION_MODEL_SELECTED_PENDING_CONSCRIPTION_BINDING_SEAL' !== ($decision['status'] ?? null)
            || true !== ($decision['model_selected'] ?? null)
            || !is_string($decision['selected_model'] ?? null)
            || !in_array($decision['selected_model'], $decision['eligible_models'] ?? [], true)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null) || 'conscription.recruiter' !== ($authority['holder'] ?? null)
            || 'SEAL_EXACT_SELECTED_MODEL_TO_DELEGATE_MISSION_TURN_ONE' !== ($authority['purpose'] ?? null)
            || true === ($decision['model_assignment_authority'] ?? null) || true === ($decision['provider_invocation_authority'] ?? null)
            || 'imperium.curia-model-requirement-commission/v1' !== ($oracleCommission['schema'] ?? null)
            || $oracleCommissionId !== ($oracleCommission['commission_id'] ?? null)
            || ($decision['source_commission']['digest'] ?? null) !== ($oracleCommission['record_digest'] ?? null)
            || $instanceId !== ($oracleCommission['instance_id'] ?? null)
            || 'imperium.curia-delegate-mission-bounded-cognition-commission/v1' !== ($commission['schema'] ?? null)
            || $commissionId !== ($commission['commission_id'] ?? null)
            || ($oracleCommission['delegate_lineage']['bounded_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || $instanceId !== ($commission['instance_id'] ?? null) || 1 !== ($commission['commission_contract']['turn_sequence'] ?? null)
            || true === ($commission['commission_contract']['provider_invocation_allowed'] ?? null)) {
            throw new \RuntimeException('R283_DELEGATE_MODEL_BINDING_CHAIN_INVALID');
        }

        $actor = ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']];
        $target = ['instance_id' => $instanceId, 'seat' => $commission['seat'], 'manifestation_id' => $commission['manifestation_id'], 'occupancy_generation' => $commission['occupancy_generation'], 'commission_id' => $commissionId, 'turn_sequence' => 1];
        $bindingId = 'delegate-mission-model-binding-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $authorityId, $decision['selected_model'], $decision['configuration'], $target])), 0, 20);
        $attestationAuthorityId = 'delegate-mission-model-access-attestation-authority-'.substr(hash('sha256', CanonicalJson::encode([$bindingId, $decision['selected_model'], $target])), 0, 20);

        return $this->save($bindingId, [
            'schema' => 'imperium.conscription-delegate-mission-model-binding/v1', 'binding_id' => $bindingId, 'instance_id' => $instanceId, 'binder' => $actor,
            'source_selection_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'source_recommendation' => $decision['source_recommendation'], 'source_oracle_commission' => ['id' => $oracleCommissionId, 'digest' => $oracleCommission['record_digest']], 'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'target' => $target, 'provider_model_version' => $decision['selected_model'], 'configuration' => $decision['configuration'],
            'binding_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false], 'sealed_at' => $sealedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_MODEL_BINDING_SEALED_PENDING_ACCESS_ATTESTATION', 'model_binding_sealed' => true,
            'model_access_attestation_authority' => ['authority_id' => $attestationAuthorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'clavium.locksmith', 'purpose' => 'ATTEST_ACCESS_TO_EXACT_BOUND_MODEL_WITHOUT_RELEASING_CREDENTIALS', 'consumed' => false, 'continuing_authority' => false],
            'profile_mutated' => false, 'model_assigned' => false, 'access_attested' => false, 'credential_released' => false, 'provider_invoked' => false, 'resource_available' => false, 'external_action_authorized' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
    }

    private function recruiter(): array
    {
        $state = $this->state->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) {
            throw new \RuntimeException('R284_DELEGATE_MODEL_RECRUITER_UNAVAILABLE');
        }
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $recruiter = 'T04' === ($state['events'][$index]['transition'] ?? null) && 'SUCCESS' === ($state['events'][$index]['result'] ?? null) ? ($state['events'][$index]['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null)) {
                return [(string) $state['binding']['instance_id'], $recruiter];
            }
        }
        throw new \RuntimeException('R284_DELEGATE_MODEL_RECRUITER_UNAVAILABLE');
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function validDigest(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $bindingId, array $record): array
    {
        if (!is_dir($this->bindings) && !mkdir($this->bindings, 0770, true) && !is_dir($this->bindings)) {
            throw new \RuntimeException('R288_DELEGATE_MODEL_BINDING_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($this->bindings.'/'.$bindingId.'.json', json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX);
        return $record;
    }
}
