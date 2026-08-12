<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class CommissioningService
{
    public function __construct(private ProceedingStore $store)
    {
    }

    public function issue(string $proceedingId, int $turnSequence): array
    {
        $proceeding = $this->store->find($proceedingId);
        $turn = $this->store->turn($proceedingId, $turnSequence);
        if (null === $proceeding || null === $turn || 'MISSION_PLAN_DRAFTED' !== ($turn['seneschal']['disposition'] ?? null)) {
            throw new \RuntimeException('C41_COMMISSION_PLAN_INVALID: exact drafted-plan turn is unavailable.');
        }
        $plan = $turn['seneschal']['mission_plan'] ?? null;
        if (!is_array($plan)) {
            throw new \RuntimeException('C41_COMMISSION_PLAN_INVALID: structured Mission Plan is absent.');
        }

        $approval = null;
        $authorizations = [];
        $authorizedResources = [];
        foreach ($this->store->acts($proceedingId) as $act) {
            if (($act['plan_digest'] ?? null) !== ($turn['record_digest'] ?? null)) {
                continue;
            }
            if ('PLAN_APPROVAL' === ($act['kind'] ?? null) && 'APPROVED' === ($act['disposition'] ?? null)) {
                $approval = $act;
            }
            if ('RESOURCE_AUTHORIZATION' === ($act['kind'] ?? null) && 'AUTHORIZED' === ($act['disposition'] ?? null)) {
                $authorizations[] = $act;
                $authorizedResources = array_values(array_unique([...$authorizedResources, ...($act['resources'] ?? [])]));
            }
        }
        $demands = array_values(array_unique($turn['resource_demands'] ?? []));
        if (null === $approval || [] !== array_diff($demands, $authorizedResources)) {
            throw new \RuntimeException('C42_COMMISSION_AUTHORITY_INCOMPLETE: exact approval and all resource authorizations are required.');
        }

        $participation = $plan['office_participation'] ?? [];
        $guildhallParticipation = $this->mentioningOffice($participation, 'Guildhall');
        $armoryParticipation = $this->mentioningOffice($participation, 'Armory');
        if ([] === $guildhallParticipation || [] === $armoryParticipation) {
            throw new \RuntimeException('C43_COMMISSION_DESTINATION_UNDECLARED: Guildhall and Armory participation must be explicit.');
        }

        $authority = [
            'approval_act_id' => $approval['act_id'],
            'authorization_act_ids' => array_column($authorizations, 'act_id'),
            'limitations' => array_values(array_filter(array_column($authorizations, 'limitations'), 'is_string')),
            'plan_turn' => $turnSequence,
            'plan_digest' => $turn['record_digest'],
        ];
        $specifications = [
            'guildhall' => [
                'target' => 'guildhall.guildmaster',
                'purpose' => 'Determine required professions and personnel suitability, obtain exact Garrison Persona and personnel inventory facts, and return a Personnel Disposition.',
                'authorized_resources' => $this->guildhallResources($demands),
                'expected_products' => ['Profession Determination Packet', 'Personnel Disposition'],
                'forbidden_effects' => ['persona construction', 'recruitment', 'manifestation', 'reservation', 'deployment'],
            ],
            'armory' => [
                'target' => 'armory',
                'purpose' => 'Determine admissible passive methodology, checklists, and tooling under the exact Mission Plan constraints.',
                'authorized_resources' => $this->armoryResources($demands),
                'expected_products' => ['Tooling Disposition'],
                'forbidden_effects' => ['tool activation', 'target access', 'credential use', 'assessment execution'],
            ],
        ];

        $issued = [];
        foreach ($specifications as $name => $specification) {
            if ([] === $specification['authorized_resources']) {
                throw new \RuntimeException('C44_COMMISSION_RESOURCE_UNDECLARED: '.$name.' has no exact authorized resource demand.');
            }
            $identity = [$proceedingId, $turn['record_digest'], $name, $authority];
            $commissionId = 'planning-'.$name.'-'.substr(hash('sha256', CanonicalJson::encode($identity)), 0, 20);
            $packet = [
                'schema' => 'imperium.planning-commission/v1',
                'phase' => 'planning-only',
                'proceeding_id' => $proceedingId,
                'instance_id' => $proceeding['instance_id'],
                'issuer' => ['seat' => 'curia.seneschal', 'source' => 'approved-structured-mission-plan'],
                'target' => $specification['target'],
                'purpose' => $specification['purpose'],
                'authorized_resources' => $specification['authorized_resources'],
                'expected_products' => $specification['expected_products'],
                'forbidden_effects' => $specification['forbidden_effects'],
                'authority' => $authority,
                'status' => 'ISSUED_PENDING_RECIPIENT',
                'execution_authority' => false,
            ];
            $issued[$name] = $this->store->persistCommission($proceedingId, $commissionId, $packet);
        }

        return [
            'proceeding_id' => $proceedingId,
            'plan_turn' => $turnSequence,
            'commissions' => $issued,
            'mechanical_support' => $this->matchingText($demands, 'secure document storage', 'standard office productivity'),
            'execution_authority' => false,
        ];
    }

    private function mentioningOffice(array $values, string $office): array
    {
        return array_values(array_filter($values, static fn (mixed $value): bool => is_string($value)
            && 1 === preg_match('/\\b'.preg_quote($office, '/').'\\b/i', $value)));
    }

    private function guildhallResources(array $demands): array
    {
        return array_values(array_filter($demands, function (mixed $value): bool {
            if (!is_string($value)) {
                return false;
            }

            return [] !== $this->mentioningOffice([$value], 'Guildhall')
                || ([] !== $this->mentioningOffice([$value], 'Garrison')
                    && [] !== $this->matchingText([$value], 'personnel inventory', 'persona and personnel inventory'));
        }));
    }

    private function armoryResources(array $demands): array
    {
        return array_values(array_filter($demands, fn (mixed $value): bool => is_string($value)
            && [] !== $this->mentioningOffice([$value], 'Armory')
            && [] !== $this->matchingText([$value], 'tooling', 'methodology', 'checklist')));
    }

    private function matchingText(array $values, string ...$needles): array
    {
        return array_values(array_filter($values, static function (mixed $value) use ($needles): bool {
            if (!is_string($value)) {
                return false;
            }
            foreach ($needles as $needle) {
                if (str_contains(strtolower($value), strtolower($needle))) {
                    return true;
                }
            }

            return false;
        }));
    }
}
