<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleDispositionContract as Lifecycle;
use App\Imperium\Runtime\Imperator\ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract as Grant;
use App\Imperium\Runtime\Imperator\ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract as Successor;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract as Principal;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceRemediationFixtureStore;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract as Issuance;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch2Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-pad-batch2-'.bin2hex(random_bytes(6));
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

    public function testExactCallerSuppliedFixturesStoreIdempotently(): void
    {
        $fixtures = $this->fixtures();
        $store = new PrincipalActivationDecisionAuthorityProvenanceRemediationFixtureStore($this->root);

        self::assertSame(
            $store->putScopeGrant($fixtures['grant'], $fixtures['source'], $fixtures['at']),
            $store->putScopeGrant($fixtures['grant'], $fixtures['source'], $fixtures['at']),
        );
        self::assertSame(
            $store->putScopeSuccessor($fixtures['successor'], $fixtures['grant']),
            $store->putScopeSuccessor($fixtures['successor'], $fixtures['grant']),
        );
        self::assertSame(
            $store->putIssuanceAuthorization(
                $fixtures['authorization'],
                $fixtures['successor'],
                $fixtures['activation'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['at'],
            ),
            $store->putIssuanceAuthorization(
                $fixtures['authorization'],
                $fixtures['successor'],
                $fixtures['activation'],
                $fixtures['attestation'],
                $fixtures['assurance'],
                $fixtures['boundary'],
                $fixtures['at'],
            ),
        );
    }

    public function testGenerationScopeExpiryRevocationAndLineageMismatchFailClosed(): void
    {
        $fixtures = $this->fixtures();
        $store = new PrincipalActivationDecisionAuthorityProvenanceRemediationFixtureStore($this->root);

        foreach (['generation', 'scope', 'expiry', 'revocation'] as $case) {
            $bad = $fixtures['grant'];
            if ('generation' === $case) {
                $bad['successor_principal']['generation'] = 3;
            }
            if ('scope' === $case) {
                $bad['preserved_scope']['provider_execution_authority'] = true;
            }
            if ('expiry' === $case) {
                $bad['expires_at'] = '2026-08-30T12:04:00+00:00';
            }
            if ('revocation' === $case) {
                $bad['revocation'] = self::referenceRecord('revocation-1');
            }
            $bad = self::seal($bad);
            try {
                $store->putScopeGrant($bad, $fixtures['source'], $fixtures['at']);
                self::fail('Accepted invalid '.$case.' fixture.');
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('PAD2', $exception->getMessage());
            }
        }

        $bad = $fixtures['authorization'];
        $bad['principal_attestation']['digest'] = str_repeat('f', 64);
        $bad = self::seal($bad);
        $this->expectExceptionMessage('PAD220_ISSUANCE_AUTHORIZATION_INVALID');
        $store->putIssuanceAuthorization(
            $bad,
            $fixtures['successor'],
            $fixtures['activation'],
            $fixtures['attestation'],
            $fixtures['assurance'],
            $fixtures['boundary'],
            $fixtures['at'],
        );
    }

    public function testFixtureBoundaryContainsNoProducerConsumerOrProviderPath(): void
    {
        $root = dirname(__DIR__, 3);
        $sources = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceRemediationContractValidator.php',
        );
        $sources .= (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceRemediationFixtureStore.php',
        );

        foreach ([
            'AuthorityConsumptionStore',
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'ProviderExecutorPrincipalActivationService',
            'public function issue',
            'public function consume',
            'public function activate',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $sources);
        }
    }

    public function testDocumentationAuthorizesOfflineInterruptionProofOnly(): void
    {
        $doc = $this->document('docs/principal-activation-decision-authority-provenance-remediation-validation.md');
        $handoff = $this->document('docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-2-complete.md');

        foreach ([
            'BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE',
            'caller-supplied offline fixtures',
            'generation continuity',
            'one-field scope delta',
            'effective activation disposition',
            'no live registry',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }
        foreach ([
            'Only remediation Batch 3 may next be considered',
            'offline interruption',
            'exact replay',
            'changed-evidence conflict',
            'may not issue or consume authority',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'approximately five batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function fixtures(): array
    {
        $at = new \DateTimeImmutable('2026-08-30T12:05:00+00:00');
        $source = self::seal([
            'schema' => Principal::SCHEMA,
            'principal_version_id' => 'imperator-principal-decision-1',
            'principal_id' => 'imperator-principal-decision',
            'instance_id' => 'imperium-test',
            'binding_id' => 'imperator-binding-decision',
            'principal_generation' => 1,
            'constitution_route' => 'EXISTING_INSTANCE_REMEDIATION',
            'source_constitution_authority' => self::referenceRecord('constitution-1'),
            'source_operator_root' => self::referenceRecord('operator-root-1'),
            'identity' => [
                'operator_id' => 'operator-test',
                'operator_identity_digest' => str_repeat('1', 64),
                'imperator_subject_id' => 'imperator-subject',
                'imperator_subject_digest' => str_repeat('2', 64),
            ],
            'authority_scope' => [
                'provider_binding_activation_authority' => true,
                'outbound_email_authority' => false,
                'credential_authority' => false,
                'provider_execution_authority' => false,
                'corridor_disposition_authority' => false,
            ],
            'lifecycle' => [
                'constituted_at' => '2026-08-30T11:00:00+00:00',
                'effective_at' => '2026-08-30T11:01:00+00:00',
                'expires_at' => '2026-08-31T11:01:00+00:00',
                'prior_version' => null,
                'superseding_version' => null,
                'current_disposition' => null,
            ],
            'status' => 'ACTIVE',
            'credential_reference_persisted' => false,
            'credential_secret_persisted' => false,
            'serialized_capability_persisted' => false,
            'sealed' => true,
        ]);
        $sourceReference = self::principalReference($source, 'principal_version_id', 1);
        $successorReference = [
            'id' => 'imperator-principal-decision-2',
            'digest' => str_repeat('3', 64),
            'schema' => 'imperium.imperator-runtime-principal/v3',
            'generation' => 2,
        ];
        $grant = self::seal([
            'schema' => Grant::SCHEMA,
            'grant_id' => 'decision-scope-grant-1',
            'instance_id' => 'imperium-test',
            'operator_root' => [
                'operator_id' => 'operator-test',
                'source_identity_digest' => str_repeat('1', 64),
                'decision_id' => 'decision-scope-grant-decision-1',
                'decision_digest' => str_repeat('4', 64),
            ],
            'source_principal' => $sourceReference,
            'successor_principal' => $successorReference,
            'scope_delta' => ['provider_executor_principal_activation_decision_authority' => true],
            'preserved_scope' => $source['authority_scope'],
            'permitted_transition' => Grant::PERMITTED_TRANSITION,
            'rationale' => 'Authorize only the exact decision authority scope successor.',
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'issuance_winner_required' => true,
            'consumption_winner_required' => true,
            'issued_at' => '2026-08-30T12:00:00+00:00',
            'expires_at' => '2026-08-30T12:10:00+00:00',
            'revocation' => null,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $successor = self::seal([
            'schema' => Successor::SCHEMA,
            'successor_transition_id' => 'decision-scope-successor-1',
            'instance_id' => 'imperium-test',
            'scope_grant' => self::reference($grant, 'grant_id'),
            'source_principal' => $sourceReference,
            'successor_principal' => $successorReference,
            'source_generation' => 1,
            'successor_generation' => 2,
            'identity_preserved' => true,
            'binding_preserved' => true,
            'scope_delta' => $grant['scope_delta'],
            'preserved_scope' => $grant['preserved_scope'],
            'initial_status' => 'PENDING_ACTIVATION',
            'activation_required' => true,
            'separate_activation_authority_required' => true,
            'transition_winner_required' => true,
            'committed_at' => '2026-08-30T12:03:00+00:00',
            'grant_consumed' => true,
            'source_principal_mutated' => false,
            'source_principal_superseded' => false,
            'decision_issuance_authorization_created' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $activation = self::seal([
            'schema' => Lifecycle::SCHEMA,
            'disposition_id' => 'decision-scope-successor-activation-1',
            'instance_id' => 'imperium-test',
            'operator_root' => self::referenceRecord('operator-root-1'),
            'source_principal_version' => [
                'id' => $successorReference['id'],
                'digest' => $successorReference['digest'],
                'schema' => $successorReference['schema'],
            ],
            'source_status' => 'PENDING_ACTIVATION',
            'disposition' => 'ACTIVATE',
            'rationale' => 'Separately activate the exact successor generation.',
            'effective_at' => '2026-08-30T12:04:00+00:00',
            'successor_principal_version' => null,
            'authority_scope_changed' => false,
            'historical_attribution_preserved' => true,
            'caller_authority_issuance_permitted_after_effective_at' => true,
            'external_action_performed' => false,
            'sealed' => true,
        ]);
        $attestation = self::artifact('principal_attestation_id', 'principal-attestation-1', 'imperium.provider-executor-principal/v1');
        $assurance = self::artifact('admission_id', 'provider-assurance-admission-1', 'imperium.provider-assurance-admission/v1');
        $boundary = self::artifact('boundary_id', 'provider-execution-boundary-1', 'imperium.provider-execution-boundary/v1');
        $authorization = self::seal([
            'schema' => Issuance::SCHEMA,
            'issuance_authorization_id' => 'decision-issuance-authorization-1',
            'instance_id' => 'imperium-test',
            'issuer_principal' => $successorReference,
            'scope_successor' => self::reference($successor, 'successor_transition_id'),
            'activation_disposition' => self::reference($activation, 'disposition_id'),
            'principal_attestation' => self::reference($attestation, 'principal_attestation_id'),
            'provider_assurance_admission' => self::reference($assurance, 'admission_id'),
            'execution_boundary' => self::reference($boundary, 'boundary_id'),
            'decision_id' => 'provider-principal-activation-decision-1',
            'activation_authority_id' => 'provider-principal-activation-authority-1',
            'permitted_transition' => Issuance::PERMITTED_TRANSITION,
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'issuance_winner_required' => true,
            'consumption_winner_required' => true,
            'issued_at' => '2026-08-30T12:04:00+00:00',
            'expires_at' => '2026-08-30T12:10:00+00:00',
            'revocation' => null,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        return compact('at', 'source', 'grant', 'successor', 'activation', 'attestation', 'assurance', 'boundary', 'authorization');
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

    private static function principalReference(array $record, string $idField, int $generation): array
    {
        return self::reference($record, $idField) + ['generation' => $generation];
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
