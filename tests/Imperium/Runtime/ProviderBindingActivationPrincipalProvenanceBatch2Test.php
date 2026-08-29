<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalConstitutionAuthorityContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleDispositionContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalProvenanceFixtureStore;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationPrincipalProvenanceBatch2Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-principal-provenance-batch2-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->root);
    }

    public function testThreeOfflineFixturesValidatePersistAndReplayExactly(): void
    {
        $store = new ImperatorPrincipalProvenanceFixtureStore($this->root);
        $authority = $this->authority();
        $principal = $this->principal();
        $disposition = $this->disposition();
        self::assertSame($authority, $store->putConstitutionAuthority($authority));
        self::assertSame($authority, $store->putConstitutionAuthority($authority));
        self::assertSame($principal, $store->putPrincipalVersion($principal));
        self::assertSame($disposition, $store->putLifecycleDisposition($disposition));
        self::assertFileExists($this->root.'/'.ImperatorPrincipalProvenanceFixtureStore::CONSTITUTION_AUTHORITIES.'/'.$authority['authority_id'].'.json');
        self::assertFileExists($this->root.'/'.ImperatorPrincipalProvenanceFixtureStore::PRINCIPAL_VERSIONS.'/'.$principal['principal_version_id'].'.json');
        self::assertFileExists($this->root.'/'.ImperatorPrincipalProvenanceFixtureStore::LIFECYCLE_DISPOSITIONS.'/'.$disposition['disposition_id'].'.json');
    }

    public function testRouteScopeSecretAndLifecycleViolationsFailClosed(): void
    {
        $store = new ImperatorPrincipalProvenanceFixtureStore($this->root);
        foreach ([
            function (): array { $r = $this->authority(); $r['permitted_transition'] = 'REMEDIATE_MISSING_IMPERATOR_PRINCIPAL'; return self::seal($r); },
            function (): array { $r = $this->authority(); $r['scope']['credential_authority'] = true; return self::seal($r); },
            function (): array { $r = $this->principal(); $r['credential_secret_persisted'] = true; return self::seal($r); },
            function (): array { $r = $this->disposition(); $r['caller_authority_issuance_permitted_after_effective_at'] = true; return self::seal($r); },
        ] as $index => $invalid) {
            try {
                $record = $invalid();
                2 > $index ? $store->putConstitutionAuthority($record) : (2 === $index ? $store->putPrincipalVersion($record) : $store->putLifecycleDisposition($record));
                self::fail('Invalid fixture accepted at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('PPV', $exception->getMessage());
            }
        }
    }

    public function testDocumentationAuthorizesOfflineInterruptionEvidenceOnly(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-batch-2-complete.md');
        foreach (['Only Batch 3 is authorized', 'offline interruption demonstrations', 'pre-commit', 'post-authority-consumption/pre-commit', 'post-commit', 'exact replay', 'conflict', 'expiry', 'contention', 'read-only reconstruction', 'may not issue live', 'current-state index', 'reconsider corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function authority(): array
    {
        return self::seal(['schema' => ImperatorPrincipalConstitutionAuthorityContract::SCHEMA, 'authority_id' => 'imperator-principal-constitution-authority-test', 'instance_id' => 'imperium-test', 'route' => 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'operator_root' => ['operator_id' => 'operator-test', 'source_identity_digest' => str_repeat('1', 64), 'decision_id' => 'decision-test', 'decision_digest' => str_repeat('2', 64)], 'operationalization' => $this->reference('operationalization-test', '3'), 'imperator_identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('1', 64), 'imperator_subject_id' => 'imperator-subject-test', 'imperator_subject_digest' => str_repeat('4', 64)], 'permitted_transition' => 'CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL', 'target_principal' => ['principal_id' => 'imperator-principal-test', 'binding_id' => 'imperator-binding-test', 'generation' => 1], 'scope' => $this->scope(), 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => '2026-08-29T23:00:00+00:00', 'expires_at' => '2026-08-29T23:10:00+00:00', 'consumed' => false, 'continuing_authority' => false, 'sealed' => true]);
    }

    private function principal(): array
    {
        return self::seal(['schema' => ImperatorRuntimePrincipalVersionContract::SCHEMA, 'principal_version_id' => 'imperator-principal-version-test-1', 'principal_id' => 'imperator-principal-test', 'instance_id' => 'imperium-test', 'binding_id' => 'imperator-binding-test', 'principal_generation' => 1, 'constitution_route' => 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'source_constitution_authority' => $this->reference('imperator-principal-constitution-authority-test', '5'), 'source_operator_root' => $this->reference('operator-root-test', '6'), 'identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('1', 64), 'imperator_subject_id' => 'imperator-subject-test', 'imperator_subject_digest' => str_repeat('4', 64)], 'authority_scope' => $this->scope(), 'lifecycle' => ['constituted_at' => '2026-08-29T23:00:00+00:00', 'effective_at' => '2026-08-29T23:01:00+00:00', 'expires_at' => '2026-09-29T23:01:00+00:00', 'prior_version' => null, 'superseding_version' => null, 'current_disposition' => null], 'status' => 'ACTIVE', 'credential_reference_persisted' => false, 'credential_secret_persisted' => false, 'serialized_capability_persisted' => false, 'sealed' => true]);
    }

    private function disposition(): array
    {
        return self::seal(['schema' => ImperatorPrincipalLifecycleDispositionContract::SCHEMA, 'disposition_id' => 'imperator-principal-lifecycle-disposition-test', 'instance_id' => 'imperium-test', 'operator_root' => $this->reference('operator-root-test', '6'), 'source_principal_version' => $this->reference('imperator-principal-version-test-1', '7'), 'source_status' => 'ACTIVE', 'disposition' => 'SUSPEND', 'rationale' => 'Offline lifecycle validation fixture.', 'effective_at' => '2026-08-29T23:05:00+00:00', 'successor_principal_version' => null, 'authority_scope_changed' => false, 'historical_attribution_preserved' => true, 'caller_authority_issuance_permitted_after_effective_at' => false, 'external_action_performed' => false, 'sealed' => true]);
    }

    private function reference(string $id, string $digit): array { return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => 'offline-reference/v1']; }
    private function scope(): array { return ['provider_binding_activation_authority' => true, 'outbound_email_authority' => false, 'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => false]; }
    private static function seal(array $record): array { unset($record['record_digest']); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
}
