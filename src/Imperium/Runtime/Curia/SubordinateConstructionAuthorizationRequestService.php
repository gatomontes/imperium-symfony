<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinateConstructionAuthorizationRequestService
{
    private string $officeRoot;
    private string $requestDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->officeRoot = $projectDir.'/var/imperium/offices';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/subordinate-construction-requests';
    }

    /** @param list<string> $resolutionIds */
    public function request(array $resolutionIds): array
    {
        if ([] === $resolutionIds) throw new \InvalidArgumentException('C90_SUBORDINATE_RESOLUTION_SET_EMPTY');
        if (count($resolutionIds) !== count(array_unique($resolutionIds))) throw new \InvalidArgumentException('C91_SUBORDINATE_RESOLUTION_DUPLICATE');

        $references = [];
        $instanceId = null;
        foreach ($resolutionIds as $resolutionId) {
            if (!preg_match('/^(hagiography|studium)-subordinate-resolution-[a-f0-9]{20}$/', $resolutionId, $match)) throw new \InvalidArgumentException('C92_SUBORDINATE_RESOLUTION_ID_INVALID');
            $office = $match[1];
            $resolution = $this->read($this->officeRoot.'/'.$office.'/subordinate-resolutions/'.$resolutionId.'.json', 'C93_SUBORDINATE_RESOLUTION_ABSENT');
            $decision = $resolution['decision'] ?? null;
            if (!$this->digestMatches($resolution)
                || 'imperium.authorship-subordinate-resolution/v1' !== ($resolution['schema'] ?? null)
                || $resolutionId !== ($resolution['resolution_id'] ?? null) || $office !== ($resolution['office'] ?? null)
                || 'PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION' !== ($resolution['status'] ?? null)
                || true !== ($resolution['sealed'] ?? null) || true !== ($resolution['subordinate_staff_resolution_complete'] ?? null)
                || true !== ($resolution['construction_request_authority'] ?? null) || true === ($resolution['construction_authority'] ?? null)
                || true === ($resolution['persona_selection_authority'] ?? null) || true === ($resolution['profile_approval_authority'] ?? null)
                || true === ($resolution['spawning_authority'] ?? null) || true === ($resolution['seat_binding_authority'] ?? null)
                || true === ($resolution['execution_authority'] ?? null) || !is_array($decision)
                || 'SUBORDINATE_REQUIREMENTS_DETERMINED' !== ($decision['disposition'] ?? null)
                || !is_array($decision['required_specializations'] ?? null) || [] === $decision['required_specializations']) {
                throw new \RuntimeException('C94_SUBORDINATE_RESOLUTION_INVALID');
            }
            $currentInstance = $resolution['instance_id'] ?? null;
            if (!is_string($currentInstance) || (null !== $instanceId && $instanceId !== $currentInstance)) throw new \RuntimeException('C95_SUBORDINATE_RESOLUTION_INSTANCE_MISMATCH');
            $instanceId ??= $currentInstance;
            $references[] = [
                'resolution_id' => $resolutionId,
                'record_digest' => $resolution['record_digest'],
                'office' => $office,
                'acceptance_id' => $resolution['acceptance_id'],
                'commission_id' => $resolution['commission_id'],
                'subordinate_staff_class' => $resolution['subordinate_staff_class'],
                'required_specializations' => $decision['required_specializations'],
            ];
        }

        $requestId = 'subordinate-construction-request-'.substr(hash('sha256', CanonicalJson::encode([$instanceId, $references])), 0, 20);
        return $this->persist([
            'schema' => 'imperium.curia-subordinate-construction-authorization-request/v1',
            'request_id' => $requestId,
            'instance_id' => $instanceId,
            'requester' => ['office' => 'curia', 'seat' => 'curia.seneschal'],
            'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'resolutions' => $references,
            'question' => 'Authorize construction only for the exact independently sealed subordinate requirements in this request?',
            'requested_authority' => 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY',
            'status' => 'PENDING_IMPERATOR_DECISION',
            'approval_recorded' => false,
            'construction_authority' => false,
            'persona_selection_authority' => false,
            'profile_approval_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $request): array
    {
        if (!is_dir($this->requestDirectory) && !mkdir($this->requestDirectory, 0770, true) && !is_dir($this->requestDirectory)) throw new \RuntimeException('Curia subordinate-construction request directory cannot be created.');
        $request['record_digest'] = hash('sha256', CanonicalJson::encode($request)); $path = $this->requestDirectory.'/'.$request['request_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'C96_SUBORDINATE_REQUEST_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($request)) throw new \RuntimeException('C97_SUBORDINATE_REQUEST_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Subordinate construction request cannot be committed atomically.'); }
        return $request;
    }
}
