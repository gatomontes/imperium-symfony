<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicReceiptReconstructionService
{
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
    }

    public function reconstruct(string $bindingId): array
    {
        if (!preg_match('/^deterministic-receipt-binding-[a-f0-9]{20}$/', $bindingId)) throw new \InvalidArgumentException('IGX900_RECEIPT_BINDING_ID_INVALID');
        $binding = $this->validator->read($this->root.'/'.DeterministicLazarettoReceiptAdmissionService::BINDINGS.'/'.$bindingId.'.json', 'IGX901_RECEIPT_BINDING_ABSENT');
        if (!$this->validator->isIntact($binding) || DeterministicReceiptBindingContract::REQUIRED_FIELDS !== array_keys($binding) || $bindingId !== ($binding['binding_id'] ?? null) || true !== ($binding['lazaretto_admission']['expected_return_contract_validated'] ?? null) || 'COMPLETE' !== ($binding['recovery']['checkpoint'] ?? null) || true === ($binding['recovery']['automatic_replay_permitted'] ?? null) || true === ($binding['recovery']['provider_reinvoked'] ?? null)) throw new \RuntimeException('IGX902_RECEIPT_BINDING_INVALID');
        $claim = $this->validator->resolve($this->root.'/'.DeterministicExecutionClaimService::CLAIMS, ['id' => $binding['execution_claim']['id'], 'digest' => $binding['execution_claim']['digest']], 'IGX903_EXECUTION_CLAIM_INVALID', 'IGX903_EXECUTION_CLAIM_INVALID', 'claim_id');
        if (($binding['execution_claim']['replay_fingerprint'] ?? null) !== ($claim['replay_fingerprint'] ?? null) || ($binding['execution_claim']['execution_id'] ?? null) !== ($claim['execution_identity']['execution_id'] ?? null) || ($binding['source_authorization'] ?? null) !== ['id' => $claim['source_authorization']['id'], 'digest' => $claim['source_authorization']['digest']]) throw new \RuntimeException('IGX903_EXECUTION_CLAIM_INVALID');
        if (!preg_match('~^'.preg_quote(DeterministicRawProviderResultService::RESULTS, '~').'/([^/]+)\.json#raw_receipt\.content_base64$~', (string) ($binding['raw_receipt']['sealed_content_reference'] ?? ''), $matches)) throw new \RuntimeException('IGX904_RAW_PROVIDER_RESULT_INVALID');
        $result = $this->validator->read($this->root.'/'.DeterministicRawProviderResultService::RESULTS.'/'.$matches[1].'.json', 'IGX904_RAW_PROVIDER_RESULT_INVALID');
        if (!$this->validator->isIntact($result) || ($result['raw_receipt']['id'] ?? null) !== ($binding['raw_receipt']['id'] ?? null) || ($result['raw_receipt']['content_digest'] ?? null) !== ($binding['raw_receipt']['content_digest'] ?? null) || ($result['provider_outcome']['status'] ?? null) !== ($binding['provider_outcome']['status'] ?? null)) throw new \RuntimeException('IGX904_RAW_PROVIDER_RESULT_INVALID');
        $issuance = null;
        foreach (glob($this->root.'/'.OutboundEmailAuthorizationIssuanceService::ISSUANCES.'/*.json') ?: [] as $path) {
            $candidate = $this->validator->read($path, 'IGX905_SOURCE_AUTHORIZATION_INVALID');
            if (($candidate['issued_authorization']['authorization_id'] ?? null) !== $binding['source_authorization']['id']) continue;
            if (!$this->validator->isIntact($candidate) || !is_array($candidate['issued_authorization'] ?? null) || !$this->validator->isIntact($candidate['issued_authorization']) || ($candidate['issued_authorization']['record_digest'] ?? null) !== $binding['source_authorization']['digest']) throw new \RuntimeException('IGX905_SOURCE_AUTHORIZATION_INVALID');
            $issuance = $candidate;
            break;
        }
        if (null === $issuance) throw new \RuntimeException('IGX905_SOURCE_AUTHORIZATION_INVALID');

        return ['source_authorization' => $issuance['issued_authorization'], 'execution_claim' => $claim, 'raw_provider_result' => $result, 'receipt_binding' => $binding, 'provider_reinvoked' => false, 'credential_resolved' => false, 'external_io_performed' => false];
    }
}
