<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileDerivationCommissionAcceptanceService
{
    private string $commissionDirectory;
    private string $dispositionDirectory;
    private string $occupancyDirectory;
    private string $acceptanceDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->commissionDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-inbox';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-dispositions';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/laboratorium/occupancy';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-acceptances';
    }

    public function accept(string $commissionId, string $bindingId): array
    {
        if (!preg_match('/^profile-derivation-commission-[a-f0-9]{20}$/', $commissionId)) throw new \InvalidArgumentException('L20_PROFILE_DERIVATION_COMMISSION_ID_INVALID');
        if ('' === trim($bindingId)) throw new \InvalidArgumentException('L21_ALCHEMIST_BINDING_ID_INVALID');
        $commission = $this->read($this->commissionDirectory.'/'.$commissionId.'.json', 'L22_PROFILE_DERIVATION_COMMISSION_ABSENT');
        $dispositionId = $commission['source_handoff_disposition']['id'] ?? null;
        $disposition = is_string($dispositionId) ? $this->read($this->dispositionDirectory.'/'.$dispositionId.'.json', 'L23_PROFILE_DERIVATION_HANDOFF_DISPOSITION_ABSENT') : [];
        $binding = $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'L24_ALCHEMIST_UNAVAILABLE');
        $this->validate($commissionId, $bindingId, $commission, $disposition, $binding);

        foreach (glob($this->acceptanceDirectory.'/profile-derivation-commission-acceptance-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'L27_PROFILE_DERIVATION_ACCEPTANCE_LEDGER_INVALID');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('L27_PROFILE_DERIVATION_ACCEPTANCE_LEDGER_INVALID');
            if (($prior['source_commission']['id'] ?? null) === $commissionId) {
                if (($prior['alchemist']['binding_id'] ?? null) === $bindingId) return $prior;
                throw new \RuntimeException('L28_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');
            }
        }
        $id = 'profile-derivation-commission-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $bindingId, $binding['record_digest']])), 0, 20);
        return $this->persist($id, [
            'schema' => 'imperium.laboratorium-profile-derivation-commission-acceptance/v1',
            'acceptance_id' => $id,
            'instance_id' => $commission['instance_id'],
            'proceeding_id' => $commission['proceeding_id'],
            'alchemist' => ['seat' => 'laboratorium.alchemist', 'binding_id' => $bindingId, 'binding_digest' => $binding['record_digest'], 'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation']],
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_handoff_disposition' => $commission['source_handoff_disposition'],
            'source_authorization_act' => $commission['source_authorization_act'],
            'source_reservation_disposition' => $commission['source_reservation_disposition'],
            'source_plan' => $commission['source_plan'],
            'persona' => $commission['persona'],
            'custody_lease' => $commission['custody_lease'],
            'profile_scope' => $commission['profile_scope'],
            'commission_scope' => $commission['commission_scope'],
            'return_destination' => $commission['return_destination'],
            'disposition' => 'ACCEPTED_FOR_EXACT_PROFILE_DERIVATION',
            'status' => 'PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION',
            'recipient_acceptance' => true,
            'profile_derivation_authority' => true,
            'profile_derivation_authority_exercisable' => true,
            'profile_candidate_creation_authority' => true,
            'profile_artifact_created' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'custody_release_authority' => false,
            'persona_substitution_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $commissionId, string $bindingId, array $commission, array $disposition, array $binding): void
    {
        if (!$this->digestMatches($commission) || !$this->digestMatches($disposition) || !$this->digestMatches($binding)
            || 'imperium.conscription-laboratorium-profile-derivation-commission/v1' !== ($commission['schema'] ?? null) || $commissionId !== ($commission['commission_id'] ?? null)
            || 'laboratorium.alchemist' !== ($commission['recipient']['seat'] ?? null) || 'DERIVE_ONE_EXACT_MISSION_PROFILE' !== ($commission['commission_scope'] ?? null)
            || 'PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE' !== ($commission['status'] ?? null) || false !== ($commission['recipient_acceptance'] ?? null)
            || true !== ($commission['profile_derivation_authority'] ?? null) || false !== ($commission['profile_derivation_authority_exercisable'] ?? null)
            || true === ($commission['profile_artifact_authority'] ?? null) || true === ($commission['profile_approval_authority'] ?? null) || true === ($commission['profile_installation_authority'] ?? null)
            || true === ($commission['custody_release_authority'] ?? null) || true === ($commission['persona_substitution_authority'] ?? null) || true === ($commission['execution_authority'] ?? null)
            || 'CUSTODY_BOUND_PROFILE_DERIVATION_ONLY' !== ($commission['custody_lease']['scope'] ?? null) || 'ADMITTED_HELD' !== ($commission['custody_lease']['custody_state'] ?? null) || 'garrison' !== ($commission['custody_lease']['custodian'] ?? null)
            || 'conscription.recruiter' !== ($commission['return_destination']['seat'] ?? null) || 'laboratorium.alchemist' !== ($commission['profile_scope']['prospective_transformer'] ?? null)
            || 'imperium.garrison-profile-derivation-handoff-disposition/v1' !== ($disposition['schema'] ?? null)
            || ($commission['source_handoff_disposition']['digest'] ?? null) !== ($disposition['record_digest'] ?? null) || 'APPROVED' !== ($disposition['disposition'] ?? null)
            || 'PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION' !== ($disposition['status'] ?? null)
            || CanonicalJson::encode($commission['persona'] ?? null) !== CanonicalJson::encode($disposition['persona'] ?? null)
            || CanonicalJson::encode($commission['profile_scope'] ?? null) !== CanonicalJson::encode($disposition['profile_scope'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($disposition['instance_id'] ?? null)
            || !in_array($binding['schema'] ?? null, ['imperium.laboratorium-alchemist-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true)
            || $bindingId !== ($binding['binding_id'] ?? null) || 'laboratorium' !== ($binding['office'] ?? null) || 'laboratorium.alchemist' !== ($binding['seat'] ?? null)
            || 'ACTIVE' !== ($binding['status'] ?? null) || true !== ($binding['binding_atomic'] ?? null) || ($commission['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || true !== ($binding['profile_derivation_commission_acceptance_authority'] ?? null) || true === ($binding['execution_authority'] ?? null)
            || true !== ($commission['sealed'] ?? null) || true !== ($disposition['sealed'] ?? null)
        ) throw new \RuntimeException('L25_PROFILE_DERIVATION_COMMISSION_INVALID');
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->acceptanceDirectory) && !mkdir($this->acceptanceDirectory, 0770, true) && !is_dir($this->acceptanceDirectory)) throw new \RuntimeException('L26_PROFILE_DERIVATION_ACCEPTANCE_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->acceptanceDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'L28_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('L28_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('L26_PROFILE_DERIVATION_ACCEPTANCE_FAILED'); }
        return $record;
    }
}
