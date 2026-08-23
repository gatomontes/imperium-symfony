<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionPresentationService
{
    private const DESTINATION = 'curia.seneschal';

    private string $reviews;
    private string $occupancy;
    private string $presentations;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->reviews = $root.'/var/imperium/operational/citadel-legate-cognition-result-reviews';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->presentations = $root.'/var/imperium/operational/legate-result-adoption-presentations';
    }

    public function present(string $reviewId, string $commissionerBindingId, string $presentationRationale, \DateTimeImmutable $presentedAt): array
    {
        if (!preg_match('/^citadel-legate-cognition-result-review-[a-f0-9]{20}$/', $reviewId)) {
            throw new \InvalidArgumentException('CUR440_COGNITION_RESULT_REVIEW_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $commissionerBindingId)) {
            throw new \InvalidArgumentException('CUR441_COMMISSIONER_BINDING_ID_INVALID');
        }
        $presentationRationale = trim($presentationRationale);
        if ('' === $presentationRationale) {
            throw new \InvalidArgumentException('CUR442_ADOPTION_PRESENTATION_RATIONALE_REQUIRED');
        }

        $review = $this->read($this->reviews.'/'.$reviewId.'.json', 'CUR443_ACCEPTED_COGNITION_RESULT_REVIEW_ABSENT');
        foreach (glob($this->presentations.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR448_ADOPTION_PRESENTATION_CONFLICT');
            if (($prior['source_review']['id'] ?? null) === $reviewId) {
                if (($prior['source_review']['digest'] ?? null) !== ($review['record_digest'] ?? null)
                    || ($prior['presenter']['binding_id'] ?? null) !== $commissionerBindingId
                    || ($prior['presentation_rationale'] ?? null) !== $presentationRationale) {
                    throw new \RuntimeException('CUR448_ADOPTION_PRESENTATION_CONFLICT');
                }

                return $prior;
            }
        }

        $commissioner = $this->read($this->occupancy.'/'.$commissionerBindingId.'.json', 'CUR444_COMMISSIONER_OCCUPANCY_ABSENT');
        $this->validate($reviewId, $review, $commissionerBindingId, $commissioner);
        $this->assertSoleCurrentCommissionerOccupancy($commissioner);

        $presenter = [
            'seat' => $commissioner['seat'],
            'binding_id' => $commissionerBindingId,
            'binding_digest' => $commissioner['record_digest'],
            'manifestation_id' => $commissioner['manifestation_id'],
            'occupancy_generation' => $commissioner['occupancy_generation'],
        ];
        $presentationId = 'legate-result-adoption-presentation-'.substr(hash('sha256', CanonicalJson::encode([$reviewId, $review['record_digest'], $presenter, $presentationRationale])), 0, 20);

        return $this->save($presentationId, [
            'schema' => 'imperium.legate-result-adoption-presentation/v1',
            'presentation_id' => $presentationId,
            'instance_id' => $review['instance_id'],
            'case_id' => $review['case_id'],
            'case_digest' => $review['case_digest'],
            'source_review' => ['id' => $reviewId, 'digest' => $review['record_digest']],
            'source_delivery' => $review['source_delivery'],
            'source_cognition_turn' => $review['source_cognition_turn'],
            'source_commission' => $review['source_commission'],
            'presenter' => $presenter,
            'recipient' => ['office' => 'curia', 'seat' => self::DESTINATION, 'intake_pending' => true],
            'legate' => $review['legate'],
            'contract' => $review['contract'],
            'result' => $review['result'],
            'commissioner_review_rationale' => $review['rationale'],
            'presentation_rationale' => $presentationRationale,
            'presented_at' => $presentedAt->format(DATE_ATOM),
            'status' => 'LEGATE_RESULT_ADOPTION_REQUEST_PRESENTED_PENDING_GOVERNING_INTAKE',
            'commission_closed' => true,
            'governing_intake_decided' => false,
            'result_evaluated_for_adoption' => false,
            'result_operationally_adopted' => false,
            'planning_amendment_authority' => false,
            'follow_up_commission_authority' => false,
            'commission_exercisable' => false,
            'governed_cognition_authority' => false,
            'provider_invocation_authority' => false,
            'credential_use_authority' => false,
            'operational_use_permitted' => false,
            'tool_use_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $reviewId, array $review, string $commissionerBindingId, array $commissioner): void
    {
        $reviewer = $review['reviewer'] ?? [];
        if (!$this->valid($review) || 'imperium.citadel-legate-cognition-result-review/v1' !== ($review['schema'] ?? null)
            || $reviewId !== ($review['review_id'] ?? null)
            || 'CITADEL_LEGATE_COGNITION_RESULT_ACCEPTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY' !== ($review['status'] ?? null)
            || 'ACCEPTED' !== ($review['disposition'] ?? null) || true !== ($review['result_reviewed'] ?? null)
            || true !== ($review['result_accepted'] ?? null) || false !== ($review['result_rejected'] ?? null)
            || true !== ($review['commission_closed'] ?? null) || true !== ($review['review_disposition_authority']['consumed'] ?? null)
            || true === ($review['result_operationally_adopted'] ?? null) || true === ($review['follow_up_commission_authority'] ?? null)
            || true === ($review['commission_exercisable'] ?? null) || true === ($review['governed_cognition_authority'] ?? null)
            || true === ($review['provider_invocation_authority'] ?? null) || true === ($review['credential_use_authority'] ?? null)
            || true === ($review['operational_use_permitted'] ?? null) || true === ($review['tool_use_authority'] ?? null)
            || true === ($review['external_action_authority'] ?? null) || true === ($review['execution_authority'] ?? null)
            || true === ($review['continuing_turn_authority'] ?? null) || true !== ($review['sealed'] ?? null)
            || $commissionerBindingId !== ($reviewer['binding_id'] ?? null)
            || !$this->valid($commissioner) || $commissionerBindingId !== ($commissioner['binding_id'] ?? null)
            || ($review['instance_id'] ?? null) !== ($commissioner['instance_id'] ?? null)
            || ($reviewer['seat'] ?? null) !== ($commissioner['seat'] ?? null)
            || ($reviewer['binding_digest'] ?? null) !== ($commissioner['record_digest'] ?? null)
            || ($reviewer['manifestation_id'] ?? null) !== ($commissioner['manifestation_id'] ?? null)
            || ($reviewer['occupancy_generation'] ?? null) !== ($commissioner['occupancy_generation'] ?? null)
            || 'ACTIVE' !== ($commissioner['status'] ?? null) || true !== ($commissioner['binding_atomic'] ?? null)
            || true !== ($commissioner['sealed'] ?? null)) {
            throw new \RuntimeException('CUR445_ADOPTION_PRESENTATION_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentCommissionerOccupancy(array $commissioner): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CUR449_COMMISSIONER_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) === ($commissioner['seat'] ?? null)
                && ($other['binding_id'] ?? null) !== ($commissioner['binding_id'] ?? null)
                && 'ACTIVE' === ($other['status'] ?? null)) {
                throw new \RuntimeException('CUR449_COMMISSIONER_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->presentations) && !mkdir($this->presentations, 0770, true) && !is_dir($this->presentations)) {
            throw new \RuntimeException('CUR446_ADOPTION_PRESENTATION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->presentations.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CUR448_ADOPTION_PRESENTATION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CUR448_ADOPTION_PRESENTATION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CUR446_ADOPTION_PRESENTATION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
