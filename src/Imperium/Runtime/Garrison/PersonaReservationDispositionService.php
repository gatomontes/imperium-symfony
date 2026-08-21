<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PersonaReservationDispositionService
{
    private string $inbox;
    private string $acceptanceDirectory;
    private string $dispositionDirectory;
    private string $custodyDirectory;
    private string $occupancyDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->inbox = $projectDir.'/var/imperium/offices/garrison/persona-reservation-inbox';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/guildhall/personnel-use-authorization-acceptances';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/garrison/persona-reservation-dispositions';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/garrison/occupancy';
    }

    public function decide(string $requestId, string $bindingId): array
    {
        if (!preg_match('/^persona-reservation-request-[a-f0-9]{20}$/', $requestId)) throw new \InvalidArgumentException('GA92_RESERVATION_REQUEST_ID_INVALID');
        if ('' === trim($bindingId)) throw new \InvalidArgumentException('GA93_CONSTABLE_BINDING_ID_INVALID');

        $request = $this->read($this->inbox.'/'.$requestId.'.json', 'GA94_RESERVATION_CHAIN_INVALID');
        $acceptanceId = $request['source_acceptance_id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'GA94_RESERVATION_CHAIN_INVALID') : [];
        $constable = $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'GA94_RESERVATION_CHAIN_INVALID');
        $commitment = $request['personnel_commitment'] ?? null;
        $persona = is_array($commitment) ? ($commitment['persona'] ?? null) : null;

        if (!$this->digestMatches($request) || !$this->digestMatches($acceptance) || !$this->digestMatches($constable)
            || 'imperium.guildhall-garrison-persona-reservation-request/v1' !== ($request['schema'] ?? null)
            || 'PENDING_CONSTABLE_RESERVATION_DISPOSITION' !== ($request['status'] ?? null)
            || true !== ($request['reservation_requested'] ?? null)
            || true === ($request['reservation_authority'] ?? null)
            || ($request['source_acceptance_digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || 'imperium.guildhall-personnel-use-authorization-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || 'AUTHORIZED_PERSONNEL_ACCEPTED_PENDING_GARRISON_RESERVATION' !== ($acceptance['status'] ?? null)
            || true !== ($acceptance['authorization_accepted'] ?? null)
            || true !== ($acceptance['garrison_reservation_request_authority'] ?? null)
            || ($request['imperator_act_id'] ?? null) !== ($acceptance['imperator_act_id'] ?? null)
            || ($request['imperator_act_digest'] ?? null) !== ($acceptance['imperator_act_digest'] ?? null)
            || ($request['guildhall_disposition_id'] ?? null) !== ($acceptance['guildhall_disposition_id'] ?? null)
            || ($request['guildhall_disposition_digest'] ?? null) !== ($acceptance['guildhall_disposition_digest'] ?? null)
            || !is_array($commitment) || !is_array($persona)
            || !is_string($persona['custody_id'] ?? null) || !is_string($persona['persona_id'] ?? null)
            || 'garrison.constable' !== ($constable['seat'] ?? null)
            || $bindingId !== ($constable['binding_id'] ?? null)
            || 'ACTIVE' !== ($constable['status'] ?? null)
            || ($request['instance_id'] ?? null) !== ($constable['instance_id'] ?? null)
            || true !== ($constable['persona_reservation_disposition_authority'] ?? null)
            || true === ($constable['selection_authority'] ?? null)
            || true === ($constable['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('GA94_RESERVATION_CHAIN_INVALID');
        }

        $matchingCommitments = array_values(array_filter($acceptance['personnel_commitments'] ?? [], static fn(mixed $candidate): bool => is_array($candidate) && ($candidate['capability_slot_id'] ?? null) === ($commitment['capability_slot_id'] ?? null)));
        if (1 !== count($matchingCommitments) || $matchingCommitments[0] !== $commitment) {
            return $this->record($request, $constable, 'DISPOSITION_MISMATCH', 'REFUSED_DISPOSITION_MISMATCH', null);
        }

        $custodyPath = $this->custodyDirectory.'/'.$persona['custody_id'].'.json';
        if (!is_file($custodyPath)) {
            return $this->record($request, $constable, 'PERSONA_NOT_ADMITTED', 'REFUSED_PERSONA_NOT_ADMITTED', null);
        }
        $custody = $this->read($custodyPath, 'GA95_CUSTODY_RECORD_INVALID');
        if (!$this->digestMatches($custody) || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)) throw new \RuntimeException('GA95_CUSTODY_RECORD_INVALID');
        if (($custody['persona_id'] ?? null) !== $persona['persona_id'] || ($custody['instance_id'] ?? null) !== ($request['instance_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)) {
            return $this->record($request, $constable, 'PERSONA_NOT_ADMITTED', 'REFUSED_PERSONA_NOT_ADMITTED', $custody);
        }
        if (true !== ($custody['available'] ?? null)) {
            return $this->record($request, $constable, 'PERSONA_UNAVAILABLE', 'REFUSED_PERSONA_UNAVAILABLE', $custody);
        }
        foreach (glob($this->dispositionDirectory.'/persona-reservation-disposition-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'GA96_RESERVATION_LEDGER_INVALID');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('GA96_RESERVATION_LEDGER_INVALID');
            if (($prior['source_request_id'] ?? null) === $requestId) return $prior;
            if (($prior['custody_id'] ?? null) === $persona['custody_id'] && true === ($prior['persona_reserved'] ?? null)) {
                return $this->record($request, $constable, 'PERSONA_ALREADY_RESERVED', 'REFUSED_PERSONA_ALREADY_RESERVED', $custody);
            }
        }
        return $this->record($request, $constable, 'RESERVED', 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION', $custody);
    }

    private function record(array $request, array $constable, string $disposition, string $status, ?array $custody): array
    {
        $reserved = 'RESERVED' === $disposition;
        $id = 'persona-reservation-disposition-'.substr(hash('sha256', CanonicalJson::encode([$request['request_id'], $request['record_digest'], $constable['record_digest'], $disposition, $custody['record_digest'] ?? null])), 0, 20);
        return $this->persist($id, [
            'schema' => 'imperium.garrison-persona-reservation-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['proceeding_id'],
            'source_request_id' => $request['request_id'],
            'source_request_digest' => $request['record_digest'],
            'imperator_act_id' => $request['imperator_act_id'],
            'imperator_act_digest' => $request['imperator_act_digest'],
            'guildhall_disposition_id' => $request['guildhall_disposition_id'],
            'guildhall_disposition_digest' => $request['guildhall_disposition_digest'],
            'personnel_commitment' => $request['personnel_commitment'],
            'custody_id' => $request['personnel_commitment']['persona']['custody_id'],
            'custody_digest' => $custody['record_digest'] ?? null,
            'constable' => ['seat' => $constable['seat'], 'binding_id' => $constable['binding_id'], 'binding_digest' => $constable['record_digest'], 'manifestation_id' => $constable['manifestation_id'], 'occupancy_generation' => $constable['occupancy_generation']],
            'disposition' => $disposition,
            'status' => $status,
            'persona_reserved' => $reserved,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->dispositionDirectory) && !mkdir($this->dispositionDirectory, 0770, true) && !is_dir($this->dispositionDirectory)) throw new \RuntimeException('GA97_RESERVATION_DISPOSITION_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositionDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'GA98_RESERVATION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('GA98_RESERVATION_DISPOSITION_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('GA97_RESERVATION_DISPOSITION_FAILED');
        }
        return $record;
    }
}
