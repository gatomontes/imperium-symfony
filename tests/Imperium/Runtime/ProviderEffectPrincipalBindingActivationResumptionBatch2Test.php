<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionContract as Production;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract as Decision;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalFixtureStore;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalInputContract as Input;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract as Admission;
use PHPUnit\Framework\TestCase;

final class ProviderEffectPrincipalBindingActivationResumptionBatch2Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-pra-batch2-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testExactCallerSuppliedFixturesStoreAndReplaySeparately(): void
    {
        $f = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);

        self::assertSame(
            $store->putResolutionAdmission(
                $f['admission'], $f['production'], $f['decision'], $f['attestation'],
                $f['assurance'], $f['boundary'], $f['at'],
            ),
            $store->putResolutionAdmission(
                $f['admission'], $f['production'], $f['decision'], $f['attestation'],
                $f['assurance'], $f['boundary'], $f['at'],
            ),
        );
        self::assertSame(
            $store->putActivationInput(
                $f['input'], $f['admission'], $f['production'], $f['decision'],
                $f['attestation'], $f['assurance'], $f['boundary'], $f['at'],
            ),
            $store->putActivationInput(
                $f['input'], $f['admission'], $f['production'], $f['decision'],
                $f['attestation'], $f['assurance'], $f['boundary'], $f['at'],
            ),
        );

        self::assertDirectoryExists(
            $this->root.'/'.ProviderExecutorPrincipalActivationCanonicalFixtureStore::RESOLUTION_ADMISSIONS,
        );
        self::assertDirectoryExists(
            $this->root.'/'.ProviderExecutorPrincipalActivationCanonicalFixtureStore::ACTIVATION_INPUTS,
        );
        foreach (glob($this->root.'/var/imperium/evidence/provider-principal-binding-activation-resumption/*/*.json') ?: [] as $path) {
            $record = (string) file_get_contents($path);
            foreach (['credential_secret', 'AGENTMAIL_API_KEY', 'access_token', 'CredentialCapability'] as $secret) {
                self::assertStringNotContainsString($secret, $record);
            }
        }
    }

    public function testTamperingReferenceTargetAuthorityAndRootDriftFailClosed(): void
    {
        $f = $this->fixtures();
        $validator = new ProviderExecutorPrincipalActivationCanonicalContractValidator();

        $cases = [];
        $tampered = $f['admission'];
        $tampered['admitted_at'] = '2026-08-31T12:00:01+00:00';
        $cases[] = $tampered;

        $changedReference = $f['admission'];
        $changedReference['production_decision']['digest'] = str_repeat('f', 64);
        $cases[] = self::seal($changedReference);

        $changedTarget = $f['admission'];
        $changedTarget['activation_target']['binding_id'] = 'substitute-binding';
        $cases[] = self::seal($changedTarget);

        $consumed = $f['admission'];
        $consumed['activation_authority']['consumed'] = true;
        $cases[] = self::seal($consumed);

        $changedRoot = $f['admission'];
        $changedRoot['replay_contention_root']['authority_id'] = 'substitute-authority';
        $cases[] = self::seal($changedRoot);

        $secretShaped = $f['admission'];
        $secretShaped['activation_target']['credential_secret'] = 'forbidden';
        $cases[] = self::seal($secretShaped);

        foreach ($cases as $case) {
            try {
                $validator->assertResolutionAdmission(
                    $case, $f['production'], $f['decision'], $f['attestation'],
                    $f['assurance'], $f['boundary'], $f['at'],
                );
                self::fail('Invalid resolution admission accepted.');
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('PRA2', $exception->getMessage());
            }
        }
    }

    public function testExpiryRevocationAndChangedInputEvidenceFailClosed(): void
    {
        $f = $this->fixtures();
        $validator = new ProviderExecutorPrincipalActivationCanonicalContractValidator();

        foreach (['expired', 'revoked'] as $case) {
            $decision = $f['decision'];
            if ('expired' === $case) {
                $decision['validity']['expires_at'] = '2026-08-31T11:59:59+00:00';
                $decision['activation_authority']['expires_at'] = '2026-08-31T11:59:59+00:00';
            } else {
                $decision['validity']['revocation_reference'] = self::referenceRecord('revocation-1');
            }
            $decision = self::seal($decision);
            $admission = $this->admission($f['production'], $decision, $f['attestation'], $f['assurance'], $f['boundary']);
            try {
                $validator->assertResolutionAdmission(
                    $admission, $f['production'], $decision, $f['attestation'],
                    $f['assurance'], $f['boundary'], $f['at'],
                );
                self::fail('Invalid authority validity accepted.');
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('PRA2', $exception->getMessage());
            }
        }

        $input = $f['input'];
        $input['production_decision']['digest'] = str_repeat('e', 64);
        $input = self::seal($input);
        $this->expectExceptionMessage('PRA210_ACTIVATION_INPUT_INVALID');
        $validator->assertActivationInput(
            $input, $f['admission'], $f['production'], $f['decision'],
            $f['attestation'], $f['assurance'], $f['boundary'], $f['at'],
        );
    }

    public function testSameIdentityChangedEvidenceIsImmutableContention(): void
    {
        $f = $this->fixtures();
        $store = new ProviderExecutorPrincipalActivationCanonicalFixtureStore($this->root);
        $store->putResolutionAdmission(
            $f['admission'], $f['production'], $f['decision'], $f['attestation'],
            $f['assurance'], $f['boundary'], $f['at'],
        );

        $contender = $f['admission'];
        $contender['admitted_at'] = '2026-08-31T12:00:01+00:00';
        $contender = self::seal($contender);

        $this->expectExceptionMessage('PST111_IMMUTABLE_RECORD_CONFLICT');
        $store->putResolutionAdmission(
            $contender, $f['production'], $f['decision'], $f['attestation'],
            $f['assurance'], $f['boundary'], $f['at'],
        );
    }

    public function testOfflineBoundaryContainsNoResolverProducerConsumerOrProviderPath(): void
    {
        $root = dirname(__DIR__, 3);
        $sources = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationCanonicalContractValidator.php',
        );
        $sources .= (string) file_get_contents(
            $root.'/src/Imperium/Runtime/LaCortine/ProviderExecutorPrincipalActivationCanonicalFixtureStore.php',
        );

        foreach ([
            'CredentialCapability',
            'EnvironmentCredentialBroker',
            'AgentMailEmailTransport',
            'ProviderExecutorPrincipalActivationService',
            'AuthorityConsumptionStore',
            'public function resolve',
            'public function issue',
            'public function consume',
            'public function activate',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $sources);
        }
    }

    public function testDocumentationAuthorizesReadOnlyAggregateReconstructionNext(): void
    {
        $doc = $this->document('docs/provider-effect-principal-binding-activation-resumption-batch-2-validation.md');
        $handoff = $this->document('docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-2-complete.md');

        foreach ([
            'RESUMPTION_BATCH_2_PURE_VALIDATORS_AND_SEGREGATED_IMMUTABLE_FIXTURE_STORES_COMPLETE',
            'caller-supplied offline fixtures',
            'same-root contention',
            'secret exclusion',
            'no live custody',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }
        foreach ([
            'Only Provider Effect Principal and Binding Activation Resumption Batch 3',
            'read-only aggregate reconstruction',
            'exact replay',
            'changed-evidence conflict',
            'may not issue or consume authority',
            'Iron Gate',
            'Lazaretto',
            'approximately four batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function fixtures(): array
    {
        $at = new \DateTimeImmutable('2026-08-31T12:00:00+00:00');
        $attestation = self::artifact('principal_attestation_id', 'principal-attestation-1', 'imperium.provider-executor-principal/v1');
        $assurance = self::artifact('admission_id', 'provider-assurance-admission-1', 'imperium.provider-assurance-admission/v1');
        $boundary = self::artifact('boundary_id', 'provider-execution-boundary-1', 'imperium.provider-execution-boundary/v1');
        $decision = self::seal([
            'schema' => Decision::SCHEMA,
            'decision_id' => 'provider-principal-activation-decision-1',
            'instance_id' => 'imperium-test',
            'source_authority' => self::referenceRecord('issuance-authorization-1'),
            'actor' => [
                'principal_id' => 'provider-executor-principal-1',
                'office' => 'la-cortine',
                'seat' => 'provider-executor',
                'binding_id' => 'provider-binding-1',
                'generation' => 2,
            ],
            'principal_attestation' => self::reference($attestation, 'principal_attestation_id'),
            'provider_assurance_admission' => self::reference($assurance, 'admission_id'),
            'scope' => [
                'provider_id' => 'agentmail',
                'operation' => 'email.send',
                'execution_boundary_id' => 'provider-execution-boundary-1',
                'principal_id' => 'provider-executor-principal-1',
                'principal_generation' => 2,
                'process_boundary_id' => 'php-process-1',
                'same_process_execution_required' => true,
            ],
            'disposition' => 'AUTHORIZED',
            'rationale' => 'Authorize only the exact attested inactive principal generation.',
            'limitations' => ['no_provider_effect' => true],
            'activation_authority' => [
                'authority_id' => 'principal-activation-authority-1',
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'issuer_service' => 'offline-fixture',
                'permitted_transition' => Decision::PERMITTED_TRANSITION,
                'target_attestation_digest' => $attestation['record_digest'],
                'expires_at' => '2026-08-31T12:10:00+00:00',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'validity' => [
                'effective_at' => '2026-08-31T11:59:00+00:00',
                'expires_at' => '2026-08-31T12:10:00+00:00',
                'revocation_reference' => null,
            ],
            'decided_at' => '2026-08-31T11:59:00+00:00',
            'external_action_performed' => false,
            'sealed' => true,
        ]);
        $production = self::seal([
            'schema' => Production::SCHEMA,
            'production_id' => 'provenance-production-1',
            'instance_id' => 'imperium-test',
            'eligible_aggregate' => self::referenceRecord('eligible-aggregate-1'),
            'pending_successor_principal' => self::referenceRecord('successor-principal-2'),
            'applied_lifecycle_disposition' => self::referenceRecord('lifecycle-disposition-1'),
            'effective_principal_status' => 'PENDING_ACTIVATION',
            'consumed_issuance_authorization' => self::referenceRecord('issuance-authorization-1'),
            'activation_decision' => self::reference($decision, 'decision_id'),
            'combined_winner' => self::referenceRecord('production-winner-1'),
            'produced_at' => '2026-08-31T11:59:30+00:00',
            'provider_executor_principal_activated' => false,
            'provider_binding_activated' => false,
            'activation_authority_consumed' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $admission = $this->admission($production, $decision, $attestation, $assurance, $boundary);
        $input = self::seal([
            'schema' => Input::SCHEMA,
            'input_id' => 'canonical-activation-input-1',
            'instance_id' => 'imperium-test',
            'resolution_admission' => self::reference($admission, 'resolution_admission_id'),
            'provenance_production' => $admission['provenance_production'],
            'production_decision' => $admission['production_decision'],
            'principal_attestation' => $admission['principal_attestation'],
            'provider_assurance_admission' => $admission['provider_assurance_admission'],
            'execution_boundary' => $admission['execution_boundary'],
            'activation_target' => $admission['activation_target'],
            'activation_authority' => $admission['activation_authority'],
            'replay_contention_root' => $admission['replay_contention_root'],
            'exact_replay_only' => true,
            'changed_evidence_conflicts' => true,
            'sealed' => true,
        ]);

        return compact('at', 'attestation', 'assurance', 'boundary', 'decision', 'production', 'admission', 'input');
    }

    private function admission(
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
    ): array {
        return self::seal([
            'schema' => Admission::SCHEMA,
            'resolution_admission_id' => 'canonical-resolution-admission-1',
            'instance_id' => 'imperium-test',
            'provenance_production' => self::reference($production, 'production_id'),
            'production_decision' => self::reference($decision, 'decision_id'),
            'principal_attestation' => self::reference($attestation, 'principal_attestation_id'),
            'provider_assurance_admission' => self::reference($assurance, 'admission_id'),
            'execution_boundary' => self::reference($boundary, 'boundary_id'),
            'activation_target' => [
                'principal_id' => 'provider-executor-principal-1',
                'binding_id' => 'provider-binding-1',
                'generation' => 2,
                'process_boundary_id' => 'php-process-1',
                'provider_id' => 'agentmail',
                'operation' => 'email.send',
            ],
            'activation_authority' => [
                'authority_id' => 'principal-activation-authority-1',
                'decision_digest' => $decision['record_digest'],
                'target_attestation_digest' => $attestation['record_digest'],
                'effective_at' => '2026-08-31T11:59:00+00:00',
                'expires_at' => '2026-08-31T12:10:00+00:00',
                'revocation_reference' => null,
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'replay_contention_root' => [
                'root_id' => 'canonical-activation-root-1',
                'instance_id' => 'imperium-test',
                'principal_id' => 'provider-executor-principal-1',
                'principal_generation' => 2,
                'process_boundary_id' => 'php-process-1',
                'production_id' => 'provenance-production-1',
                'decision_id' => 'provider-principal-activation-decision-1',
                'authority_id' => 'principal-activation-authority-1',
            ],
            'admitted_at' => '2026-08-31T12:00:00+00:00',
            'exact_replay_only' => true,
            'changed_evidence_conflicts' => true,
            'resolution_required' => false,
            'activation_performed' => false,
            'authority_consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
    }

    private static function artifact(string $idField, string $id, string $schema): array
    {
        return self::seal([
            'schema' => $schema,
            $idField => $id,
            'instance_id' => 'imperium-test',
            'sealed' => true,
        ]);
    }

    private static function referenceRecord(string $id): array
    {
        return ['id' => $id, 'digest' => str_repeat('a', 64), 'schema' => 'imperium.test.reference/v1'];
    }

    private static function reference(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
