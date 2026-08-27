<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CurianGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private string $directory;
    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        private ProceedingStore $proceedings,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->directory = $root.'/var/imperium/curia/cognition-authorities';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return 'curia' === $cluster && in_array($authorityType, ['audience-opening', 'deliberation-turn'], true);
    }

    public function resolve(string $cluster, string $type, string $id): array
    {
        if (!$this->supports($cluster, $type) || !preg_match('/^curian-cognition-[a-f0-9]{20}$/', $id)) {
            throw new \RuntimeException('GCA910_CURIAN_AUTHORITY_UNSUPPORTED');
        }
        $source = $this->validator->requireIntact(
            $this->validator->read($this->directory.'/'.$id.'.json', 'GCA911_CURIAN_AUTHORITY_ABSENT'),
            'GCA912_CURIAN_AUTHORITY_INVALID',
        );
        $seneschal = $source['seneschal'] ?? null;
        if ('imperium.curian-cognition-authority/v1' !== ($source['schema'] ?? null)
            || $type !== ($source['authority_type'] ?? null) || $id !== ($source['authority_id'] ?? null)
            || true !== ($source['single_use'] ?? null) || true !== ($source['cognition_authority_exercisable'] ?? null)
            || true !== ($source['sealed'] ?? null) || true === ($source['imperator_authorization'] ?? null)
            || true === ($source['resource_authority'] ?? null) || true === ($source['credential_authority'] ?? null)
            || true === ($source['tool_authority'] ?? null) || true === ($source['sortie_authority'] ?? null)
            || true === ($source['execution_authority'] ?? null) || !is_string($source['input_digest'] ?? null)
            || !is_array($seneschal) || 'curia.seneschal' !== ($seneschal['seat'] ?? null)
            || !is_string($seneschal['manifestation_id'] ?? null) || !is_int($seneschal['occupancy_generation'] ?? null)) {
            throw new \RuntimeException('GCA912_CURIAN_AUTHORITY_INVALID');
        }
        if ('deliberation-turn' === $type) {
            $proceeding = $this->proceedings->find((string) ($source['proceeding_id'] ?? ''));
            if (!is_array($proceeding) || !$this->intact($proceeding)
                || ($source['proceeding_digest'] ?? null) !== ($proceeding['record_digest'] ?? null)
                || CanonicalJson::encode($seneschal) !== CanonicalJson::encode($proceeding['seneschal']['occupant'] ?? null)
                || !is_string($source['response_id'] ?? null)) {
                throw new \RuntimeException('GCA913_CURIAN_LINEAGE_INVALID');
            }
        }

        return [
            'cluster' => 'curia', 'authority_type' => $type, 'authority_id' => $id,
            'instance_id' => $source['instance_id'], 'case_id' => $source['proceeding_id'],
            'case_digest' => $source['proceeding_digest'] ?? $source['record_digest'],
            'seat' => 'curia.seneschal',
            'purpose' => 'audience-opening' === $type ? 'assess-imperator-request' : 'advance-curian-planning',
            'input_digest' => $source['input_digest'],
            'source' => ['id' => $id, 'digest' => $source['record_digest']],
            'single_use' => true, 'exercisable' => true, 'consumed' => $this->consumed($source),
            'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function consumed(array $source): bool
    {
        $proceedingId = (string) ($source['proceeding_id'] ?? '');
        if ('audience-opening' === ($source['authority_type'] ?? null)) {
            $proceeding = $this->proceedings->find($proceedingId);
            return is_array($proceeding)
                && ($source['authority_id'] ?? null) === ($proceeding['source_cognition_authority']['id'] ?? null)
                && ($source['record_digest'] ?? null) === ($proceeding['source_cognition_authority']['digest'] ?? null);
        }
        $responseId = $source['response_id'] ?? null;
        $turn = is_string($responseId) ? $this->proceedings->findTurn($proceedingId, $responseId) : null;
        return is_array($turn)
            && ($source['authority_id'] ?? null) === ($turn['source_cognition_authority']['id'] ?? null)
            && ($source['record_digest'] ?? null) === ($turn['source_cognition_authority']['digest'] ?? null);
    }

    private function intact(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
}
