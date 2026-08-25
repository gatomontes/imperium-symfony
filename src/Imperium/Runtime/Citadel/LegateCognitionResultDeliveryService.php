<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LegateCognitionResultDeliveryService
{
    private string $turns;
    private string $commissions;
    private string $dispositions;
    private string $decisions;
    private string $providerActivations;
    private string $occupancy;
    private string $deliveries;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->turns = $root.'/var/imperium/operational/citadel-legate-bounded-cognition-turns';
        $this->commissions = $root.'/var/imperium/operational/citadel-legate-governed-commissions';
        $this->dispositions = $root.'/var/imperium/operational/citadel-legate-governed-commission-dispositions';
        $this->decisions = $root.'/var/imperium/operational/citadel-legate-cognition-turn-authorization-decisions';
        $this->providerActivations = $root.'/var/imperium/offices/clavium/citadel-legate-provider-invocation-activations';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->deliveries = $root.'/var/imperium/operational/citadel-legate-cognition-result-deliveries';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function deliver(string $turnId, string $commissionerBindingId, \DateTimeImmutable $deliveredAt): array
    {
        if (!preg_match('/^citadel-legate-bounded-cognition-turn-[a-f0-9]{20}$/', $turnId)) {
            throw new \InvalidArgumentException('CIT420_COGNITION_TURN_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $commissionerBindingId)) {
            throw new \InvalidArgumentException('CIT421_COMMISSIONER_BINDING_ID_INVALID');
        }

        $turn = $this->read($this->turns.'/'.$turnId.'.json', 'CIT422_COGNITION_TURN_ABSENT');
        foreach (glob($this->deliveries.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CIT428_COGNITION_RESULT_DELIVERY_CONFLICT');
            if (($prior['source_cognition_turn']['id'] ?? null) === $turnId) {
                if (($prior['source_cognition_turn']['digest'] ?? null) !== ($turn['record_digest'] ?? null)
                    || ($prior['recipient']['binding_id'] ?? null) !== $commissionerBindingId) {
                    throw new \RuntimeException('CIT428_COGNITION_RESULT_DELIVERY_CONFLICT');
                }

                return $prior;
            }
        }

        $commission = $this->source($this->commissions, $turn['source_commission'] ?? [], 'CIT423_COGNITION_RESULT_CHAIN_ABSENT');
        $disposition = $this->source($this->dispositions, $turn['source_commission_disposition'] ?? [], 'CIT423_COGNITION_RESULT_CHAIN_ABSENT');
        $decision = $this->source($this->decisions, $turn['source_cognition_turn_authorization'] ?? [], 'CIT423_COGNITION_RESULT_CHAIN_ABSENT');
        $providerActivation = $this->source($this->providerActivations, $turn['source_provider_activation'] ?? [], 'CIT423_COGNITION_RESULT_CHAIN_ABSENT');
        $commissioner = $this->read($this->occupancy.'/'.$commissionerBindingId.'.json', 'CIT424_COMMISSIONER_OCCUPANCY_ABSENT');
        $this->validate($turnId, $turn, $commission, $disposition, $decision, $providerActivation, $commissionerBindingId, $commissioner);
        $this->assertSoleCurrentCommissionerOccupancy($commissioner);

        $recipient = [
            'seat' => $commissioner['seat'],
            'binding_id' => $commissionerBindingId,
            'binding_digest' => $commissioner['record_digest'],
            'manifestation_id' => $commissioner['manifestation_id'],
            'occupancy_generation' => $commissioner['occupancy_generation'],
        ];
        $deliveryId = 'citadel-legate-cognition-result-delivery-'.substr(hash('sha256', CanonicalJson::encode([$turnId, $turn['record_digest'], $recipient])), 0, 20);
        $reviewAuthorityId = 'citadel-legate-cognition-result-review-authority-'.substr(hash('sha256', CanonicalJson::encode([$deliveryId, $recipient])), 0, 20);

        return $this->save($deliveryId, [
            'schema' => 'imperium.citadel-legate-cognition-result-delivery/v1',
            'delivery_id' => $deliveryId,
            'instance_id' => $turn['instance_id'],
            'case_id' => $turn['case_id'],
            'case_digest' => $turn['case_digest'],
            'source_cognition_turn' => ['id' => $turnId, 'digest' => $turn['record_digest']],
            'source_commission' => $turn['source_commission'],
            'source_commission_disposition' => $turn['source_commission_disposition'],
            'source_cognition_turn_authorization' => $turn['source_cognition_turn_authorization'],
            'source_provider_activation' => $turn['source_provider_activation'],
            'legate' => $turn['target'],
            'recipient' => $recipient,
            'contract' => $turn['contract'],
            'result' => $turn['output'],
            'delivered_at' => $deliveredAt->format(DATE_ATOM),
            'status' => 'CITADEL_LEGATE_COGNITION_RESULT_DELIVERED_PENDING_COMMISSIONER_REVIEW',
            'result_delivered' => true,
            'result_reviewed' => false,
            'result_accepted' => false,
            'result_rejected' => false,
            'review_disposition_authority' => [
                'authority_id' => $reviewAuthorityId,
                'authority_single_use' => true,
                'destination' => $commissioner['seat'],
                'purpose' => 'RECORD_ONE_EXACT_COGNITION_RESULT_REVIEW_DISPOSITION',
                'consumed' => false,
            ],
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

    private function validate(string $turnId, array $turn, array $commission, array $disposition, array $decision, array $providerActivation, string $commissionerBindingId, array $commissioner): void
    {
        if (!$this->valid($turn) || 'imperium.citadel-legate-bounded-cognition-turn/v1' !== ($turn['schema'] ?? null)
            || $turnId !== ($turn['turn_id'] ?? null)
            || !in_array($turn['status'] ?? null, ['CITADEL_LEGATE_GOVERNED_COGNITION_TURN_COMPLETED_SEALED_NO_CONTINUING_AUTHORITY', 'CITADEL_LEGATE_GOVERNED_COGNITION_TURN_STOPPED_SEALED_NO_CONTINUING_AUTHORITY'], true)
            || true !== ($turn['provider_invoked'] ?? null) || true !== ($turn['cognition_performed'] ?? null) || 1 !== ($turn['turns_consumed'] ?? null)
            || true !== ($turn['bounded_cognition_turn_authority']['consumed'] ?? null) || true !== ($turn['credential_lease']['consumed'] ?? null)
            || true === ($turn['commission_exercisable'] ?? null) || true === ($turn['governed_cognition_authority'] ?? null)
            || true === ($turn['provider_invocation_authority'] ?? null) || true === ($turn['continuing_turn_authority'] ?? null) || true !== ($turn['sealed'] ?? null)
            || 'imperium.citadel-legate-governed-commission/v1' !== ($commission['schema'] ?? null)
            || 'imperium.citadel-legate-governed-commission-disposition/v1' !== ($disposition['schema'] ?? null) || 'ACCEPTED' !== ($disposition['disposition'] ?? null)
            || 'imperium.citadel-legate-cognition-turn-authorization-decision/v1' !== ($decision['schema'] ?? null) || 'AUTHORIZED' !== ($decision['decision'] ?? null)
            || 'imperium.clavium-citadel-legate-provider-invocation-activation/v1' !== ($providerActivation['schema'] ?? null)
            || ($disposition['source_commission']['id'] ?? null) !== ($commission['commission_id'] ?? null)
            || ($disposition['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || ($decision['source_commission']['id'] ?? null) !== ($commission['commission_id'] ?? null)
            || ($decision['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || ($decision['source_commission_disposition']['id'] ?? null) !== ($disposition['disposition_id'] ?? null)
            || ($decision['source_commission_disposition']['digest'] ?? null) !== ($disposition['record_digest'] ?? null)
            || ($providerActivation['source_cognition_turn_authorization']['id'] ?? null) !== ($decision['decision_id'] ?? null)
            || ($providerActivation['source_cognition_turn_authorization']['digest'] ?? null) !== ($decision['record_digest'] ?? null)
            || ($commission['contract'] ?? null) !== ($turn['contract'] ?? null)
            || ($commission['issuer']['binding_id'] ?? null) !== $commissionerBindingId
            || !$this->valid($commissioner) || $commissionerBindingId !== ($commissioner['binding_id'] ?? null)
            || ($turn['instance_id'] ?? null) !== ($commissioner['instance_id'] ?? null)
            || ($commission['issuer']['seat'] ?? null) !== ($commissioner['seat'] ?? null)
            || ($commission['issuer']['binding_digest'] ?? null) !== ($commissioner['record_digest'] ?? null)
            || ($commission['issuer']['manifestation_id'] ?? null) !== ($commissioner['manifestation_id'] ?? null)
            || ($commission['issuer']['occupancy_generation'] ?? null) !== ($commissioner['occupancy_generation'] ?? null)
            || 'ACTIVE' !== ($commissioner['status'] ?? null) || true !== ($commissioner['binding_atomic'] ?? null) || true !== ($commissioner['sealed'] ?? null)) {
            throw new \RuntimeException('CIT425_COGNITION_RESULT_DELIVERY_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentCommissionerOccupancy(array $commissioner): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CIT429_COMMISSIONER_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) === ($commissioner['seat'] ?? null)
                && ($other['binding_id'] ?? null) !== ($commissioner['binding_id'] ?? null)
                && 'ACTIVE' === ($other['status'] ?? null)) {
                throw new \RuntimeException('CIT429_COMMISSIONER_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function source(string $directory, array $reference, string $error): array
    {
        return $this->validator->resolve($directory, $reference, $error, 'CIT425_COGNITION_RESULT_DELIVERY_CHAIN_INVALID');
    }

    private function read(string $path, string $error): array
    {
        return $this->validator->read($path, $error);
    }

    private function valid(array $record): bool
    {
        return $this->validator->isIntact($record);
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->deliveries) && !mkdir($this->deliveries, 0770, true) && !is_dir($this->deliveries)) {
            throw new \RuntimeException('CIT426_COGNITION_RESULT_DELIVERY_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->deliveries.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CIT428_COGNITION_RESULT_DELIVERY_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CIT428_COGNITION_RESULT_DELIVERY_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CIT426_COGNITION_RESULT_DELIVERY_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
