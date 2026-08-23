<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class ImperatorActs
{
    private const string IMPERATOR_ID = 'imperator-development-root';

    public function __construct(private ProceedingStore $store)
    {
    }

    public function approvePlan(string $proceedingId, int $turnSequence, ?string $actId = null): array
    {
        [$proceeding, $turn] = $this->plan($proceedingId, $turnSequence);
        $actId ??= 'approval-'.substr(hash('sha256', CanonicalJson::encode([
            $proceedingId, $turnSequence, $turn['record_digest'], self::IMPERATOR_ID,
        ])), 0, 24);
        $act = [
            'schema' => 'imperium.imperator-plan-approval/v1',
            'kind' => 'PLAN_APPROVAL',
            'proceeding_id' => $proceedingId,
            'instance_id' => $proceeding['instance_id'],
            'plan_turn' => $turnSequence,
            'plan_digest' => $turn['record_digest'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'disposition' => 'APPROVED',
            'grants_resource_authority' => false,
            'grants_execution_authority' => false,
        ];

        return $this->withReadiness($this->store->persistAct($proceedingId, $actId, $act), $proceedingId, $turn);
    }

    public function objectToPlan(string $proceedingId, int $turnSequence, string $objection, ?string $actId = null): array
    {
        [$proceeding, $turn] = $this->plan($proceedingId, $turnSequence);
        $objection = trim($objection);
        if ('' === $objection) throw new \InvalidArgumentException('C34_PLAN_OBJECTION_EMPTY: an exact objection is required.');
        $actId ??= 'objection-'.substr(hash('sha256', CanonicalJson::encode([$proceedingId, $turnSequence, $turn['record_digest'], $objection, self::IMPERATOR_ID])), 0, 24);
        $act = ['schema' => 'imperium.imperator-plan-objection/v1','kind' => 'PLAN_OBJECTION','proceeding_id' => $proceedingId,'instance_id' => $proceeding['instance_id'],'plan_turn' => $turnSequence,'plan_digest' => $turn['record_digest'],'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],'authority_basis' => 'development-local-cli','disposition' => 'OBJECTED_RETURNED_FOR_REVISION','objection' => $objection,'approves_plan' => false,'grants_resource_authority' => false,'grants_execution_authority' => false];

        return $this->withReadiness($this->store->persistAct($proceedingId, $actId, $act), $proceedingId, $turn);
    }

    public function authorizeResources(string $proceedingId, int $turnSequence, array $resources, ?string $limitations = null, ?string $actId = null): array
    {
        [$proceeding, $turn] = $this->plan($proceedingId, $turnSequence);
        $demands = array_values(array_unique($turn['resource_demands'] ?? []));
        $resources = array_values(array_unique(array_map('trim', $resources)));
        if ([] === $resources || in_array('', $resources, true)) {
            throw new \InvalidArgumentException('At least one exact declared resource demand is required.');
        }
        $undeclared = array_values(array_diff($resources, $demands));
        if ([] !== $undeclared) {
            throw new \RuntimeException('C32_UNDECLARED_RESOURCE: '.implode('; ', $undeclared));
        }
        $actId ??= 'authorization-'.substr(hash('sha256', CanonicalJson::encode([
            $proceedingId, $turnSequence, $turn['record_digest'], $resources, $limitations, self::IMPERATOR_ID,
        ])), 0, 24);
        $act = [
            'schema' => 'imperium.imperator-resource-authorization/v1',
            'kind' => 'RESOURCE_AUTHORIZATION',
            'proceeding_id' => $proceedingId,
            'instance_id' => $proceeding['instance_id'],
            'plan_turn' => $turnSequence,
            'plan_digest' => $turn['record_digest'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'disposition' => 'AUTHORIZED',
            'resources' => $resources,
            'limitations' => null === $limitations ? null : trim($limitations),
            'approves_plan' => false,
            'grants_execution_authority' => false,
        ];

        return $this->withReadiness($this->store->persistAct($proceedingId, $actId, $act), $proceedingId, $turn);
    }

    private function plan(string $proceedingId, int $turnSequence): array
    {
        $proceeding = $this->store->find($proceedingId);
        $turn = $this->store->turn($proceedingId, $turnSequence);
        if (null === $proceeding || null === $turn) {
            throw new \RuntimeException('C31_PLAN_NOT_FOUND: proceeding or plan turn does not exist.');
        }
        if ('MISSION_PLAN_DRAFTED' !== ($turn['seneschal']['disposition'] ?? null)) {
            throw new \RuntimeException('C31_PLAN_NOT_FOUND: referenced turn is not a drafted Mission Plan.');
        }
        if (!is_array($turn['seneschal']['mission_plan'] ?? null)) {
            throw new \RuntimeException('C33_PLAN_NOT_COMMISSIONABLE: drafted plan lacks the structured commissioning contract.');
        }

        return [$proceeding, $turn];
    }

    private function withReadiness(array $act, string $proceedingId, array $turn): array
    {
        $approved = false;
        $objected = false;
        $authorized = [];
        foreach ($this->store->acts($proceedingId) as $record) {
            if (($record['plan_digest'] ?? null) !== $turn['record_digest']) {
                continue;
            }
            if ('PLAN_APPROVAL' === ($record['kind'] ?? null) && 'APPROVED' === ($record['disposition'] ?? null)) {
                $approved = true;
                $objected = false;
            }
            if ('PLAN_OBJECTION' === ($record['kind'] ?? null) && 'OBJECTED_RETURNED_FOR_REVISION' === ($record['disposition'] ?? null)) {
                $approved = false;
                $objected = true;
            }
            if ('RESOURCE_AUTHORIZATION' === ($record['kind'] ?? null) && 'AUTHORIZED' === ($record['disposition'] ?? null)) {
                $authorized = array_values(array_unique([...$authorized, ...($record['resources'] ?? [])]));
            }
        }
        $demands = array_values(array_unique($turn['resource_demands'] ?? []));
        $structured = is_array($turn['seneschal']['mission_plan'] ?? null);
        $act['readiness'] = [
            'plan_approved' => $approved,
            'unresolved_plan_objection' => $objected,
            'plan_structured' => $structured,
            'resource_demands_satisfied' => [] === array_diff($demands, $authorized),
            'commissioning_ready' => $structured && $approved && [] === array_diff($demands, $authorized),
            'execution_authorized' => false,
        ];

        return $act;
    }
}
