<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LaboratoriumProfileDerivationCommissionService
{
    private string $dispositionDirectory;
    private string $handoffDirectory;
    private string $acceptanceDirectory;
    private string $reservationDirectory;
    private string $custodyDirectory;
    private string $commissionDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir, private StateStore $bootstrap)
    {
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-dispositions';
        $this->handoffDirectory = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-inbox';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/conscription/profile-derivation-authorization-acceptances';
        $this->reservationDirectory = $projectDir.'/var/imperium/offices/garrison/persona-reservation-dispositions';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->commissionDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-inbox';
    }

    public function commission(string $dispositionId): array
    {
        if (!preg_match('/^profile-derivation-handoff-disposition-[a-f0-9]{20}$/', $dispositionId)) throw new \InvalidArgumentException('R83_PROFILE_DERIVATION_HANDOFF_DISPOSITION_ID_INVALID');
        $disposition = $this->read($this->dispositionDirectory.'/'.$dispositionId.'.json', 'R84_PROFILE_DERIVATION_HANDOFF_DISPOSITION_ABSENT');
        $handoffId = $disposition['source_request']['id'] ?? null;
        $handoff = is_string($handoffId) ? $this->read($this->handoffDirectory.'/'.$handoffId.'.json', 'R85_PROFILE_DERIVATION_HANDOFF_REQUEST_ABSENT') : [];
        $acceptanceId = $disposition['source_acceptance']['id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'R86_PROFILE_DERIVATION_ACCEPTANCE_ABSENT') : [];
        $reservationId = $disposition['source_reservation_disposition']['id'] ?? null;
        $reservation = is_string($reservationId) ? $this->read($this->reservationDirectory.'/'.$reservationId.'.json', 'R87_PROFILE_DERIVATION_RESERVATION_ABSENT') : [];
        $custodyId = $disposition['custody']['id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'R88_PROFILE_DERIVATION_CUSTODY_ABSENT') : [];
        $this->validateChain($dispositionId, $disposition, $handoff, $acceptance, $reservation, $custody);
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        if ($instanceId !== $disposition['instance_id']) throw new \RuntimeException('R90_PROFILE_DERIVATION_COMMISSION_INSTANCE_MISMATCH');

        foreach (glob($this->commissionDirectory.'/profile-derivation-commission-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'R93_PROFILE_DERIVATION_COMMISSION_LEDGER_INVALID');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('R93_PROFILE_DERIVATION_COMMISSION_LEDGER_INVALID');
            if (($prior['source_handoff_disposition']['id'] ?? null) === $dispositionId) return $prior;
        }
        $actor = ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']];
        $commissionId = 'profile-derivation-commission-'.substr(hash('sha256', CanonicalJson::encode([$dispositionId, $disposition['record_digest'], $actor])), 0, 20);
        return $this->persist($commissionId, [
            'schema' => 'imperium.conscription-laboratorium-profile-derivation-commission/v1',
            'commission_id' => $commissionId,
            'instance_id' => $instanceId,
            'proceeding_id' => $disposition['proceeding_id'],
            'issuer' => $actor,
            'recipient' => ['office' => 'laboratorium', 'seat' => 'laboratorium.alchemist'],
            'source_handoff_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
            'source_handoff_request' => $disposition['source_request'],
            'source_acceptance' => $disposition['source_acceptance'],
            'source_authorization_act' => $disposition['source_authorization_act'],
            'source_reservation_disposition' => $disposition['source_reservation_disposition'],
            'source_plan' => $disposition['source_plan'],
            'persona' => $disposition['persona'],
            'custody_lease' => ['custody_id' => $custodyId, 'custody_digest' => $custody['record_digest'], 'scope' => 'CUSTODY_BOUND_PROFILE_DERIVATION_ONLY', 'custody_state' => 'ADMITTED_HELD', 'custodian' => 'garrison'],
            'profile_scope' => $disposition['profile_scope'],
            'commission_scope' => 'DERIVE_ONE_EXACT_MISSION_PROFILE',
            'return_destination' => ['office' => 'conscription', 'seat' => 'conscription.recruiter'],
            'status' => 'PENDING_ALCHEMIST_PROFILE_DERIVATION_COMMISSION_ACCEPTANCE',
            'recipient_acceptance' => false,
            'profile_derivation_authority' => true,
            'profile_derivation_authority_exercisable' => false,
            'profile_artifact_authority' => false,
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

    private function validateChain(string $dispositionId, array $disposition, array $handoff, array $acceptance, array $reservation, array $custody): void
    {
        if (!$this->digestMatches($disposition) || !$this->digestMatches($handoff) || !$this->digestMatches($acceptance) || !$this->digestMatches($reservation) || !$this->digestMatches($custody)
            || 'imperium.garrison-profile-derivation-handoff-disposition/v1' !== ($disposition['schema'] ?? null) || $dispositionId !== ($disposition['disposition_id'] ?? null)
            || 'APPROVED' !== ($disposition['disposition'] ?? null) || 'PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION' !== ($disposition['status'] ?? null)
            || true !== ($disposition['handoff_authority'] ?? null) || true !== ($disposition['conscription_laboratorium_commission_request_authority'] ?? null)
            || true === ($disposition['custody_release_authority'] ?? null) || true === ($disposition['persona_substitution_authority'] ?? null) || true === ($disposition['execution_authority'] ?? null)
            || 'CUSTODY_BOUND_PROFILE_DERIVATION_ONLY' !== ($disposition['lease_scope'] ?? null) || 'ADMITTED_HELD' !== ($disposition['custody']['state'] ?? null) || 'garrison' !== ($disposition['custody']['retained_by'] ?? null)
            || 'imperium.conscription-garrison-profile-derivation-handoff-request/v1' !== ($handoff['schema'] ?? null)
            || ($disposition['source_request']['digest'] ?? null) !== ($handoff['record_digest'] ?? null) || 'PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION' !== ($handoff['status'] ?? null)
            || 'imperium.conscription-profile-derivation-authorization-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || ($disposition['source_acceptance']['digest'] ?? null) !== ($acceptance['record_digest'] ?? null) || 'PROFILE_DERIVATION_ACCEPTED_PENDING_CONSTABLE_HANDOFF_DISPOSITION' !== ($acceptance['status'] ?? null)
            || 'imperium.garrison-persona-reservation-disposition/v1' !== ($reservation['schema'] ?? null)
            || ($disposition['source_reservation_disposition']['digest'] ?? null) !== ($reservation['record_digest'] ?? null) || 'RESERVED' !== ($reservation['disposition'] ?? null) || true !== ($reservation['persona_reserved'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null) || ($disposition['custody']['digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($disposition['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || ($disposition['instance_id'] ?? null) !== ($handoff['instance_id'] ?? null) || ($disposition['instance_id'] ?? null) !== ($acceptance['instance_id'] ?? null) || ($disposition['instance_id'] ?? null) !== ($reservation['instance_id'] ?? null) || ($disposition['instance_id'] ?? null) !== ($custody['instance_id'] ?? null)
            || CanonicalJson::encode($disposition['source_plan'] ?? null) !== CanonicalJson::encode($handoff['source_plan'] ?? null)
            || CanonicalJson::encode($disposition['profile_scope'] ?? null) !== CanonicalJson::encode($handoff['profile_scope'] ?? null)
            || CanonicalJson::encode($disposition['profile_scope'] ?? null) !== CanonicalJson::encode($acceptance['profile_scope'] ?? null)
            || CanonicalJson::encode($disposition['persona'] ?? null) !== CanonicalJson::encode($handoff['persona'] ?? null)
            || 'conscription.recruiter' !== ($disposition['profile_scope']['prospective_commissioner_and_installer'] ?? null) || 'laboratorium.alchemist' !== ($disposition['profile_scope']['prospective_transformer'] ?? null)
            || true !== ($disposition['sealed'] ?? null) || true !== ($handoff['sealed'] ?? null) || true !== ($acceptance['sealed'] ?? null) || true !== ($reservation['sealed'] ?? null)
        ) throw new \RuntimeException('R89_PROFILE_DERIVATION_COMMISSION_CHAIN_INVALID');
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) throw new \RuntimeException('R91_RECRUITER_UNAVAILABLE');
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null) && is_string($recruiter['manifestation_id'] ?? null)) return [$state['binding']['instance_id'] ?? null, $recruiter];
        }
        throw new \RuntimeException('R91_RECRUITER_UNAVAILABLE');
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
        if (!is_dir($this->commissionDirectory) && !mkdir($this->commissionDirectory, 0770, true) && !is_dir($this->commissionDirectory)) throw new \RuntimeException('R92_PROFILE_DERIVATION_COMMISSION_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->commissionDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'R94_PROFILE_DERIVATION_COMMISSION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('R94_PROFILE_DERIVATION_COMMISSION_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('R92_PROFILE_DERIVATION_COMMISSION_FAILED'); }
        return $record;
    }
}
