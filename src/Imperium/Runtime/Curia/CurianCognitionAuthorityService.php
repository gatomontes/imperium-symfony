<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CurianCognitionAuthorityService
{
    private string $directory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->directory = $root.'/var/imperium/curia/cognition-authorities';
    }

    public function openAudience(string $request, array $context, array $seneschal): array
    {
        return $this->persist('audience-opening', $context['proceeding_id'] ?? '', $context['instance_id'] ?? '', $seneschal, [
            'request' => $request,
            'context' => $context,
        ], null, null);
    }

    public function openDeliberation(array $proceeding, array $priorTurns, string $response, array $context, array $seneschal): array
    {
        return $this->persist('deliberation-turn', $context['proceeding_id'] ?? '', $context['instance_id'] ?? '', $seneschal, [
            'proceeding' => $proceeding,
            'prior_turns' => $priorTurns,
            'imperator_response' => $response,
            'context' => $context,
        ], $proceeding['record_digest'] ?? null, $context['response_id'] ?? null);
    }

    private function persist(string $type, mixed $proceedingId, mixed $instanceId, array $seneschal, array $inputs, mixed $proceedingDigest, mixed $responseId): array
    {
        if (!is_string($proceedingId) || '' === $proceedingId || !is_string($instanceId) || '' === $instanceId
            || 'curia.seneschal' !== ($seneschal['seat'] ?? null)
            || !is_string($seneschal['manifestation_id'] ?? null)
            || !is_int($seneschal['occupancy_generation'] ?? null)) {
            throw new \RuntimeException('GCA900_CURIAN_AUTHORITY_INPUT_INVALID');
        }
        $authority = [
            'schema' => 'imperium.curian-cognition-authority/v1',
            'authority_type' => $type,
            'authority_id' => 'curian-cognition-'.substr(hash('sha256', CanonicalJson::encode([$type, $proceedingId, $instanceId, $seneschal, $inputs])), 0, 20),
            'instance_id' => $instanceId,
            'proceeding_id' => $proceedingId,
            'proceeding_digest' => $proceedingDigest,
            'response_id' => $responseId,
            'seneschal' => $seneschal,
            'input_digest' => hash('sha256', CanonicalJson::encode(array_values($inputs))),
            'single_use' => true,
            'cognition_authority_exercisable' => true,
            'imperator_authorization' => false,
            'resource_authority' => false,
            'credential_authority' => false,
            'tool_authority' => false,
            'sortie_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ];
        $authority['record_digest'] = hash('sha256', CanonicalJson::encode($authority));
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('GCA901_CURIAN_AUTHORITY_PERSISTENCE_FAILED');
        }
        $path = $this->directory.'/'.$authority['authority_id'].'.json';
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($authority)) {
                throw new \RuntimeException('GCA902_CURIAN_AUTHORITY_REPLAY_CONFLICT');
            }
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($authority, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)
            || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('GCA901_CURIAN_AUTHORITY_PERSISTENCE_FAILED');
        }
        return $authority;
    }
}
