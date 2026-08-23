<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CommissionerCognitionResultReviewService
{
    private const DISPOSITIONS = ['ACCEPTED', 'REJECTED'];

    private string $deliveries;
    private string $occupancy;
    private string $reviews;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->deliveries = $root.'/var/imperium/operational/citadel-legate-cognition-result-deliveries';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->reviews = $root.'/var/imperium/operational/citadel-legate-cognition-result-reviews';
    }

    public function review(string $deliveryId, string $authorityId, string $commissionerBindingId, string $disposition, string $rationale, \DateTimeImmutable $reviewedAt): array
    {
        if (!preg_match('/^citadel-legate-cognition-result-delivery-[a-f0-9]{20}$/', $deliveryId)) {
            throw new \InvalidArgumentException('CIT430_COGNITION_RESULT_DELIVERY_ID_INVALID');
        }
        if (!preg_match('/^citadel-legate-cognition-result-review-authority-[a-f0-9]{20}$/', $authorityId)) {
            throw new \InvalidArgumentException('CIT431_COGNITION_RESULT_REVIEW_AUTHORITY_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $commissionerBindingId)) {
            throw new \InvalidArgumentException('CIT432_COMMISSIONER_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('CIT433_COGNITION_RESULT_REVIEW_DISPOSITION_INVALID');
        }

        $delivery = $this->read($this->deliveries.'/'.$deliveryId.'.json', 'CIT434_COGNITION_RESULT_DELIVERY_ABSENT');
        foreach (glob($this->reviews.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CIT439_COGNITION_RESULT_REVIEW_CONFLICT');
            if (($prior['source_delivery']['id'] ?? null) === $deliveryId) {
                if (($prior['source_delivery']['digest'] ?? null) !== ($delivery['record_digest'] ?? null)
                    || ($prior['reviewer']['binding_id'] ?? null) !== $commissionerBindingId
                    || ($prior['disposition'] ?? null) !== $disposition
                    || ($prior['rationale'] ?? null) !== $rationale) {
                    throw new \RuntimeException('CIT439_COGNITION_RESULT_REVIEW_CONFLICT');
                }

                return $prior;
            }
        }

        $commissioner = $this->read($this->occupancy.'/'.$commissionerBindingId.'.json', 'CIT435_COMMISSIONER_OCCUPANCY_ABSENT');
        $this->validate($deliveryId, $delivery, $authorityId, $commissionerBindingId, $commissioner);
        $this->assertSoleCurrentCommissionerOccupancy($commissioner);

        $reviewer = [
            'seat' => $commissioner['seat'],
            'binding_id' => $commissionerBindingId,
            'binding_digest' => $commissioner['record_digest'],
            'manifestation_id' => $commissioner['manifestation_id'],
            'occupancy_generation' => $commissioner['occupancy_generation'],
        ];
        $reviewId = 'citadel-legate-cognition-result-review-'.substr(hash('sha256', CanonicalJson::encode([$deliveryId, $delivery['record_digest'], $reviewer, $disposition, $rationale])), 0, 20);

        return $this->save($reviewId, [
            'schema' => 'imperium.citadel-legate-cognition-result-review/v1',
            'review_id' => $reviewId,
            'instance_id' => $delivery['instance_id'],
            'case_id' => $delivery['case_id'],
            'case_digest' => $delivery['case_digest'],
            'source_delivery' => ['id' => $deliveryId, 'digest' => $delivery['record_digest']],
            'source_cognition_turn' => $delivery['source_cognition_turn'],
            'source_commission' => $delivery['source_commission'],
            'reviewer' => $reviewer,
            'legate' => $delivery['legate'],
            'contract' => $delivery['contract'],
            'result' => $delivery['result'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'reviewed_at' => $reviewedAt->format(DATE_ATOM),
            'status' => 'ACCEPTED' === $disposition
                ? 'CITADEL_LEGATE_COGNITION_RESULT_ACCEPTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY'
                : 'CITADEL_LEGATE_COGNITION_RESULT_REJECTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY',
            'review_disposition_authority' => [
                'id' => $authorityId,
                'consumed' => true,
                'continuing_authority' => false,
            ],
            'result_reviewed' => true,
            'result_accepted' => 'ACCEPTED' === $disposition,
            'result_rejected' => 'REJECTED' === $disposition,
            'result_operationally_adopted' => false,
            'commission_closed' => true,
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

    private function validate(string $deliveryId, array $delivery, string $authorityId, string $commissionerBindingId, array $commissioner): void
    {
        $authority = $delivery['review_disposition_authority'] ?? [];
        $recipient = $delivery['recipient'] ?? [];
        if (!$this->valid($delivery) || 'imperium.citadel-legate-cognition-result-delivery/v1' !== ($delivery['schema'] ?? null)
            || $deliveryId !== ($delivery['delivery_id'] ?? null)
            || 'CITADEL_LEGATE_COGNITION_RESULT_DELIVERED_PENDING_COMMISSIONER_REVIEW' !== ($delivery['status'] ?? null)
            || true !== ($delivery['result_delivered'] ?? null) || false !== ($delivery['result_reviewed'] ?? null)
            || false !== ($delivery['result_accepted'] ?? null) || false !== ($delivery['result_rejected'] ?? null)
            || $authorityId !== ($authority['authority_id'] ?? null) || true !== ($authority['authority_single_use'] ?? null)
            || false !== ($authority['consumed'] ?? null) || 'RECORD_ONE_EXACT_COGNITION_RESULT_REVIEW_DISPOSITION' !== ($authority['purpose'] ?? null)
            || $commissionerBindingId !== ($recipient['binding_id'] ?? null) || ($authority['destination'] ?? null) !== ($recipient['seat'] ?? null)
            || true === ($delivery['follow_up_commission_authority'] ?? null) || true === ($delivery['commission_exercisable'] ?? null)
            || true === ($delivery['governed_cognition_authority'] ?? null) || true === ($delivery['provider_invocation_authority'] ?? null)
            || true === ($delivery['credential_use_authority'] ?? null) || true === ($delivery['operational_use_permitted'] ?? null)
            || true === ($delivery['tool_use_authority'] ?? null) || true === ($delivery['external_action_authority'] ?? null)
            || true === ($delivery['execution_authority'] ?? null) || true === ($delivery['continuing_turn_authority'] ?? null) || true !== ($delivery['sealed'] ?? null)
            || !$this->valid($commissioner) || $commissionerBindingId !== ($commissioner['binding_id'] ?? null)
            || ($delivery['instance_id'] ?? null) !== ($commissioner['instance_id'] ?? null)
            || ($recipient['seat'] ?? null) !== ($commissioner['seat'] ?? null)
            || ($recipient['binding_digest'] ?? null) !== ($commissioner['record_digest'] ?? null)
            || ($recipient['manifestation_id'] ?? null) !== ($commissioner['manifestation_id'] ?? null)
            || ($recipient['occupancy_generation'] ?? null) !== ($commissioner['occupancy_generation'] ?? null)
            || 'ACTIVE' !== ($commissioner['status'] ?? null) || true !== ($commissioner['binding_atomic'] ?? null) || true !== ($commissioner['sealed'] ?? null)) {
            throw new \RuntimeException('CIT436_COGNITION_RESULT_REVIEW_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentCommissionerOccupancy(array $commissioner): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CIT440_COMMISSIONER_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) === ($commissioner['seat'] ?? null)
                && ($other['binding_id'] ?? null) !== ($commissioner['binding_id'] ?? null)
                && 'ACTIVE' === ($other['status'] ?? null)) {
                throw new \RuntimeException('CIT440_COMMISSIONER_OCCUPANCY_CONFLICT');
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
        if (!is_dir($this->reviews) && !mkdir($this->reviews, 0770, true) && !is_dir($this->reviews)) {
            throw new \RuntimeException('CIT437_COGNITION_RESULT_REVIEW_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->reviews.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CIT439_COGNITION_RESULT_REVIEW_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CIT439_COGNITION_RESULT_REVIEW_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CIT437_COGNITION_RESULT_REVIEW_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
