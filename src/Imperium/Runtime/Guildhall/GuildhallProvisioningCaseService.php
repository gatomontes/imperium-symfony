<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;

final readonly class GuildhallProvisioningCaseService
{
    private string $demandDirectory;
    private string $caseDirectory;

    public function __construct(string $projectDir, private ProfileDefinitionRegistry $definitions)
    {
        $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests';
        $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases';
    }

    public function open(string $demandId): array
    {
        if (!preg_match('/^guildhall-activation-[a-f0-9]{20}$/', $demandId)) {
            throw new \InvalidArgumentException('M30_ACTIVATION_DEMAND_INVALID: exact Guildhall activation demand identity is required.');
        }
        $path = $this->demandDirectory.'/'.$demandId.'.json';
        if (!is_file($path)) {
            throw new \RuntimeException('M31_ACTIVATION_DEMAND_ABSENT: Guildhall activation demand is unavailable.');
        }
        $demand = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!$this->digestMatches($demand)
            || 'imperium.office-activation-demand/v1' !== ($demand['schema'] ?? null)
            || $demandId !== ($demand['demand_id'] ?? null)
            || 'mastermason' !== ($demand['recipient'] ?? null)
            || 'guildhall' !== ($demand['office'] ?? null)
            || 'PROFILE_DEFINITIONS_READY' !== ($demand['status'] ?? null)
            || true === ($demand['spawning_authority'] ?? null)
            || true === ($demand['recipient_acceptance'] ?? null)
            || true === ($demand['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('M32_ACTIVATION_DEMAND_INVALID: exact non-authorizing Guildhall activation demand is required.');
        }

        $expected = [
            'guildhall.guildmaster' => ['guildmaster', 'Guildmaster'],
            'guildhall.committee.disciplinary-fit' => ['committee-disciplinary-fit', 'Disciplinary-Fit committee member'],
            'guildhall.committee.composition' => ['committee-composition', 'Composition committee member'],
            'guildhall.committee.boundary-challenge' => ['committee-boundary-challenge', 'Boundary-Challenge committee member'],
        ];
        $lanes = [];
        foreach ($demand['required_seats'] ?? [] as $required) {
            $seat = $required['seat'] ?? null;
            if (!is_string($seat) || !isset($expected[$seat])) {
                throw new \RuntimeException('M33_ACTIVATION_SEATS_INVALID: Guildhall activation demand contains an unexpected Seat.');
            }
            [$name, $role] = $expected[$seat];
            $definition = $this->definitions->current($name, $seat);
            if (CanonicalJson::encode($definition) !== CanonicalJson::encode($required['profile_definition'] ?? null)) {
                throw new \RuntimeException('M34_PROFILE_DEFINITION_STALE: Guildhall activation demand does not bind the exact current definition.');
            }
            $lanes[] = [
                'seat' => $seat,
                'profile_definition' => $definition,
                'canonical_staff_requirement' => [
                    'role' => $role,
                    'mission_persona_selection' => false,
                    'persona_admission_state' => 'admitted',
                    'current_profile_required' => true,
                    'qualified_manifestation_required' => true,
                    'status' => 'BLOCKED_PENDING_CANONICAL_STAFF_ARTIFACTS',
                ],
            ];
            unset($expected[$seat]);
        }
        if ([] !== $expected || 4 !== count($lanes)) {
            throw new \RuntimeException('M33_ACTIVATION_SEATS_INVALID: all four exact Guildhall Seats are required once.');
        }

        $caseId = 'guildhall-provisioning-'.substr(hash('sha256', CanonicalJson::encode([$demandId, $demand['record_digest'], $lanes])), 0, 20);
        $case = [
            'schema' => 'imperium.office-provisioning-case/v1',
            'case_id' => $caseId,
            'activation_demand_id' => $demandId,
            'activation_demand_digest' => $demand['record_digest'],
            'office' => 'guildhall',
            'coordinator' => 'mastermason',
            'summoning_rule' => [
                'trigger' => 'curia.personnel_question',
                'requester' => 'curia.seneschal',
                'router' => 'curia.chamberlain',
                'runtime_executor' => 'mastermason',
                'manifestation_constructor' => 'conscription',
            ],
            'lanes' => $lanes,
            'status' => 'CANONICAL_STAFF_ARTIFACTS_REQUIRED',
            'mission_persona_selection_required' => false,
            'per_mission_profile_derivation_required' => false,
            'activation_request_recorded' => false,
            'spawning_authority' => false,
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];

        return $this->persist($caseId, $case);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $caseId, array $case): array
    {
        if (!is_dir($this->caseDirectory) && !mkdir($this->caseDirectory, 0770, true) && !is_dir($this->caseDirectory)) {
            throw new \RuntimeException('MasterMason activation-case directory cannot be created.');
        }
        $path = $this->caseDirectory.'/'.$caseId.'.json';
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case));
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($case)) {
                throw new \RuntimeException('M35_PROVISIONING_REPLAY_CONFLICT: provisioning case identity is already bound differently.');
            }

            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($case, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Guildhall provisioning case cannot be committed atomically.');
        }

        return $case;
    }
}
