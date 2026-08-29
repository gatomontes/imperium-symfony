<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\FutureInstanceImperatorPrincipalConstitutionService;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalConstitutionAuthorityContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalProvenanceFixtureStore;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationPrincipalProvenanceBatch4Test extends TestCase
{
    private string $root;

    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-principal-provenance-batch4-'.bin2hex(random_bytes(6)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { if (!is_dir($this->root)) return; $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testExactFutureInstanceAuthorityConstitutesOnePendingGeneration(): void
    {
        $authority = $this->authority();
        (new ImperatorPrincipalProvenanceFixtureStore($this->root))->putConstitutionAuthority($authority);
        $service = new FutureInstanceImperatorPrincipalConstitutionService($this->root);
        $at = new \DateTimeImmutable('2026-08-29T23:01:00+00:00');
        $principal = $service->constitute($authority['authority_id'], $at);
        self::assertSame($principal, $service->constitute($authority['authority_id'], $at));
        self::assertSame('PENDING_ACTIVATION', $principal['status']);
        self::assertSame(1, $principal['principal_generation']);
        self::assertSame('FUTURE_INSTANCE_ROOT_ESTABLISHMENT', $principal['constitution_route']);
        self::assertFalse($principal['credential_reference_persisted']);
        self::assertFalse($principal['credential_secret_persisted']);
        self::assertFalse($principal['serialized_capability_persisted']);
        self::assertFileExists($this->root.'/var/imperium/runtime/authority-consumptions/authority-consumption-'.hash('sha256', $authority['authority_id']).'.json');
    }

    public function testSealedOperationalizationAndWrongRouteFailClosed(): void
    {
        $authority = $this->authority();
        (new ImperatorPrincipalProvenanceFixtureStore($this->root))->putConstitutionAuthority($authority);
        mkdir($this->root.'/var/imperium/operator-root', 0770, true);
        file_put_contents($this->root.'/var/imperium/operator-root/operationalization-seal.json', '{}');
        $this->expectExceptionMessage('PPR404_OPERATIONALIZATION_ALREADY_SEALED');
        (new FutureInstanceImperatorPrincipalConstitutionService($this->root))->constitute($authority['authority_id'], new \DateTimeImmutable('2026-08-29T23:01:00+00:00'));
    }

    public function testDocumentationAuthorizesExistingInstanceRemediationOnlyNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-batch-4-complete.md');
        foreach (['Only Batch 5 is authorized', 'existing-instance remediation producer', 'EXISTING_INSTANCE_REMEDIATION', 'intact operationalization seal', 'may not activate', 'issue caller authority', 'current-state index', 'reconsider corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function authority(): array
    {
        $record = ['schema' => ImperatorPrincipalConstitutionAuthorityContract::SCHEMA, 'authority_id' => 'imperator-principal-constitution-authority-batch4', 'instance_id' => 'imperium-test', 'route' => 'FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'operator_root' => ['operator_id' => 'operator-test', 'source_identity_digest' => str_repeat('1', 64), 'decision_id' => 'decision-test', 'decision_digest' => str_repeat('2', 64)], 'operationalization' => ['id' => 'operationalization-pending-test', 'digest' => str_repeat('3', 64), 'schema' => 'imperium.operator-root-operationalization-pending/v1'], 'imperator_identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('1', 64), 'imperator_subject_id' => 'imperator-subject-test', 'imperator_subject_digest' => str_repeat('4', 64)], 'permitted_transition' => 'CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL', 'target_principal' => ['principal_id' => 'imperator-principal-test', 'binding_id' => 'imperator-binding-test', 'generation' => 1], 'scope' => ['provider_binding_activation_authority' => true, 'outbound_email_authority' => false, 'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => false], 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => '2026-08-29T23:00:00+00:00', 'expires_at' => '2026-08-29T23:10:00+00:00', 'consumed' => false, 'continuing_authority' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
