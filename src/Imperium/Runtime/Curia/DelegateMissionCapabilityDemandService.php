<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionCapabilityDemandService
{
    private const string CONSUMER = 'guildhall.guildmaster';
    private const array REQUIRED_LISTS = [
        'scope',
        'deliverables',
        'constraints',
        'required_inputs',
        'capability_requirements',
        'expected_outcomes',
        'data_requirements',
        'tool_requirements',
        'credential_requirements',
        'perimeter_requirements',
        'stop_conditions',
        'return_conditions',
        'unbinding_conditions',
        'custody_restoration_conditions',
        'retirement_conditions',
    ];
    private const array FALSE_AUTHORITIES = [
        'mission_plan_amendment_authority',
        'guildhall_delivery_authority',
        'guildhall_intake_authority',
        'profession_translation_authority',
        'profession_determination_authority',
        'persona_selection_authority',
        'persona_suitability_authority',
        'personnel_use_authority',
        'reservation_authority',
        'retrieval_authority',
        'custody_transfer_authority',
        'profile_derivation_authority',
        'profile_examination_authority',
        'profile_approval_authority',
        'profile_installation_authority',
        'profile_qualification_authority',
        'manifestation_assembly_authority',
        'seat_binding_authority',
        'commission_authority',
        'follow_up_commission_authority',
        'deployment_authority',
        'operational_use_authority',
        'cognition_authority',
        'provider_invocation_authority',
        'data_access_authority',
        'tool_use_authority',
        'credential_use_authority',
        'perimeter_crossing_authority',
        'external_action_authority',
        'execution_authority',
        'return_execution_authority',
        'continuing_turn_authority',
    ];

    private string $authorizations;
    private string $dossiers;
    private string $reviews;
    private string $demands;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->authorizations = $root.'/var/imperium/authorizations/missions';
        $this->dossiers = $root.'/var/imperium/offices/curia/planning-dossiers';
        $this->reviews = $root.'/var/imperium/offices/curia/planning-dossier-reviews';
        $this->demands = $root.'/var/imperium/offices/curia/delegate-mission-capability-demands';
    }

    public function seal(string $missionAuthorizationId, \DateTimeImmutable $sealedAt): array
    {
        if (!preg_match('/^mission-authorization-[a-f0-9]{20}$/', $missionAuthorizationId)) {
            throw new \InvalidArgumentException('CUR490_MISSION_AUTHORIZATION_ID_INVALID');
        }

        $authorization = $this->read(
            $this->authorizations.'/'.$missionAuthorizationId.'.json',
            'CUR491_MISSION_AUTHORIZATION_ABSENT',
        );
        $source = $authorization['authority_source'] ?? [];
        $dossierId = $source['dossier']['id'] ?? '';
        $reviewId = $source['imperator_review']['id'] ?? '';
        $dossier = $this->read($this->dossiers.'/'.$dossierId.'.json', 'CUR492_APPROVED_MISSION_PLAN_ABSENT');
        $review = $this->read($this->reviews.'/'.$reviewId.'.json', 'CUR493_GOVERNING_APPROVAL_ABSENT');

        $this->validateChain($missionAuthorizationId, $authorization, $dossierId, $dossier, $reviewId, $review);
        $plan = $authorization['mission_plan'];
        $this->validateDelegateDemandPlan($plan);

        foreach (glob($this->demands.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR496_DELEGATE_MISSION_CAPABILITY_DEMAND_CONFLICT');
            if (($prior['authority_source']['mission_authorization']['id'] ?? null) === $missionAuthorizationId) {
                if (($prior['authority_source']['mission_authorization']['digest'] ?? null) !== $authorization['record_digest']) {
                    throw new \RuntimeException('CUR496_DELEGATE_MISSION_CAPABILITY_DEMAND_CONFLICT');
                }

                return $prior;
            }
        }

        $planDigest = hash('sha256', CanonicalJson::encode($plan));
        $planIdentity = [
            'proceeding_id' => $dossier['source_plan']['proceeding_id'],
            'turn_sequence' => $dossier['source_plan']['turn_sequence'],
            'turn_digest' => $dossier['source_plan']['turn_digest'],
            'dossier_id' => $dossierId,
            'dossier_version' => $dossier['dossier_version'],
            'dossier_digest' => $dossier['record_digest'],
            'plan_digest' => $planDigest,
        ];
        $demandId = 'delegate-mission-capability-demand-'.substr(hash('sha256', CanonicalJson::encode([
            $authorization['instance_id'],
            $missionAuthorizationId,
            $authorization['record_digest'],
            $planIdentity,
            OfficerClass::Delegate->value,
        ])), 0, 20);

        $record = [
            'schema' => 'imperium.delegate-mission-capability-demand/v1',
            'demand_id' => $demandId,
            'instance_id' => $authorization['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'authority_source' => [
                'mission_authorization' => ['id' => $missionAuthorizationId, 'digest' => $authorization['record_digest']],
                'approved_dossier' => ['id' => $dossierId, 'version' => $dossier['dossier_version'], 'digest' => $dossier['record_digest']],
                'imperator_review' => ['id' => $reviewId, 'digest' => $review['record_digest'], 'disposition' => 'APPROVE_DOSSIER'],
            ],
            'mission_plan' => $planIdentity,
            'demand' => [
                'objective' => $plan['objective'],
                'scope' => $plan['scope'],
                'deliverables' => $plan['deliverables'],
                'constraints' => $plan['constraints'],
                'required_inputs' => $plan['required_inputs'],
                'capability_requirements' => $plan['capability_requirements'],
                'expected_outcomes' => $plan['expected_outcomes'],
                'mission_seat' => $plan['mission_seat'],
                'bounded_duration' => $plan['bounded_duration'],
                'data_requirements' => $plan['data_requirements'],
                'tool_requirements' => $plan['tool_requirements'],
                'credential_requirements' => $plan['credential_requirements'],
                'perimeter_requirements' => $plan['perimeter_requirements'],
                'stop_conditions' => $plan['stop_conditions'],
                'return_conditions' => $plan['return_conditions'],
                'unbinding_conditions' => $plan['unbinding_conditions'],
                'custody_restoration_conditions' => $plan['custody_restoration_conditions'],
                'retirement_conditions' => $plan['retirement_conditions'],
            ],
            'producer' => ['office' => 'curia', 'kind' => 'mechanical-service', 'service' => 'delegate-mission-capability-demand'],
            'consumer' => ['office' => 'guildhall', 'seat' => self::CONSUMER, 'intake_pending' => true, 'delivered' => false],
            'translation_boundary' => [
                'name' => 'CAPABILITY_TO_PROFESSION',
                'owner' => self::CONSUMER,
                'source_language' => 'FUNCTIONAL_CAPABILITIES',
                'curia_profession_selection_authority' => false,
                'curia_persona_selection_authority' => false,
            ],
            'sealed_at' => $sealedAt->format(DATE_ATOM),
            'status' => 'DELEGATE_MISSION_CAPABILITY_DEMAND_SEALED_PENDING_GUILDHALL_INTAKE_NO_PERSONNEL_AUTHORITY',
            'sealed' => true,
        ];
        foreach (self::FALSE_AUTHORITIES as $authority) {
            $record[$authority] = false;
        }

        return $this->save($demandId, $record);
    }

    private function validateChain(string $authorizationId, array $authorization, string $dossierId, array $dossier, string $reviewId, array $review): void
    {
        $source = $authorization['authority_source'] ?? [];
        $derivation = $authorization['derivation_authority'] ?? [];
        $reviewDerivation = $review['mission_authorization_derivation_authority'] ?? [];
        if (!$this->valid($authorization)
            || 'imperium.mission-authorization/v1' !== ($authorization['schema'] ?? null)
            || $authorizationId !== ($authorization['authorization_id'] ?? null)
            || 'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION' !== ($authorization['status'] ?? null)
            || true !== ($authorization['sealed'] ?? null)
            || true !== ($authorization['direct_execution_prohibited'] ?? null)
            || true !== ($authorization['silent_scope_expansion_prohibited'] ?? null)
            || true !== ($derivation['consumed'] ?? null)
            || true === ($derivation['continuing_authority'] ?? null)
            || true === ($authorization['profile_mutation_performed'] ?? null)
            || true === ($authorization['credential_release_performed'] ?? null)
            || true === ($authorization['provider_invocation_performed'] ?? null)
            || true === ($authorization['deployment_performed'] ?? null)
            || true === ($authorization['external_effect_performed'] ?? null)
            || true === ($authorization['execution_performed'] ?? null)
            || true === ($authorization['execution_authority'] ?? null)
            || !$this->valid($dossier)
            || 'imperium.curia-planning-dossier/v1' !== ($dossier['schema'] ?? null)
            || $dossierId !== ($dossier['dossier_id'] ?? null)
            || ($source['dossier']['version'] ?? null) !== ($dossier['dossier_version'] ?? null)
            || ($source['dossier']['digest'] ?? null) !== ($dossier['record_digest'] ?? null)
            || ($authorization['instance_id'] ?? null) !== ($dossier['instance_id'] ?? null)
            || CanonicalJson::encode($authorization['mission_plan'] ?? null) !== CanonicalJson::encode($dossier['mission_plan'] ?? null)
            || CanonicalJson::encode($authorization['authorized_dossier_lines'] ?? null) !== CanonicalJson::encode($dossier['lines'] ?? null)
            || !$this->valid($review)
            || 'imperium.imperator-planning-dossier-review/v1' !== ($review['schema'] ?? null)
            || $reviewId !== ($review['review_id'] ?? null)
            || ($source['imperator_review']['digest'] ?? null) !== ($review['record_digest'] ?? null)
            || 'APPROVE_DOSSIER' !== ($review['disposition'] ?? null)
            || true !== ($review['dossier_approval'] ?? null)
            || true !== ($review['all_lines_acknowledged'] ?? null)
            || 'IMPERATOR_PLANNING_DOSSIER_APPROVED_PENDING_MISSION_AUTHORIZATION' !== ($review['status'] ?? null)
            || ($review['dossier']['id'] ?? null) !== $dossierId
            || ($review['dossier']['version'] ?? null) !== ($dossier['dossier_version'] ?? null)
            || ($review['dossier']['digest'] ?? null) !== ($dossier['record_digest'] ?? null)
            || ($reviewDerivation['authority_id'] ?? null) !== ($source['derivation_authority_id'] ?? null)
            || ($derivation['id'] ?? null) !== ($source['derivation_authority_id'] ?? null)) {
            throw new \RuntimeException('CUR494_DELEGATE_MISSION_CAPABILITY_DEMAND_CHAIN_INVALID');
        }
    }

    private function validateDelegateDemandPlan(mixed $plan): void
    {
        if (!is_array($plan)
            || !is_string($plan['objective'] ?? null)
            || '' === trim($plan['objective'])
            || !is_string($plan['mission_seat'] ?? null)
            || 1 !== preg_match('/^[a-z0-9][a-z0-9.-]{2,127}$/', $plan['mission_seat'])
            || !$this->validDuration($plan['bounded_duration'] ?? null)
            || $this->containsPersonnelSelectionKey($plan)) {
            throw new \RuntimeException('CUR495_DELEGATE_MISSION_CAPABILITY_DEMAND_PLAN_INVALID');
        }
        foreach (self::REQUIRED_LISTS as $field) {
            if (!$this->nonEmptyUniqueStrings($plan[$field] ?? null)) {
                throw new \RuntimeException('CUR495_DELEGATE_MISSION_CAPABILITY_DEMAND_PLAN_INVALID');
            }
        }
    }

    private function validDuration(mixed $duration): bool
    {
        return is_array($duration)
            && ['maximum', 'unit', 'starts_when', 'expires_when'] === array_keys($duration)
            && is_int($duration['maximum'])
            && $duration['maximum'] > 0
            && in_array($duration['unit'], ['minutes', 'hours', 'days'], true)
            && is_string($duration['starts_when'])
            && '' !== trim($duration['starts_when'])
            && is_string($duration['expires_when'])
            && '' !== trim($duration['expires_when']);
    }

    private function nonEmptyUniqueStrings(mixed $values): bool
    {
        if (!is_array($values) || [] === $values || array_values($values) !== $values) {
            return false;
        }
        foreach ($values as $value) {
            if (!is_string($value) || '' === trim($value)) {
                return false;
            }
        }

        return array_values(array_unique($values)) === $values;
    }

    private function containsPersonnelSelectionKey(array $value): bool
    {
        foreach ($value as $key => $nested) {
            if (is_string($key) && in_array(strtolower($key), ['profession', 'profession_id', 'profession_requirements', 'persona', 'persona_id', 'persona_version', 'persona_selection'], true)) {
                return true;
            }
            if (is_array($nested) && $this->containsPersonnelSelectionKey($nested)) {
                return true;
            }
        }

        return false;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->demands) && !mkdir($this->demands, 0770, true) && !is_dir($this->demands)) {
            throw new \RuntimeException('CUR496_DELEGATE_MISSION_CAPABILITY_DEMAND_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->demands.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CUR496_DELEGATE_MISSION_CAPABILITY_DEMAND_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CUR496_DELEGATE_MISSION_CAPABILITY_DEMAND_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CUR496_DELEGATE_MISSION_CAPABILITY_DEMAND_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
