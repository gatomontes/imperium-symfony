<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NormalizedToolResultAdmissionService
{
    public const string ADMISSIONS = 'var/imperium/lazaretto/normalized-tool-result-admissions';
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function admit(array $result, \DateTimeImmutable $at): array
    {
        $digest = $result['record_digest'] ?? null;
        $unsealed = $result;
        unset($unsealed['record_digest']);
        if (NormalizedToolResultContract::REQUIRED_FIELDS !== array_keys($result) || !is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($unsealed))) || !in_array($result['effect_outcome']['status'] ?? null, NormalizedToolResultContract::EFFECT_OUTCOMES, true)
            || 'NORMALIZED_PENDING_LAZARETTO_ADMISSION' !== ($result['recovery']['checkpoint'] ?? null) || true === ($result['recovery']['automatic_replay_permitted'] ?? null)) throw new \RuntimeException('GTP620_NORMALIZED_RESULT_NOT_ADMISSIBLE');
        $id = 'normalized-tool-result-admission-'.substr(hash('sha256', $result['result_id'].'|'.$result['record_digest']), 0, 20);
        return $this->records->put(self::ADMISSIONS, $id, ['schema' => 'imperium.lazaretto.normalized-tool-result-admission/v1', 'admission_id' => $id, 'normalized_tool_result' => ['id' => $result['result_id'], 'digest' => $result['record_digest'], 'schema' => $result['schema']], 'status' => 'ADMITTED_NORMALIZED', 'raw_provider_content_interpreted' => false, 'provider_reinvoked' => false, 'admitted_at' => $at->format(DATE_ATOM), 'sealed' => true]);
    }
}
