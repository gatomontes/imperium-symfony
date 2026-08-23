<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileDerivationAuthorizationDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];
    private string $requestDirectory;
    private string $decisionDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->requestDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-requests';
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-decisions';
    }

    public function decide(string $requestId, string $disposition, string $response, ?string $limitations = null): array
    {
        if (!preg_match('/^profile-derivation-authorization-request-[a-f0-9]{20}$/', $requestId)) throw new \InvalidArgumentException('C144_PROFILE_DERIVATION_REQUEST_ID_INVALID');
        $disposition = strtoupper(trim($disposition)); $response = trim($response); $limitations = null === $limitations ? null : trim($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $response || '' === $limitations) throw new \InvalidArgumentException('C145_PROFILE_DERIVATION_DISPOSITION_INVALID');
        $request = $this->read($this->requestDirectory.'/'.$requestId.'.json', 'C146_PROFILE_DERIVATION_REQUEST_ABSENT');
        if (!$this->digestMatches($request)
            || 'imperium.curia-profile-derivation-authorization-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || 'PENDING_IMPERATOR_PROFILE_DERIVATION_DECISION' !== ($request['status'] ?? null)
            || self::IMPERATOR_ID !== ($request['recipient']['id'] ?? null)
            || 'PROFILE_DERIVATION_ONLY' !== ($request['requested_authority'] ?? null)
            || self::DISPOSITIONS !== ($request['allowed_dispositions'] ?? null)
            || !is_array($request['source_reservation_disposition'] ?? null)
            || !is_array($request['source_plan'] ?? null)
            || !is_array($request['profile_scope'] ?? null)
            || 'curia' !== ($request['profile_scope']['profile_steward'] ?? null)
            || 'conscription.recruiter' !== ($request['profile_scope']['prospective_commissioner_and_installer'] ?? null)
            || 'laboratorium.alchemist' !== ($request['profile_scope']['prospective_transformer'] ?? null)
            || 'senate' !== ($request['profile_scope']['prospective_examiner'] ?? null)
            || 'imperator' !== ($request['profile_scope']['prospective_approver'] ?? null)
            || true === ($request['retrieval_authority'] ?? null)
            || true === ($request['profile_derivation_authority'] ?? null)
            || true === ($request['conscription_acceptance_authority'] ?? null)
            || true === ($request['spawning_authority'] ?? null)
            || true === ($request['seat_binding_authority'] ?? null)
            || true === ($request['deployment_authority'] ?? null)
            || true === ($request['execution_authority'] ?? null)
            || true !== ($request['sealed'] ?? null)
        ) throw new \RuntimeException('C147_PROFILE_DERIVATION_REQUEST_INVALID');

        foreach (glob($this->decisionDirectory.'/profile-derivation-decision-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'C150_PROFILE_DERIVATION_DECISION_CONFLICT');
            if (($prior['source_request_id'] ?? null) === $requestId) {
                if (($prior['disposition'] ?? null) === $disposition && ($prior['response'] ?? null) === $response && ($prior['limitations'] ?? null) === $limitations) return $prior;
                throw new \RuntimeException('C150_PROFILE_DERIVATION_DECISION_CONFLICT');
            }
        }
        $authorized = 'AUTHORIZED' === $disposition;
        $actId = 'profile-derivation-decision-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $request['record_digest'], $disposition, $response, $limitations, self::IMPERATOR_ID])), 0, 20);
        return $this->persist($actId, [
            'schema' => 'imperium.imperator-profile-derivation-decision/v1',
            'kind' => 'PROFILE_DERIVATION_DECISION',
            'act_id' => $actId,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['proceeding_id'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_request_id' => $requestId,
            'source_request_digest' => $request['record_digest'],
            'source_reservation_disposition' => $request['source_reservation_disposition'],
            'source_plan' => $request['source_plan'],
            'profile_scope' => $request['profile_scope'],
            'disposition' => $disposition,
            'response' => $response,
            'limitations' => $limitations,
            'status' => $authorized ? 'PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE' : 'NON_AUTHORIZING_IMPERATOR_PROFILE_DERIVATION_DISPOSITION_RECORDED',
            'profile_derivation_authority' => $authorized,
            'profile_derivation_authority_exercisable' => $authorized,
            'conscription_followup_required' => $authorized,
            'curia_revision_required' => in_array($disposition, ['RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED'], true),
            'retrieval_authority' => false,
            'conscription_acceptance_authority' => false,
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
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->decisionDirectory) && !mkdir($this->decisionDirectory, 0770, true) && !is_dir($this->decisionDirectory)) throw new \RuntimeException('C148_PROFILE_DERIVATION_DECISION_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->decisionDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'C150_PROFILE_DERIVATION_DECISION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('C150_PROFILE_DERIVATION_DECISION_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary); throw new \RuntimeException('C148_PROFILE_DERIVATION_DECISION_FAILED');
        }
        return $record;
    }
}
