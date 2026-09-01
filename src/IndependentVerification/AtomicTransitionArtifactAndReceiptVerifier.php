<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationContractValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationReportContract as Report;

/** Separate, read-only verifier. It imports no producer or closure implementation. */
final readonly class AtomicTransitionArtifactAndReceiptVerifier
{
    public const string IMPLEMENTATION = 'imperium.independent.atomic-transition-artifact-receipt-verifier/v1';

    public function __construct(
        private AtomicTransitionIndependentVerificationContractValidator $contracts = new AtomicTransitionIndependentVerificationContractValidator(),
    ) {
    }

    /**
     * @param array<string, string> $artifactBytes Exact retained artifact bytes keyed by binding name.
     */
    public function verify(
        string $reportId,
        array $input,
        array $summary,
        array $receipt,
        array $artifactBytes,
        array $verifierIdentity,
    ): array {
        $this->contracts->validate($input);
        $domains = array_fill_keys(Report::DOMAINS, 'PASS');

        if ('AVAILABLE_OPERATOR_LOCAL' !== $input['private_receipt_availability']
            || true !== $input['private_receipt_locator_supplied']) {
            $domains['receipt_structure'] = 'INDETERMINATE';
        }
        $receiptDigest = $this->digestRecord($receipt);
        if (!hash_equals($input['private_receipt_digest'], $receiptDigest)
            || !hash_equals((string) ($summary['private_receipt_digest'] ?? ''), $receiptDigest)) {
            $domains['receipt_structure'] = 'REFUSED';
        }
        if (!$this->receiptShapeAndSealsValid($receipt)) {
            $domains['receipt_structure'] = 'REFUSED';
        }

        if (!$this->artifactsMatch($input['artifact_bindings'], $artifactBytes, $summary, $input)) {
            $domains['source_and_build'] = 'REFUSED';
        }
        if (!$this->sourceBindingsMatch($receipt, $summary, $input)) {
            $domains['source_and_build'] = 'REFUSED';
        }
        if (!$this->sectionDigestMatches($receipt, $summary, 'origin', 'evidence_origin_digest')
            || !$this->sectionDigestMatches($receipt, $summary, 'provenance', 'execution_provenance_digest')) {
            $domains['origin_and_provenance'] = 'REFUSED';
        }
        if (!$this->sectionDigestMatches($receipt, $summary, 'trusted_result', 'trusted_result_digest')) {
            $domains['trusted_result'] = 'REFUSED';
        }
        if (!$this->sectionDigestMatches($receipt, $summary, 'dependency_graph', 'dependency_graph_digest')) {
            $domains['dependency_graph'] = 'REFUSED';
        }

        // v1 retained only producer conclusions, not the eight underlying case inputs.
        // The retained v1 schema has no underlying case evidence by definition.
        $domains['acceptance_matrix'] = 'INDETERMINATE';
        if (!$this->clean($receipt)) {
            $domains['complete_chain_exclusion'] = 'REFUSED';
        }
        if (!$this->nonAuthorityDerived($receipt)) {
            $domains['non_authority_perimeter'] = 'REFUSED';
        }

        $disposition = in_array('REFUSED', $domains, true)
            ? 'REFUSED'
            : (in_array('INDETERMINATE', $domains, true) ? 'INDETERMINATE' : 'PASS');
        $report = [
            'schema' => Report::SCHEMA,
            'report_id' => $reportId,
            'verification_id' => $input['verification_id'],
            'verifier_identity' => $verifierIdentity,
            'sanitized_evidence' => $input['sanitized_evidence'],
            'private_receipt_digest' => $receiptDigest,
            'domain_outcomes' => $domains,
            'producer_disposition_imported' => false,
            'producer_success_boolean_imported' => false,
            'sanitized' => true,
            'receipt_content_retained' => false,
            'receipt_locator_retained' => false,
            'private_material_retained' => false,
            'provider_binding_status' => 'BOUND_INACTIVE',
            'required_v3_execution_admission' => 'NOT_IMPLEMENTED',
            'unknown_replay_posture' => 'UNKNOWN_REPLAY_PROHIBITED',
            'read_only' => true,
            'runtime_state_written' => false,
            'authority_issued_or_consumed' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'continuing_authority' => false,
            'disposition' => $disposition,
            'sealed' => true,
        ];
        $report['record_digest'] = hash('sha256', CanonicalJson::encode($report));
        $this->contracts->validate($report);

        return $report;
    }

    private function artifactsMatch(array $bindings, array $bytes, array $summary, array $input): bool
    {
        if ([] === $bindings || array_keys($bindings) !== array_keys($bytes)) {
            return false;
        }
        foreach ($bindings as $name => $digest) {
            if (!is_string($name) || !is_string($digest) || !is_string($bytes[$name])
                || !hash_equals($digest, hash('sha256', $bytes[$name]))) {
                return false;
            }
        }
        $summaryBindings = [
            'source_tree' => 'source_tree_digest', 'build' => 'build_artifact_digest',
            'lock' => 'dependency_lock_digest', 'runner' => 'runner_digest',
            'mission' => 'mission_implementation_digest',
        ];
        foreach ($summaryBindings as $artifact => $field) {
            if (!isset($bindings[$artifact]) || !hash_equals($bindings[$artifact], (string) ($summary[$field] ?? ''))) {
                return false;
            }
        }
        if (!isset($bytes['source_commit'])) {
            return false;
        }
        $gitObject = 'commit '.strlen($bytes['source_commit'])."\0".$bytes['source_commit'];
        return hash_equals($input['source_commit'], hash('sha1', $gitObject));
    }

    private function sourceBindingsMatch(array $receipt, array $summary, array $input): bool
    {
        $source = $receipt['source'] ?? null;
        return is_array($source)
            && ($source['source_commit'] ?? null) === $input['source_commit']
            && ($summary['source_commit'] ?? null) === $input['source_commit']
            && ($source['source_tree_digest'] ?? null) === $input['source_tree_digest']
            && ($summary['source_tree_digest'] ?? null) === $input['source_tree_digest'];
    }

    private function sectionDigestMatches(array $receipt, array $summary, string $section, string $field): bool
    {
        return isset($receipt[$section]) && is_array($receipt[$section])
            && isset($receipt[$section]['record_digest'])
            && hash_equals((string) ($summary[$field] ?? ''), (string) $receipt[$section]['record_digest']);
    }

    private function receiptShapeAndSealsValid(array $receipt): bool
    {
        $required = ['schema', 'mission_id', 'source', 'origin', 'provenance', 'case', 'fixture',
            'mutation', 'expected', 'plan', 'trusted_result', 'dependency_graph',
            'acceptance_matrix', 'caller_result_accepted',
            'provider_or_external_effect_authorized', 'live_credential_or_capability_authorized',
            'runtime_state_written', 'continuing_authority',
            'complete_chain_content_exclusion_observed', 'record_digest'];
        if ('imperium.private-atomic-transition-integrated-disposable-mission/v1' !== $receipt['schema']
            || $required !== array_keys($receipt)
            || !hash_equals((string) $receipt['record_digest'], $this->digestRecord($receipt))) {
            return false;
        }
        foreach (['origin', 'provenance', 'case', 'fixture', 'mutation', 'expected', 'plan',
            'trusted_result', 'dependency_graph'] as $section) {
            if (!$this->sealed($receipt[$section])) {
                return false;
            }
        }
        return true;
    }

    private function sealed(mixed $record): bool
    {
        return is_array($record) && true === ($record['sealed'] ?? null)
            && isset($record['record_digest'])
            && hash_equals((string) $record['record_digest'], $this->digestRecord($record));
    }

    private function digestRecord(array $record): string
    {
        unset($record['record_digest']);
        return hash('sha256', CanonicalJson::encode($record));
    }

    private function nonAuthorityDerived(array $receipt): bool
    {
        foreach (['caller_result_accepted', 'provider_or_external_effect_authorized',
            'live_credential_or_capability_authorized', 'runtime_state_written',
            'continuing_authority'] as $field) {
            if (false !== ($receipt[$field] ?? null)) {
                return false;
            }
        }
        return true;
    }

    private function clean(mixed $value, ?string $key = null): bool
    {
        if (is_object($value) || is_resource($value)
            || (null !== $key && preg_match('/(?:secret|password|token|private[_-]?key|credential[_-]?value)/i', $key))) {
            return false;
        }
        if (is_array($value)) {
            foreach ($value as $childKey => $child) {
                if (!$this->clean($child, is_string($childKey) ? $childKey : null)) {
                    return false;
                }
            }
        }
        return !is_string($value) || !preg_match('/Bearer\s+\S+|-----BEGIN [A-Z ]+PRIVATE KEY-----|process-local-capability:\/\//', $value);
    }
}
