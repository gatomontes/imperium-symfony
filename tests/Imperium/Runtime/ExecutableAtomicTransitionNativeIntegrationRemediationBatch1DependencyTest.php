<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalConstitutionAuthorityContract as Constitution;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalProvenanceFixtureStore;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract as Principal;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as Admission;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

/** Existing pure validators only; no fixture writes, live identity or authority use. */
final class ExecutableAtomicTransitionNativeIntegrationRemediationBatch1DependencyTest extends TestCase
{
    private const string DOCUMENT = 'docs/executable-atomic-transition-native-integration-remediation-implementation-v1.md';
    private const string HANDOFF = 'docs/handoffs/executable-atomic-transition-native-integration-remediation-batch-1-blocked.md';
    private const string MARKER = 'NATIVE_INTEGRATION_BATCH_1_BLOCKED_ROOT_TRUST_POLICY_REQUIRED';

    public function testInitialConstitutionRejectsEvenResealedScopeWidening(): void
    {
        $record = $this->initialAuthority();
        $validator = new ImperatorPrincipalProvenanceFixtureStore(dirname(__DIR__, 3));
        $validator->assertConstitutionAuthority($record);
        $record['scope']['provider_binding_successor_atomic_live_transition_authority'] = true;
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $this->expectExceptionMessage('PPV100_CONSTITUTION_AUTHORITY_INVALID');
        $validator->assertConstitutionAuthority($record);
    }

    public function testCallerCannotRelabelInitialAuthorityAsExistingScopeGrant(): void
    {
        $record = $this->initialAuthority();
        $record['route'] = 'EXISTING_INSTANCE_TRANSITION_SCOPE_SUCCESSOR';
        $record['permitted_transition'] = 'DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION';
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $this->expectExceptionMessage('PPV100_CONSTITUTION_AUTHORITY_INVALID');
        (new ImperatorPrincipalProvenanceFixtureStore(dirname(__DIR__, 3)))->assertConstitutionAuthority($record);
    }

    public function testCurrentContractsDoNotClaimTheMissingCompetenceOrAdmission(): void
    {
        self::assertSame(['FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'EXISTING_INSTANCE_REMEDIATION'], Constitution::ROUTES);
        self::assertSame(['provider_executor_principal_activation_decision_authority'], Principal::ADDED_AUTHORITY_SCOPE_FIELDS);
        self::assertFalse(Principal::NON_AUTHORITIES['self_widens_scope']);
        self::assertSame('NOT_IMPLEMENTED', Admission::STATUS);
        self::assertFalse(Admission::INVARIANTS['execution_admitted']);
        self::assertFalse(Admission::INVARIANTS['effect_start_permitted']);
    }

    public function testHandoffDoesNotTurnTheDependencyInvestigationIntoCompletion(): void
    {
        foreach ([self::DOCUMENT, self::HANDOFF, 'docs/delegate-mission-flow.md',
            'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $path) {
            self::assertStringContainsString(self::MARKER, $this->read($path), $path);
        }
        $document = $this->read(self::DOCUMENT);
        foreach (['seven unfinished planned stages', 'Batches 2 through 7 have not started',
            'senior-symfony-backend-engineer', 'trust-anchor ownership',
            'operator-provisioned public signing identity', 'Key rotation',
            'No runtime or service configuration change remains', 'BOUND_INACTIVE',
            'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED', 'fixture import',
            'clean locally merged Batch 6 main'] as $fact) {
            self::assertStringContainsString($fact, $document, $fact);
        }
        preg_match_all('/^\| [^|]+ \| ([A-Z_]+) \|/m', $document, $classes);
        self::assertCount(7, $classes[1]);
        foreach ($classes[1] as $class) {
            self::assertContains($class, ['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY']);
        }
        preg_match_all('/^- `((?:src|docs|tests|\.github)\/[^`]+)`/m', $document, $sources);
        foreach ($sources[1] as $source) {
            self::assertFileExists(dirname(__DIR__, 3).'/'.$source);
        }
    }

    private function initialAuthority(): array
    {
        $record = ['schema' => Constitution::SCHEMA,
            'authority_id' => 'dependency-only-constitution', 'instance_id' => 'imperium-test',
            'route' => 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT',
            'operator_root' => ['operator_id' => 'operator-test', 'source_identity_digest' => str_repeat('1', 64),
                'decision_id' => 'decision-test', 'decision_digest' => str_repeat('2', 64)],
            'operationalization' => ['id' => 'pending-test', 'digest' => str_repeat('3', 64),
                'schema' => 'imperium.operator-root-operationalization-pending/v1'],
            'imperator_identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('1', 64),
                'imperator_subject_id' => 'subject-test', 'imperator_subject_digest' => str_repeat('4', 64)],
            'permitted_transition' => 'CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL',
            'target_principal' => ['principal_id' => 'principal-test', 'binding_id' => 'binding-test', 'generation' => 1],
            'scope' => ['provider_binding_activation_authority' => true, 'outbound_email_authority' => false,
                'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => false],
            'authority_single_use' => true, 'authority_exercisable' => true,
            'issued_at' => '2026-09-02T00:00:00+00:00', 'expires_at' => '2026-09-02T00:10:00+00:00',
            'consumed' => false, 'continuing_authority' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function read(string $path): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/'.$path);
    }
}
