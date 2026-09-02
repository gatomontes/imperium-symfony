<?php

declare(strict_types=1);

namespace App\ReproofV2;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as Validator;

/** In-memory trusted corridor. Owns every result; has no IO or signing capability. */
final class Runner
{
    public function run(string $proofId, array $source, string $authorizationDigest, string $executorDigest): array
    {
        foreach ([$authorizationDigest, $executorDigest] as $digest) {
            if (!preg_match('/^[a-f0-9]{64}$/D', $digest)) {
                throw new \RuntimeException('REPROOF_BINDING_INVALID');
            }
        }
        $cases = (new CaseProfile())->inputs($proofId);
        $inputRoot = Records::hash(array_column(array_column($cases, 'input'), 'record_digest'));
        $expectedRoot = Records::hash(array_column(array_column($cases, 'expected'), 'record_digest'));
        $origin = Records::seal(['schema' => Contract::SCHEMAS['origin'], 'proof_id' => $proofId,
            'source_digest' => $source['record_digest'], 'input_root' => $inputRoot, 'expected_root' => $expectedRoot,
            'authorization_digest' => $authorizationDigest, 'runtime_version' => PHP_VERSION]);
        $classifier = new Classifier(new Validator());
        foreach ($cases as &$case) {
            $input = $case['input'];
            try {
                $classification = $classifier->classify($input['primary']);
                $comparison = null === $input['comparison'] ? 'NOT_APPLICABLE' : $classifier->compare($input['primary'], $input['comparison']);
            } catch (\Throwable) {
                // Never retain raw exception text, object or offending input in diagnostics.
                throw new \RuntimeException('REPROOF_CASE_VALIDATION_REFUSED');
            }
            $values = ['classification' => $classification, 'directive' => $input['plan']['directives'][$classification],
                'comparison' => $comparison, 'validator_error' => null,
                'findings' => ['NOT_APPLICABLE' === $comparison ? $classification.'_READ_ONLY' : $comparison]];
            foreach ($values as $field => $value) {
                if ($value !== $case['expected'][$field]) {
                    throw new \RuntimeException('REPROOF_EXPECTATION_MISMATCH');
                }
            }
            $case['observed'] = Records::seal(['schema' => Contract::SCHEMAS['observed'], 'case_id' => $input['case_id'],
                'input_digest' => $input['record_digest'], 'expected_digest' => $case['expected']['record_digest'],
                'executor_digest' => $executorDigest] + $values);
        }
        unset($case);
        $matrix = Records::seal(['schema' => Contract::SCHEMAS['matrix'], 'profile' => Contract::PROFILE,
            'cases' => $cases, 'input_root' => $inputRoot, 'expected_root' => $expectedRoot,
            'observed_root' => Records::hash(array_column(array_column($cases, 'observed'), 'record_digest'))]);
        $graph = SourceBundle::graph($source);
        $exclusion = (new PayloadExclusion())->prove(['origin' => $origin, 'matrix' => $matrix, 'graph' => $graph]);
        $receipt = Records::seal(['schema' => Contract::SCHEMAS['receipt'], 'proof_id' => $proofId,
            'origin' => $origin, 'source' => $source, 'matrix' => $matrix, 'graph' => $graph, 'exclusion' => $exclusion]);
        $candidate = Records::seal(['schema' => Contract::SCHEMAS['candidate'], 'proof_id' => $proofId,
            'source_commit' => $source['commit'], 'source_manifest_root' => $source['manifest_root'],
            'origin_digest' => $origin['record_digest'], 'receipt_digest' => $receipt['record_digest'],
            'input_root' => $inputRoot, 'expected_root' => $expectedRoot, 'observed_root' => $matrix['observed_root'],
            'retention' => Contract::RETENTION, 'disposition' => 'CANDIDATE_NOT_VERIFIED']);
        return ['receipt' => $receipt, 'candidate' => $candidate];
    }
}
