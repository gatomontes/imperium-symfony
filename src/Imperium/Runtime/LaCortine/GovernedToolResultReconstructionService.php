<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Armory\CanonicalEmailSendToolDefinitionService;
use App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityContract;
use App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityService;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernedToolResultReconstructionService
{
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
    }

    public function reconstruct(string $admissionId, string $eligibilityId): array
    {
        if (!preg_match('/^normalized-tool-result-admission-[a-f0-9]{20}$/', $admissionId)
            || !preg_match('/^provider-bound-credential-eligibility-[a-f0-9]{20}$/', $eligibilityId)) {
            throw new \InvalidArgumentException('GTP700_RECONSTRUCTION_ID_INVALID');
        }

        $admission = $this->validator->read($this->root.'/'.NormalizedToolResultAdmissionService::ADMISSIONS.'/'.$admissionId.'.json', 'GTP701_NORMALIZED_ADMISSION_INVALID');
        if (!$this->validator->isIntact($admission) || $admissionId !== ($admission['admission_id'] ?? null)
            || 'ADMITTED_NORMALIZED' !== ($admission['status'] ?? null) || true === ($admission['raw_provider_content_interpreted'] ?? null)
            || true === ($admission['provider_reinvoked'] ?? null)) throw new \RuntimeException('GTP701_NORMALIZED_ADMISSION_INVALID');

        $result = $this->validator->resolve($this->root.'/'.ProviderBoundEvidenceNormalizationService::RESULTS, $admission['normalized_tool_result'], 'GTP702_NORMALIZED_RESULT_INVALID', 'GTP702_NORMALIZED_RESULT_INVALID', 'result_id');
        if (NormalizedToolResultContract::REQUIRED_FIELDS !== array_keys($result)
            || 'NORMALIZED_PENDING_LAZARETTO_ADMISSION' !== ($result['recovery']['checkpoint'] ?? null)
            || true === ($result['recovery']['automatic_replay_permitted'] ?? null)
            || true === ($result['recovery']['provider_reinvoked'] ?? null)) throw new \RuntimeException('GTP702_NORMALIZED_RESULT_INVALID');

        $binding = $this->validator->resolve($this->root.'/'.ProviderImplementationBindingService::BINDINGS, $result['provider_binding'], 'GTP703_PROVIDER_BINDING_INVALID', 'GTP703_PROVIDER_BINDING_INVALID', 'binding_id');
        if (ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($binding)
            || ($result['tool_operation'] ?? null) !== ($binding['tool_operation'] ?? null)
            || ($result['source_authorization']['id'] ?? null) !== ($binding['scope']['authorization_target_id'] ?? null)
            || ($result['source_authorization']['digest'] ?? null) !== ($binding['scope']['authorization_target_digest'] ?? null)
            || ($result['decoder']['decoder_id'] ?? null) !== ($binding['evidence_decoder']['id'] ?? null)) throw new \RuntimeException('GTP703_PROVIDER_BINDING_INVALID');

        $rawReference = (string) ($result['provider_evidence']['sealed_content_reference'] ?? '');
        if (!preg_match('~^'.preg_quote(ProviderNeutralRawEvidenceService::EVIDENCE, '~').'/([^/]+)\.json#content_base64$~', $rawReference, $matches)) throw new \RuntimeException('GTP704_RAW_EVIDENCE_INVALID');
        $raw = $this->validator->read($this->root.'/'.ProviderNeutralRawEvidenceService::EVIDENCE.'/'.$matches[1].'.json', 'GTP704_RAW_EVIDENCE_INVALID');
        if (!$this->validator->isIntact($raw) || ($raw['record_digest'] ?? null) !== ($result['provider_evidence']['raw_result_digest'] ?? null)
            || ($raw['provider_observation']['content_digest'] ?? null) !== ($result['provider_evidence']['content_digest'] ?? null)
            || ($raw['provider_binding'] ?? null) !== ($result['provider_binding'] ?? null)) throw new \RuntimeException('GTP704_RAW_EVIDENCE_INVALID');

        $eligibility = $this->validator->read($this->root.'/'.ProviderBoundCredentialEligibilityService::ELIGIBILITIES.'/'.$eligibilityId.'.json', 'GTP705_CREDENTIAL_ELIGIBILITY_INVALID');
        if (!$this->validator->isIntact($eligibility) || ProviderBoundCredentialEligibilityContract::REQUIRED_FIELDS !== array_keys($eligibility)
            || $eligibilityId !== ($eligibility['eligibility_id'] ?? null) || ($eligibility['provider_binding'] ?? null) !== ($result['provider_binding'] ?? null)
            || ($eligibility['authorization_target'] ?? null) !== ['id' => $result['source_authorization']['id'], 'digest' => $result['source_authorization']['digest']]
            || true === ($eligibility['credential_resolved'] ?? null) || true === ($eligibility['external_io_permitted'] ?? null)) throw new \RuntimeException('GTP705_CREDENTIAL_ELIGIBILITY_INVALID');

        $tool = (new CanonicalEmailSendToolDefinitionService($this->root))->read();
        if (($result['tool_operation']['id'] ?? null) !== $tool['tool_id'].'.v'.$tool['tool_version']
            || ($result['tool_operation']['digest'] ?? null) !== $tool['record_digest']) throw new \RuntimeException('GTP706_TOOL_DEFINITION_INVALID');

        return ['tool_definition' => $tool, 'source_authorization' => $result['source_authorization'], 'execution_claim' => $result['execution_claim'],
            'provider_binding' => $binding, 'credential_eligibility' => $eligibility, 'credential_consumption_attempt' => null,
            'raw_provider_evidence' => $raw, 'decoder' => $result['decoder'], 'normalized_result' => $result, 'lazaretto_admission' => $admission,
            'read_only' => true, 'provider_reinvoked' => false, 'credential_resolved' => false, 'external_io_performed' => false, 'continuing_authority' => false];
    }
}
