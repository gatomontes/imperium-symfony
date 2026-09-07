<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** Receipt is intentionally possible without an officer, principal or provider grant. */
final readonly class CitadelIntakeService
{
    public function __construct(private FormationJournal $journal) {}

    public function receive(string $request, string $submissionId): array
    {
        if ('' === trim($request) || strlen($request) > 1048576
            || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{7,79}$/D', $submissionId)) {
            throw new \InvalidArgumentException('CMF010_INTAKE_INPUT_INVALID');
        }
        return $this->journal->change(function (array &$state) use ($request, $submissionId): array {
            $fingerprint = FormationJournal::digest([$submissionId, $request]);
            if (isset($state['submissions'][$submissionId])) {
                $prior = $state['intakes'][$state['submissions'][$submissionId]];
                if ($prior['submission_fingerprint'] !== $fingerprint) {
                    throw new \RuntimeException('CMF011_SUBMISSION_REPLAY_CONFLICT');
                }
                return $prior;
            }
            // A storage identity is neither a new Imperium nor a constituted principal.
            $state['citadel_id'] ??= 'citadel-'.bin2hex(random_bytes(16));
            $id = 'intake-'.bin2hex(random_bytes(16));
            $record = [
                'schema' => 'imperium.citadel-intake/v1',
                'citadel_id' => $state['citadel_id'], 'intake_id' => $id,
                'submission_id' => $submissionId, 'submission_fingerprint' => $fingerprint,
                'exchange' => [['sequence' => 1, 'kind' => 'submitted-request', 'content' => $request,
                    'actor_authentication' => 'UNVERIFIED_SUBMITTER', 'approval_authority' => false]],
                'intent_version' => 1, 'status' => 'PENDING_AUTHENTICATED_INTERVIEW',
                'curia_id' => null, 'mission_id' => null, 'execution_authority' => false,
            ];
            $record['record_digest'] = FormationJournal::digest($record);
            $state['intakes'][$id] = $record;
            $state['submissions'][$submissionId] = $id;
            $state['registry_generation'] = ($state['registry_generation'] ?? 0) + 1;
            return $record;
        });
    }

    public function find(string $id): array
    {
        return $this->journal->read()['state']['intakes'][$id]
            ?? throw new \RuntimeException('CMF012_INTAKE_ABSENT');
    }
}
