<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract as Transition;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract as PrincipalV2;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract as PrincipalV3;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract as Decision;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract as Authorization;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract as Envelope;
use PHPUnit\Framework\TestCase;

class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5BTest extends TestCase
{
    protected string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-pad-batch5b-'.bin2hex(random_bytes(6));
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
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testExactCallerSuppliedFixturesStoreIdempotentlyAndSeparately(): void
    {
        $fixtures = $this->fixtures();
        $store = new PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore($this->root);

        $principal = $store->putSuccessorPrincipal(
            $fixtures['principal'],
            $fixtures['source'],
            $fixtures['transition'],
        );
        self::assertSame(
            $principal,
            $store->putSuccessorPrincipal($fixtures['principal'], $fixtures['source'], $fixtures['transition']),
        );

        $envelope = $store->putProductionEnvelope(
            $fixtures['envelope'],
            $fixtures['authorization'],
            $fixtures['principal'],
        );
        self::assertSame(
            $envelope,
            $store->putProductionEnvelope($fixtures['envelope'], $fixtures['authorization'], $fixtures['principal']),
        );
        self::assertFileExists(
            $this->root.'/'.PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore::SUCCESSOR_PRINCIPALS.'/imperator-v3-2.json',
        );
        self::assertFileExists(
            $this->root.'/'.PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore::PRODUCTION_ENVELOPES.'/decision-envelope-1.json',
        );
    }

    public function testChangedIdentityScopeAndAuthorizationLineageFailClosed(): void
    {
        $fixtures = $this->fixtures();
        $store = new PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore($this->root);

        $identity = $fixtures['principal'];
        $identity['identity']['operator_id'] = 'other-operator';
        $identity = self::seal($identity);

        $scope = $fixtures['principal'];
        $scope['authority_scope']['provider_execution_authority'] = true;
        $scope = self::seal($scope);

        foreach ([$identity, $scope] as $invalid) {
            try {
                $store->putSuccessorPrincipal($invalid, $fixtures['source'], $fixtures['transition']);
                self::fail('Accepted invalid successor principal.');
            } catch (\RuntimeException $exception) {
                self::assertSame('PAD5B00_SUCCESSOR_PRINCIPAL_INVALID', $exception->getMessage());
            }
        }

        $envelope = $fixtures['envelope'];
        $envelope['decision_id'] = 'different-decision';
        $envelope = self::seal($envelope);

        $this->expectExceptionMessage('PAD5B10_PRODUCTION_ENVELOPE_INVALID');
        $store->putProductionEnvelope($envelope, $fixtures['authorization'], $fixtures['principal']);
    }

    public function testFixtureBoundaryContainsNoProductionConsumptionOrProviderPath(): void
    {
        $root = dirname(__DIR__, 3);
        $source = (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator.php',
        );
        $source .= (string) file_get_contents(
            $root.'/src/Imperium/Runtime/Imperator/PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore.php',
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
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesProductionAsASeparateBatchOnly(): void
    {
        $doc = $this->document('docs/principal-activation-decision-authority-provenance-remediation-batch-5b-validation.md');
        $handoff = $this->document('docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-5b-complete.md');

        foreach ([
            'BATCH_5B_PURE_VALIDATORS_AND_SEGREGATED_IMMUTABLE_OFFLINE_FIXTURE_STORES_COMPLETE',
            'caller-supplied offline fixture',
            'identity and binding preservation',
            'single added decision-authority scope',
            'issuance-authorization lineage',
        ] as $claim) {
            self::assertNotFalse(stripos($doc, $claim), $claim);
        }
        foreach ([
            'Only remediation Batch 5C production may next be considered',
            'ELIGIBLE read-only aggregate result',
            'consume exactly one decision-issuance authorization',
            'may not activate the provider executor principal or provider binding',
            'Iron Gate and Lazaretto remain closed',
            'approximately three batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    protected function fixtures(): array
    {
        $identity = [
            'operator_id' => 'operator-test',
            'operator_identity_digest' => str_repeat('1', 64),
            'imperator_subject_id' => 'imperator-subject',
            'imperator_subject_digest' => str_repeat('2', 64),
        ];
        $scopeV2 = [
            'provider_binding_activation_authority' => true,
            'outbound_email_authority' => false,
            'credential_authority' => false,
            'provider_execution_authority' => false,
            'corridor_disposition_authority' => false,
        ];
        $source = self::seal([
            'schema' => PrincipalV2::SCHEMA,
            'principal_version_id' => 'imperator-v2-1',
            'principal_id' => 'imperator',
            'instance_id' => 'imperium-test',
            'binding_id' => 'imperator-binding',
            'principal_generation' => 1,
            'constitution_route' => 'EXISTING_INSTANCE_REMEDIATION',
            'source_constitution_authority' => self::referenceRecord('constitution'),
            'source_operator_root' => self::referenceRecord('operator-root'),
            'identity' => $identity,
            'authority_scope' => $scopeV2,
            'lifecycle' => [
                'constituted_at' => '2026-08-30T20:00:00+00:00',
                'effective_at' => '2026-08-30T20:01:00+00:00',
                'expires_at' => '2026-08-31T20:01:00+00:00',
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
        $sourceReference = self::principalReference($source);
        $successorReference = [
            'id' => 'imperator-v3-2',
            'digest' => str_repeat('3', 64),
            'schema' => PrincipalV3::SCHEMA,
            'generation' => 2,
        ];
        $transition = self::seal([
            'schema' => Transition::SCHEMA,
            'successor_transition_id' => 'scope-successor-1',
            'instance_id' => 'imperium-test',
            'scope_grant' => self::referenceRecord('scope-grant'),
            'source_principal' => $sourceReference,
            'successor_principal' => $successorReference,
            'source_generation' => 1,
            'successor_generation' => 2,
            'identity_preserved' => true,
            'binding_preserved' => true,
            'scope_delta' => ['provider_executor_principal_activation_decision_authority' => true],
            'preserved_scope' => $scopeV2,
            'initial_status' => 'PENDING_ACTIVATION',
            'activation_required' => true,
            'separate_activation_authority_required' => true,
            'transition_winner_required' => true,
            'committed_at' => '2026-08-30T20:02:00+00:00',
            'grant_consumed' => true,
            'source_principal_mutated' => false,
            'source_principal_superseded' => false,
            'decision_issuance_authorization_created' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $principal = self::seal([
            'schema' => PrincipalV3::SCHEMA,
            'principal_version_id' => 'imperator-v3-2',
            'principal_id' => 'imperator',
            'instance_id' => 'imperium-test',
            'binding_id' => 'imperator-binding',
            'principal_generation' => 2,
            'constitution_route' => 'EXISTING_INSTANCE_REMEDIATION',
            'source_constitution_authority' => self::referenceRecord('constitution'),
            'source_operator_root' => self::referenceRecord('operator-root'),
            'identity' => $identity,
            'authority_scope' => $scopeV2 + [
                'provider_executor_principal_activation_decision_authority' => true,
            ],
            'lifecycle' => [
                'constituted_at' => '2026-08-30T20:02:00+00:00',
                'effective_at' => null,
                'expires_at' => '2026-08-31T20:01:00+00:00',
                'prior_version' => self::reference($source, 'principal_version_id'),
                'superseding_version' => null,
                'current_disposition' => null,
            ],
            'status' => 'PENDING_ACTIVATION',
            'credential_reference_persisted' => false,
            'credential_secret_persisted' => false,
            'serialized_capability_persisted' => false,
            'sealed' => true,
        ]);
        $successorReference['digest'] = $principal['record_digest'];
        $transition['successor_principal'] = $successorReference;
        $transition = self::seal($transition);

        $authorization = self::seal([
            'schema' => Authorization::SCHEMA,
            'issuance_authorization_id' => 'decision-authorization-1',
            'instance_id' => 'imperium-test',
            'issuer_principal' => $successorReference,
            'scope_successor' => self::reference($transition, 'successor_transition_id'),
            'activation_disposition' => self::referenceRecord('activation-disposition'),
            'principal_attestation' => self::referenceRecord('principal-attestation'),
            'provider_assurance_admission' => self::referenceRecord('assurance-admission'),
            'execution_boundary' => self::referenceRecord('execution-boundary'),
            'decision_id' => 'activation-decision-1',
            'activation_authority_id' => 'activation-authority-1',
            'permitted_transition' => Authorization::PERMITTED_TRANSITION,
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'issuance_winner_required' => true,
            'consumption_winner_required' => true,
            'issued_at' => '2026-08-30T20:03:00+00:00',
            'expires_at' => '2026-08-30T20:13:00+00:00',
            'revocation' => null,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $authorizationReference = self::reference($authorization, 'issuance_authorization_id');
        $envelope = self::seal([
            'schema' => Envelope::SCHEMA,
            'production_envelope_id' => 'decision-envelope-1',
            'instance_id' => 'imperium-test',
            'issuance_authorization' => $authorizationReference,
            'issuer_principal' => $successorReference,
            'source_authority' => $authorizationReference,
            'actor' => [
                'principal_id' => 'imperator',
                'office' => 'imperator',
                'seat' => 'provider-executor-principal-activation-decision',
                'binding_id' => 'imperator-binding',
                'generation' => 2,
            ],
            'principal_attestation' => $authorization['principal_attestation'],
            'provider_assurance_admission' => $authorization['provider_assurance_admission'],
            'execution_boundary' => $authorization['execution_boundary'],
            'scope' => [
                'provider_id' => 'provider-test',
                'operation' => 'activate-provider-executor-principal',
                'execution_boundary_id' => $authorization['execution_boundary']['id'],
                'principal_id' => 'imperator',
                'principal_generation' => 2,
                'process_boundary_id' => 'offline-proof-process',
                'same_process_execution_required' => true,
            ],
            'disposition' => 'AUTHORIZED',
            'rationale' => 'Exact validated decision-production fixture.',
            'limitations' => ['Offline fixture only.', 'No provider effect.'],
            'activation_authority' => [
                'authority_id' => 'activation-authority-1',
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'issuer_service' => 'future-imperator.decision-producer',
                'permitted_transition' => Decision::PERMITTED_TRANSITION,
                'target_attestation_digest' => $authorization['principal_attestation']['digest'],
                'expires_at' => '2026-08-30T20:13:00+00:00',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'validity' => [
                'effective_at' => '2026-08-30T20:04:00+00:00',
                'expires_at' => '2026-08-30T20:13:00+00:00',
                'revocation_reference' => null,
            ],
            'decision_id' => 'activation-decision-1',
            'permitted_transition' => Authorization::PERMITTED_TRANSITION,
            'sealed' => true,
        ]);

        return compact('source', 'transition', 'principal', 'authorization', 'envelope');
    }

    protected static function principalReference(array $record): array
    {
        return self::reference($record, 'principal_version_id') + [
            'generation' => $record['principal_generation'],
        ];
    }

    protected static function referenceRecord(string $id): array
    {
        return [
            'id' => $id,
            'digest' => hash('sha256', $id),
            'schema' => 'imperium.test.reference/v1',
        ];
    }

    protected static function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    protected static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    protected function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
