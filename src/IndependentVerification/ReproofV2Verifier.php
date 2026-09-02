<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\ReproofV2\Records;

/** Read-only evidence verification. Trust is supplied separately by the operator, never from the package. */
final class ReproofV2Verifier
{
    private const array DOMAINS = ['source_and_build', 'receipt_structure', 'origin_and_provenance',
        'trusted_result', 'dependency_graph', 'acceptance_matrix', 'complete_chain_exclusion', 'non_authority_perimeter'];
    private const array IMPLEMENTATION_FILES = ['src/Bootstrap/CanonicalJson.php', 'src/ReproofV2/Records.php',
        'src/IndependentVerification/ReproofV2CaseEvaluator.php', 'src/IndependentVerification/ReproofV2Exclusion.php',
        'src/IndependentVerification/ReproofV2SourceProof.php', 'src/IndependentVerification/ReproofV2Verifier.php'];

    public static function implementationRoot(): string
    {
        $files = [];
        foreach (self::IMPLEMENTATION_FILES as $path) {
            $hash = hash_file('sha256', dirname(__DIR__, 2).'/'.$path);
            if (false === $hash) { throw new \RuntimeException('REPROOF_VERIFIER_BYTES_UNAVAILABLE'); }
            $files[$path] = $hash;
        }
        return Records::hash($files);
    }

    public function preflight(?array $package): string
    {
        if (null === $package) { return 'INDETERMINATE_MISSING_PACKAGE'; }
        if (($package['receipt']['schema'] ?? null) !== 'imperium.atomic-transition-reproof.private-receipt/v2') { return 'REFUSED_NOT_V2'; }
        if (count($package['receipt']['matrix']['cases'] ?? []) !== 8) { return 'REFUSED_MISSING_CASE_EVIDENCE'; }
        return 'ELIGIBLE_FOR_VERIFICATION_NOT_AUTHORIZED';
    }

    public function verify(array $package, array $trust): array
    {
        // Reject malformed trust before making any public projection of caller values.
        $this->keys($trust, ['proof_id', 'source_commit', 'source_manifest_root', 'authorization_digest',
            'runtime_version', 'verifier_root', 'identity_digest', 'evidence_class']);
        $this->require(is_string($trust['proof_id']) && 1 === preg_match('/^reproof-v2-[a-z0-9-]{3,80}$/D', $trust['proof_id']));
        $this->require(is_string($trust['source_commit']) && 1 === preg_match('/^[a-f0-9]{40}$/D', $trust['source_commit']));
        foreach (['source_manifest_root', 'authorization_digest', 'verifier_root', 'identity_digest'] as $field) {
            $this->require(is_string($trust[$field]) && 1 === preg_match('/^[a-f0-9]{64}$/D', $trust[$field]));
        }
        $this->require(is_string($trust['runtime_version']) && 1 === preg_match('/^8\.[4-9]\.[0-9]+$/D', $trust['runtime_version']));
        $this->require(in_array($trust['evidence_class'], ['SYNTHETIC_TEST', 'OPERATOR_AUTHORIZED_LOCAL_EXECUTION'], true));
        $domains = array_fill_keys(self::DOMAINS, 'REFUSED');
        $candidateDigest = str_repeat('0', 64); $receiptDigest = str_repeat('0', 64);
        try {
            $this->require(self::implementationRoot() === $trust['verifier_root']);
            $this->keys($package, ['receipt', 'candidate']);
            $receipt = $package['receipt']; $candidate = $package['candidate'];
            $this->sealed($receipt, ['schema', 'proof_id', 'origin', 'source', 'matrix', 'graph', 'exclusion', 'record_digest'], 'private-receipt');
            $this->sealed($candidate, ['schema', 'proof_id', 'source_commit', 'source_manifest_root', 'origin_digest', 'receipt_digest',
                'input_root', 'expected_root', 'observed_root', 'retention', 'disposition', 'record_digest'], 'sanitized-candidate');
            $this->require($receipt['proof_id'] === $trust['proof_id'] && $candidate['proof_id'] === $trust['proof_id']);
            $this->require($candidate['receipt_digest'] === $receipt['record_digest']);
            $candidateDigest = $candidate['record_digest']; $receiptDigest = $receipt['record_digest'];
            $domains['receipt_structure'] = 'PASS';

            $source = $receipt['source'];
            $this->keys($source, ['schema', 'object_format', 'commit', 'commit_bytes', 'trees', 'files', 'manifest_root', 'record_digest']);
            $derived = (new ReproofV2SourceProof())->verify($source, $trust);
            $this->require($candidate['source_commit'] === $source['commit'] && $candidate['source_manifest_root'] === $source['manifest_root']);
            $domains['source_and_build'] = 'PASS';

            $origin = $receipt['origin']; $matrix = $receipt['matrix'];
            $this->sealed($origin, ['schema', 'proof_id', 'source_digest', 'input_root', 'expected_root', 'authorization_digest', 'runtime_version', 'record_digest'], 'origin');
            $this->require($origin['proof_id'] === $trust['proof_id'] && $origin['source_digest'] === $source['record_digest']
                && $origin['authorization_digest'] === $trust['authorization_digest'] && $origin['runtime_version'] === $trust['runtime_version']
                && $candidate['origin_digest'] === $origin['record_digest']);
            foreach (['input_root', 'expected_root'] as $root) { $this->require($origin[$root] === ($matrix[$root] ?? null)); }
            foreach (['input_root', 'expected_root', 'observed_root'] as $root) { $this->require($candidate[$root] === ($matrix[$root] ?? null)); }
            $domains['origin_and_provenance'] = 'PASS';

            (new ReproofV2CaseEvaluator())->evaluate($matrix, $trust['proof_id'], $derived['manifest']['src/ReproofV2/Runner.php']['sha256']);
            $domains['trusted_result'] = 'PASS'; $domains['acceptance_matrix'] = 'PASS';
            $this->require(Records::same($receipt['graph'], $derived['graph']));
            $domains['dependency_graph'] = 'PASS';

            $scanner = new ReproofV2Exclusion();
            $exclusion = $scanner->derive(['origin' => $origin, 'matrix' => $matrix, 'graph' => $derived['graph']]);
            $this->require(Records::same($receipt['exclusion'], $exclusion));
            $scanner->scan($candidate);
            $this->require($candidate['retention'] === 'OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT'
                && $candidate['disposition'] === 'CANDIDATE_NOT_VERIFIED');
            $domains['complete_chain_exclusion'] = 'PASS';
            // Exact externally pinned source/loader, independent graph, finite inert records
            // and false transaction effect fields jointly establish this bounded perimeter.
            // It does not claim that untrusted PHP/native infrastructure has been sandboxed.
            $domains['non_authority_perimeter'] = 'PASS';
        } catch (\Throwable) {
            // Domain remains REFUSED. No exception strings or private values leave this method.
        }
        $disposition = in_array('REFUSED', $domains, true) ? 'REFUSED'
            : ('SYNTHETIC_TEST' === $trust['evidence_class'] ? 'SYNTHETIC_PASS_NOT_ADMISSIBLE' : 'PASS');
        return Records::seal(['schema' => 'imperium.atomic-transition-reproof.independent-report/v2',
            'proof_id' => $trust['proof_id'], 'candidate_digest' => $candidateDigest, 'receipt_digest' => $receiptDigest,
            'source_commit' => $trust['source_commit'], 'verifier_root' => $trust['verifier_root'],
            'trusted_identity_digest' => $trust['identity_digest'], 'domain_outcomes' => $domains, 'disposition' => $disposition,
            'qualification_removed' => false, 'campaign_closed' => false]);
    }

    private function sealed(array $record, array $fields, string $kind): void
    {
        $this->keys($record, $fields);
        $this->require($record['schema'] === 'imperium.atomic-transition-reproof.'.$kind.'/v2'
            && Records::same($record, Records::seal($record)));
    }

    private function keys(array $record, array $fields): void
    {
        $keys = array_keys($record); sort($keys); sort($fields); $this->require($keys === $fields);
    }

    private function require(bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('REPROOF_VERIFICATION_REFUSED'); }
    }
}
