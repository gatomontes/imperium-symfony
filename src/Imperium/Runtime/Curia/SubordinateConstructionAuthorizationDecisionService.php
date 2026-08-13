<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinateConstructionAuthorizationDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private string $requestDirectory;
    private string $officeRoot;
    private string $decisionDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->requestDirectory = $projectDir.'/var/imperium/curia/subordinate-construction-requests';
        $this->officeRoot = $projectDir.'/var/imperium/offices';
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/subordinate-construction-decisions';
    }

    public function authorize(string $requestId, ?string $actId = null): array
    {
        if (!preg_match('/^subordinate-construction-request-[a-f0-9]{20}$/', $requestId)) throw new \InvalidArgumentException('C98_SUBORDINATE_REQUEST_ID_INVALID');
        $request = $this->read($this->requestDirectory.'/'.$requestId.'.json', 'C99_SUBORDINATE_REQUEST_ABSENT');
        $references = $request['resolutions'] ?? null;
        if (!$this->digestMatches($request) || $requestId !== ($request['request_id'] ?? null)
            || 'imperium.curia-subordinate-construction-authorization-request/v1' !== ($request['schema'] ?? null)
            || 'PENDING_IMPERATOR_DECISION' !== ($request['status'] ?? null)
            || 'imperator' !== ($request['recipient']['kind'] ?? null) || self::IMPERATOR_ID !== ($request['recipient']['id'] ?? null)
            || 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY' !== ($request['requested_authority'] ?? null)
            || !is_array($references) || [] === $references || true === ($request['approval_recorded'] ?? null)
            || true === ($request['construction_authority'] ?? null) || true === ($request['persona_selection_authority'] ?? null)
            || true === ($request['profile_approval_authority'] ?? null) || true === ($request['spawning_authority'] ?? null)
            || true === ($request['seat_binding_authority'] ?? null) || true === ($request['execution_authority'] ?? null)) {
            throw new \RuntimeException('C100_SUBORDINATE_REQUEST_INVALID');
        }

        $seen = [];
        foreach ($references as $reference) {
            $resolutionId = is_array($reference) ? ($reference['resolution_id'] ?? null) : null;
            $office = is_array($reference) ? ($reference['office'] ?? null) : null;
            if (!is_string($resolutionId) || !in_array($office, ['hagiography', 'studium'], true) || isset($seen[$resolutionId])) throw new \RuntimeException('C101_SUBORDINATE_RESOLUTION_SET_INVALID');
            $resolution = $this->read($this->officeRoot.'/'.$office.'/subordinate-resolutions/'.$resolutionId.'.json', 'C101_SUBORDINATE_RESOLUTION_SET_INVALID');
            if (!$this->digestMatches($resolution) || 'imperium.authorship-subordinate-resolution/v1' !== ($resolution['schema'] ?? null)
                || ($reference['record_digest'] ?? null) !== ($resolution['record_digest'] ?? null)
                || ($request['instance_id'] ?? null) !== ($resolution['instance_id'] ?? null) || $office !== ($resolution['office'] ?? null)
                || ($reference['acceptance_id'] ?? null) !== ($resolution['acceptance_id'] ?? null)
                || ($reference['commission_id'] ?? null) !== ($resolution['commission_id'] ?? null)
                || ($reference['subordinate_staff_class'] ?? null) !== ($resolution['subordinate_staff_class'] ?? null)
                || CanonicalJson::encode($reference['required_specializations'] ?? null) !== CanonicalJson::encode($resolution['decision']['required_specializations'] ?? null)
                || 'PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION' !== ($resolution['status'] ?? null)
                || 'SUBORDINATE_REQUIREMENTS_DETERMINED' !== ($resolution['decision']['disposition'] ?? null)
                || true !== ($resolution['sealed'] ?? null) || true !== ($resolution['subordinate_staff_resolution_complete'] ?? null)
                || true !== ($resolution['construction_request_authority'] ?? null)
                || true === ($resolution['construction_authority'] ?? null) || true === ($resolution['persona_selection_authority'] ?? null)
                || true === ($resolution['profile_approval_authority'] ?? null) || true === ($resolution['spawning_authority'] ?? null)
                || true === ($resolution['seat_binding_authority'] ?? null) || true === ($resolution['execution_authority'] ?? null)) {
                throw new \RuntimeException('C101_SUBORDINATE_RESOLUTION_SET_INVALID');
            }
            $seen[$resolutionId] = true;
        }

        $actId ??= 'subordinate-construction-authorization-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $request['record_digest'], $references, self::IMPERATOR_ID])), 0, 20);
        if (!preg_match('/^subordinate-construction-authorization-[a-zA-Z0-9._-]+$/', $actId)) throw new \InvalidArgumentException('C102_SUBORDINATE_ACT_ID_INVALID');
        return $this->persist($actId, [
            'schema' => 'imperium.imperator-subordinate-construction-authorization/v1',
            'kind' => 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_AUTHORIZATION',
            'act_id' => $actId,
            'instance_id' => $request['instance_id'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_request_id' => $requestId,
            'source_request_digest' => $request['record_digest'],
            'resolutions' => $references,
            'disposition' => 'AUTHORIZED_FOR_EXACT_RESOLUTIONS',
            'authorized_authority' => 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY',
            'construction_authority' => true,
            'persona_selection_authority' => false,
            'profile_approval_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(string $actId, array $act): array
    {
        if (!is_dir($this->decisionDirectory) && !mkdir($this->decisionDirectory, 0770, true) && !is_dir($this->decisionDirectory)) throw new \RuntimeException('Curia subordinate-construction decision directory cannot be created.');
        $act['record_digest'] = hash('sha256', CanonicalJson::encode($act)); $path = $this->decisionDirectory.'/'.$actId.'.json';
        if (is_file($path)) { $existing = $this->read($path, 'C103_SUBORDINATE_ACT_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($act)) throw new \RuntimeException('C104_SUBORDINATE_ACT_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($act, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Subordinate construction authorization cannot be committed atomically.'); }
        return $act;
    }
}
