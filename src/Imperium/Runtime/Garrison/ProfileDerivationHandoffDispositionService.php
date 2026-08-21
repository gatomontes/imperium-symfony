<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileDerivationHandoffDispositionService
{
    private const array DISPOSITIONS = ['APPROVED', 'REFUSED'];
    private string $inbox;
    private string $acceptanceDirectory;
    private string $reservationDirectory;
    private string $custodyDirectory;
    private string $occupancyDirectory;
    private string $dispositionDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->inbox = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-inbox';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/conscription/profile-derivation-authorization-acceptances';
        $this->reservationDirectory = $projectDir.'/var/imperium/offices/garrison/persona-reservation-dispositions';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/garrison/occupancy';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-dispositions';
    }

    public function decide(string $requestId, string $bindingId, string $disposition, string $rationale): array
    {
        if (!preg_match('/^profile-derivation-handoff-request-[a-f0-9]{20}$/', $requestId)) throw new \InvalidArgumentException('GA99_PROFILE_DERIVATION_HANDOFF_REQUEST_ID_INVALID');
        if ('' === trim($bindingId)) throw new \InvalidArgumentException('GA100_CONSTABLE_BINDING_ID_INVALID');
        $disposition = strtoupper(trim($disposition)); $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) throw new \InvalidArgumentException('GA101_PROFILE_DERIVATION_HANDOFF_DISPOSITION_INVALID');

        $request = $this->read($this->inbox.'/'.$requestId.'.json', 'GA102_PROFILE_DERIVATION_HANDOFF_CHAIN_INVALID');
        $acceptanceId = $request['source_acceptance']['id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'GA102_PROFILE_DERIVATION_HANDOFF_CHAIN_INVALID') : [];
        $reservationId = $request['source_reservation_disposition']['id'] ?? null;
        $reservation = is_string($reservationId) ? $this->read($this->reservationDirectory.'/'.$reservationId.'.json', 'GA102_PROFILE_DERIVATION_HANDOFF_CHAIN_INVALID') : [];
        $custodyId = $request['custody']['id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'GA103_PROFILE_DERIVATION_CUSTODY_INVALID') : [];
        $constable = $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'GA104_CONSTABLE_UNAVAILABLE');
        $this->validateChain($requestId, $bindingId, $request, $acceptance, $reservation, $custody, $constable);

        foreach (glob($this->dispositionDirectory.'/profile-derivation-handoff-disposition-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'GA106_PROFILE_DERIVATION_HANDOFF_LEDGER_INVALID');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('GA106_PROFILE_DERIVATION_HANDOFF_LEDGER_INVALID');
            if (($prior['source_request']['id'] ?? null) === $requestId) {
                if (($prior['disposition'] ?? null) === $disposition && ($prior['rationale'] ?? null) === $rationale && ($prior['constable']['binding_id'] ?? null) === $bindingId) return $prior;
                throw new \RuntimeException('GA107_PROFILE_DERIVATION_HANDOFF_DISPOSITION_CONFLICT');
            }
        }

        $approved = 'APPROVED' === $disposition;
        $id = 'profile-derivation-handoff-disposition-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $request['record_digest'], $bindingId, $constable['record_digest'], $disposition, $rationale])), 0, 20);
        return $this->persist($id, [
            'schema' => 'imperium.garrison-profile-derivation-handoff-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['proceeding_id'],
            'constable' => ['seat' => $constable['seat'], 'binding_id' => $bindingId, 'binding_digest' => $constable['record_digest'], 'manifestation_id' => $constable['manifestation_id'], 'occupancy_generation' => $constable['occupancy_generation']],
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_acceptance' => $request['source_acceptance'],
            'source_authorization_act' => $request['source_authorization_act'],
            'source_reservation_disposition' => $request['source_reservation_disposition'],
            'source_plan' => $request['source_plan'],
            'profile_scope' => $request['profile_scope'],
            'persona' => $request['persona'],
            'custody' => ['id' => $custodyId, 'digest' => $custody['record_digest'], 'state' => 'ADMITTED_HELD', 'retained_by' => 'garrison'],
            'lease_scope' => 'CUSTODY_BOUND_PROFILE_DERIVATION_ONLY',
            'disposition' => $disposition,
            'rationale' => $rationale,
            'status' => $approved ? 'PROFILE_DERIVATION_HANDOFF_APPROVED_PENDING_CONSCRIPTION_LABORATORIUM_COMMISSION' : 'PROFILE_DERIVATION_HANDOFF_REFUSED',
            'handoff_authority' => $approved,
            'conscription_laboratorium_commission_request_authority' => $approved,
            'custody_release_authority' => false,
            'persona_substitution_authority' => false,
            'profile_artifact_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validateChain(string $requestId, string $bindingId, array $request, array $acceptance, array $reservation, array $custody, array $constable): void
    {
        if (!$this->digestMatches($request) || !$this->digestMatches($acceptance) || !$this->digestMatches($reservation) || !$this->digestMatches($custody) || !$this->digestMatches($constable)
            || 'imperium.conscription-garrison-profile-derivation-handoff-request/v1' !== ($request['schema'] ?? null) || $requestId !== ($request['request_id'] ?? null)
            || 'PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION' !== ($request['status'] ?? null) || 'CUSTODY_BOUND_PROFILE_DERIVATION_ONLY' !== ($request['requested_handoff'] ?? null)
            || true !== ($request['handoff_requested'] ?? null) || true === ($request['handoff_authority'] ?? null) || true === ($request['custody_release_authority'] ?? null)
            || true === ($request['laboratorium_commission_authority'] ?? null) || true === ($request['spawning_authority'] ?? null) || true === ($request['deployment_authority'] ?? null) || true === ($request['execution_authority'] ?? null)
            || 'imperium.conscription-profile-derivation-authorization-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || ($request['source_acceptance']['digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || 'PROFILE_DERIVATION_ACCEPTED_PENDING_CONSTABLE_HANDOFF_DISPOSITION' !== ($acceptance['status'] ?? null) || true !== ($acceptance['garrison_handoff_request_authority'] ?? null)
            || ($request['instance_id'] ?? null) !== ($acceptance['instance_id'] ?? null) || ($request['proceeding_id'] ?? null) !== ($acceptance['proceeding_id'] ?? null)
            || CanonicalJson::encode($request['source_authorization_act'] ?? null) !== CanonicalJson::encode($acceptance['source_authorization_act'] ?? null)
            || CanonicalJson::encode($request['source_reservation_disposition'] ?? null) !== CanonicalJson::encode($acceptance['source_reservation_disposition'] ?? null)
            || CanonicalJson::encode($request['source_plan'] ?? null) !== CanonicalJson::encode($acceptance['source_plan'] ?? null)
            || CanonicalJson::encode($request['profile_scope'] ?? null) !== CanonicalJson::encode($acceptance['profile_scope'] ?? null)
            || 'imperium.garrison-persona-reservation-disposition/v1' !== ($reservation['schema'] ?? null)
            || ($request['source_reservation_disposition']['digest'] ?? null) !== ($reservation['record_digest'] ?? null)
            || 'RESERVED' !== ($reservation['disposition'] ?? null) || 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION' !== ($reservation['status'] ?? null) || true !== ($reservation['persona_reserved'] ?? null)
            || CanonicalJson::encode($request['persona'] ?? null) !== CanonicalJson::encode($reservation['personnel_commitment']['persona'] ?? null)
            || ($request['custody']['id'] ?? null) !== ($reservation['custody_id'] ?? null) || ($request['custody']['digest'] ?? null) !== ($reservation['custody_digest'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null) || ($request['custody']['id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($request['custody']['digest'] ?? null) !== ($custody['record_digest'] ?? null) || ($request['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null)
            || ($request['instance_id'] ?? null) !== ($custody['instance_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || !in_array($constable['schema'] ?? null, ['imperium.garrison-constable-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true) || $bindingId !== ($constable['binding_id'] ?? null)
            || 'garrison.constable' !== ($constable['seat'] ?? null) || 'ACTIVE' !== ($constable['status'] ?? null) || ($request['instance_id'] ?? null) !== ($constable['instance_id'] ?? null)
            || true !== ($constable['profile_derivation_handoff_disposition_authority'] ?? null) || true === ($constable['selection_authority'] ?? null) || true === ($constable['execution_authority'] ?? null)
            || true !== ($request['sealed'] ?? null) || true !== ($acceptance['sealed'] ?? null) || true !== ($reservation['sealed'] ?? null)
        ) throw new \RuntimeException('GA105_PROFILE_DERIVATION_HANDOFF_CHAIN_INVALID');
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
        if (!is_dir($this->dispositionDirectory) && !mkdir($this->dispositionDirectory, 0770, true) && !is_dir($this->dispositionDirectory)) throw new \RuntimeException('GA108_PROFILE_DERIVATION_HANDOFF_DISPOSITION_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->dispositionDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'GA107_PROFILE_DERIVATION_HANDOFF_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('GA107_PROFILE_DERIVATION_HANDOFF_DISPOSITION_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary); throw new \RuntimeException('GA108_PROFILE_DERIVATION_HANDOFF_DISPOSITION_FAILED');
        }
        return $record;
    }
}
