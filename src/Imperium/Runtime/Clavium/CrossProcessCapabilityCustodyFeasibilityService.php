<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use App\Imperium\Runtime\LaCortine\EnvironmentCredentialBroker;
use App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CrossProcessCapabilityCustodyFeasibilityService
{
    public const string ASSESSMENTS = 'var/imperium/offices/clavium/cross-process-capability-custody-feasibility';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $atomic = new AtomicTransition($root);
        $this->validator = new RecordReferenceValidator($root);
        $this->records = new ImmutableRecordStore($root, $atomic);
    }

    public function assess(string $activationId, CredentialCapability $capability, EnvironmentCredentialBroker $issuer, EnvironmentCredentialBroker $recipient, \DateTimeImmutable $assessedAt): array
    {
        if (!preg_match('/^single-execution-provider-binding-activation-[a-f0-9]{20}$/', $activationId) || $issuer === $recipient) throw new \InvalidArgumentException('PBA500_CUSTODY_FEASIBILITY_INPUT_INVALID');
        $activation = $this->validator->read($this->root.'/'.SingleExecutionProviderBindingActivationService::ACTIVATIONS.'/'.$activationId.'.json', 'PBA501_ACTIVATION_ABSENT');
        if (!$this->validator->isIntact($activation) || SingleExecutionProviderBindingActivationContract::REQUIRED_FIELDS !== array_keys($activation) || SingleExecutionProviderBindingActivationContract::SCHEMA !== ($activation['schema'] ?? null) || 'ACTIVATED_UNCONSUMED' !== ($activation['status'] ?? null) || new \DateTimeImmutable($activation['expires_at']) <= $assessedAt) throw new \RuntimeException('PBA502_ACTIVATION_INVALID');
        $claim = $this->validator->resolve($this->root.'/'.DeterministicExecutionClaimService::CLAIMS, $activation['execution_claim'], 'PBA503_EXECUTION_CLAIM_ABSENT', 'PBA504_EXECUTION_CLAIM_MISMATCH', 'claim_id');
        $metadata = $claim['credential_capability'] ?? [];
        if (DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim) || DeterministicExecutionClaimContract::SCHEMA !== ($claim['schema'] ?? null) || 'CLAIMED_PRE_IO' !== ($claim['effect']['checkpoint'] ?? null) || false !== ($claim['effect']['external_io_started'] ?? null) || new \DateTimeImmutable($claim['expires_at']) <= $assessedAt || DeterministicExecutionClaimContract::REQUIRED_CREDENTIAL_CAPABILITY_FIELDS !== array_keys($metadata) || $capability->capabilityId !== $metadata['capability_id'] || !hash_equals($metadata['credential_reference_digest'], hash('sha256', $capability->credentialRef)) || $capability->commissionId !== $metadata['commission_id'] || $capability->operation !== $metadata['operation'] || $capability->expiresAt->format(DATE_ATOM) !== $metadata['expires_at'] || $capability->maxUses !== $metadata['max_uses'] || !$issuer->recognizesExactCapability($capability) || $recipient->recognizesExactCapability($capability) || $issuer->supportsCrossProcessCustody() || $recipient->supportsCrossProcessCustody()) throw new \RuntimeException('PBA505_CAPABILITY_IDENTITY_OR_BROKER_POSTURE_INVALID');
        $identity = ['capability_id' => $capability->capabilityId, 'identity_digest' => hash('sha256', CanonicalJson::encode($metadata)), 'credential_reference_digest' => $metadata['credential_reference_digest'], 'issuer_id' => EnvironmentCredentialBroker::class];
        $assessmentId = 'cross-process-capability-custody-feasibility-'.substr(hash('sha256', CanonicalJson::encode([$activationId, $activation['record_digest'], $identity])), 0, 20);

        return $this->records->put(self::ASSESSMENTS, $assessmentId, ['schema' => CrossProcessCapabilityCustodyFeasibilityContract::SCHEMA, 'assessment_id' => $assessmentId, 'instance_id' => $activation['instance_id'], 'source_activation' => ['id' => $activationId, 'digest' => $activation['record_digest'], 'schema' => $activation['schema']], 'capability_identity' => $identity, 'broker_assessment' => ['issuer_recognizes_exact_object' => true, 'recipient_recognizes_exact_object' => false, 'cross_process_custody_supported' => false, 'metadata_reconstruction_permitted' => false], 'disposition' => CrossProcessCapabilityCustodyFeasibilityContract::REFUSAL, 'reasons' => ['Issuer proof is process-local PHP object identity.', 'A distinct broker refuses the same capability as unissued.', 'Durable metadata cannot recreate or transfer capability authority.'], 'assessed_at' => $assessedAt->format(DATE_ATOM), 'custody_created' => false, 'delivery_created' => false, 'capability_issued' => false, 'capability_reconstructed' => false, 'credential_reference_persisted' => false, 'secret_material_persisted' => false, 'external_action_performed' => false, 'sealed' => true]);
    }
}
