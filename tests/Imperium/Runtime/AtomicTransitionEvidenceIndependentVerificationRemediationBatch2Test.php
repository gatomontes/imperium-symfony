<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\IndependentVerification\AtomicTransitionArtifactAndReceiptVerifier as Verifier;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationInputContract as Input;
use PHPUnit\Framework\TestCase;

class AtomicTransitionEvidenceIndependentVerificationRemediationBatch2Test extends TestCase
{
    public function testRetainedV1ShapeIsIndeterminateWithoutUnderlyingAcceptanceCases(): void
    {
        [$input, $summary, $receipt, $bytes] = $this->fixture();
        $report = (new Verifier())->verify('report.1', $input, $summary, $receipt, $bytes, $this->ref('identity.1'));

        self::assertSame('INDETERMINATE', $report['disposition']);
        self::assertSame('INDETERMINATE', $report['domain_outcomes']['acceptance_matrix']);
        foreach (array_diff(array_keys($report['domain_outcomes']), ['acceptance_matrix']) as $domain) {
            self::assertSame('PASS', $report['domain_outcomes'][$domain], $domain);
        }
        self::assertFalse($report['producer_disposition_imported']);
        self::assertFalse($report['producer_success_boolean_imported']);
    }

    public function testArtifactAndReceiptSubstitutionRefuse(): void
    {
        [$input, $summary, $receipt, $bytes] = $this->fixture();
        $bytes['runner'] = 'substituted';
        $receipt['origin']['status'] = 'counterfeit';
        $receipt['record_digest'] = $this->digest($receipt);

        $report = (new Verifier())->verify('report.2', $input, $summary, $receipt, $bytes, $this->ref('identity.1'));
        self::assertSame('REFUSED', $report['disposition']);
        self::assertSame('REFUSED', $report['domain_outcomes']['source_and_build']);
        self::assertSame('REFUSED', $report['domain_outcomes']['receipt_structure']);
    }

    public function testVerifierHasNoProducerOrClosureImplementationDependency(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/IndependentVerification/AtomicTransitionArtifactAndReceiptVerifier.php');
        foreach (['AtomicTransitionIntegratedDisposableMission', 'AtomicTransitionEvidenceIndependentReconstructor',
            'AtomicTransitionEvidenceTerminalAdversarialAuditor', "['disposition']"] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    protected function fixture(): array
    {
        $bytes = ['source_commit' => 'tree 0\n\nauthor synthetic\n', 'source_tree' => 'tree',
            'build' => 'build', 'lock' => 'lock', 'runner' => 'runner', 'mission' => 'mission'];
        $bindings = array_map(static fn (string $value): string => hash('sha256', $value), $bytes);
        $sourceCommit = hash('sha1', 'commit '.strlen($bytes['source_commit'])."\0".$bytes['source_commit']);
        $record = fn (string $schema): array => $this->seal(['schema' => $schema, 'sealed' => true]);
        $receipt = [
            'schema' => 'imperium.private-atomic-transition-integrated-disposable-mission/v1',
            'mission_id' => 'ATOMIC-TRANSITION-DISPOSABLE-PROOF-1',
            'source' => ['source_commit' => $sourceCommit, 'source_tree_digest' => $bindings['source_tree']],
            'origin' => $record('origin/v1'), 'provenance' => $record('provenance/v1'),
            'case' => $record('case/v1'), 'fixture' => $record('fixture/v1'),
            'mutation' => $record('mutation/v1'), 'expected' => $record('expected/v1'),
            'plan' => $record('plan/v1'), 'trusted_result' => $record('result/v1'),
            'dependency_graph' => $record('graph/v1'), 'acceptance_matrix' => ['exact_replay' => 'EXACT_REPLAY'],
            'caller_result_accepted' => false, 'provider_or_external_effect_authorized' => false,
            'live_credential_or_capability_authorized' => false, 'runtime_state_written' => false,
            'continuing_authority' => false,
            'complete_chain_content_exclusion_observed' => true,
        ];
        $receipt['record_digest'] = $this->digest($receipt);
        $summary = [
            'source_commit' => $receipt['source']['source_commit'],
            'source_tree_digest' => $receipt['source']['source_tree_digest'],
            'build_artifact_digest' => $bindings['build'],
            'dependency_lock_digest' => $bindings['lock'],
            'runner_digest' => $bindings['runner'],
            'mission_implementation_digest' => $bindings['mission'],
            'private_receipt_digest' => $receipt['record_digest'],
            'evidence_origin_digest' => $receipt['origin']['record_digest'],
            'execution_provenance_digest' => $receipt['provenance']['record_digest'],
            'trusted_result_digest' => $receipt['trusted_result']['record_digest'],
            'dependency_graph_digest' => $receipt['dependency_graph']['record_digest'],
            'acceptance_matrix' => $receipt['acceptance_matrix'],
        ];
        $input = $this->seal([
            'schema' => Input::SCHEMA, 'verification_id' => 'verification.1',
            'sanitized_evidence' => $this->ref('evidence.1'),
            'source_commit' => $receipt['source']['source_commit'],
            'source_tree_digest' => $bindings['source_tree'], 'artifact_bindings' => $bindings,
            'private_receipt_digest' => $receipt['record_digest'],
            'private_receipt_availability' => 'AVAILABLE_OPERATOR_LOCAL',
            'private_receipt_locator_supplied' => true,
            'producer_reconstruction_supplied' => false, 'producer_conclusion_supplied' => false,
            'read_only' => true, 'authority_empty' => true, 'execution_authorized' => false,
            'provider_authorized' => false, 'external_io_authorized' => false,
            'runtime_write_authorized' => false, 'continuing_authority' => false, 'sealed' => true,
        ]);
        return [$input, $summary, $receipt, $bytes];
    }

    protected function ref(string $id): array
    {
        return ['id' => $id, 'digest' => str_repeat('a', 64), 'schema' => 'reference/v1'];
    }

    protected function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    protected function digest(array $record): string
    {
        unset($record['record_digest']);
        return hash('sha256', CanonicalJson::encode($record));
    }
}
