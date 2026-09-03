<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Documentary regression gates complement, rather than replace, the application proof. */
final class CanonicalConsumerCorrectionBatch4Test extends TestCase
{
    private const string PREFIX = 'executable-atomic-transition-canonical-consumer-integration-correction';
    private const string VERDICT = 'CANONICAL_CONSUMER_INTEGRATION_TERMINAL_AUDIT_ACCEPTED_BOUNDED_PRE_EFFECT';

    public function testTerminalEvidenceIsPinnedToTheSeparatelyMergedCorrection(): void
    {
        $audit = $this->read('docs/'.self::PREFIX.'-terminal-audit-v1.md');
        $ledger = json_decode($this->read('docs/'.self::PREFIX.'-reading-ledger-v3.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('7f1b634a37eb8f9d70058f4b18bcefc34e4ff22e', $ledger['audited_main']);
        self::assertStringContainsString($ledger['audited_main'], $audit);
        self::assertStringContainsString(self::VERDICT, $audit);
        self::assertStringContainsString('acceptance was', $audit);
        self::assertStringContainsString('withheld', $audit);
        self::assertGreaterThanOrEqual(20, count($ledger['sources']));
        $paths = array_column($ledger['sources'], 'path');
        self::assertSame($paths, array_values(array_unique($paths)));
        $successor = json_decode($this->read('docs/native-inspection-snapshot-consistency-terminal-reading-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        $reviewedSuccessors = array_column($successor['sources'], null, 'path');
        foreach ($ledger['sources'] as $source) {
            self::assertSame('FULLY_READ', $source['read_status']);
            $actual = hash('sha256', $this->read($source['path']));
            if ($source['normalized_sha256'] === $actual) { continue; }
            self::assertArrayHasKey($source['path'], $reviewedSuccessors, $source['path'].' changed without a successor review.');
            self::assertSame($source['normalized_sha256'], $reviewedSuccessors[$source['path']]['predecessor_sha256']);
            self::assertSame($actual, $reviewedSuccessors[$source['path']]['normalized_sha256']);
        }
    }

    public function testEveryOriginalFindingAndCorridorHasAFinalDisposition(): void
    {
        $inventory = $this->read('docs/'.self::PREFIX.'-inventory-v2.md');
        foreach (['C' => 31, 'D' => 11, 'A' => 5, 'E' => 10] as $prefix => $count) {
            preg_match_all('/^\| ('.$prefix.'\d{2}) \|.*$/m', $inventory, $rows);
            self::assertCount($count, $rows[0]);
            self::assertCount($count, array_unique($rows[1]));
            foreach ($rows[0] as $row) {
                self::assertMatchesRegularExpression('/\| (EXISTS_CANONICALLY|EXISTS_FRAGMENTED|ABSENT|DEFERRED_BOUNDARY) \|/', $row);
            }
        }
        foreach (['schema substitution', 'Cached results', 'OutboundRequest still has no', 'DEFERRED_BOUNDARY'] as $evidence) {
            self::assertStringContainsString($evidence, $inventory);
        }
        foreach (['docs/delegate-mission-flow.md', 'todo/blackquill-todos.md', 'docs/handoffs/README.md', 'docs/handoffs/'.self::PREFIX.'-campaign-complete.md'] as $path) {
            $document = $this->read($path);
            self::assertStringContainsString(self::VERDICT, $document);
            self::assertStringContainsString('EXECUTABLE_ATOMIC_TRANSITION_CANONICAL_CONSUMER_INTEGRATION_CORRECTION_CAMPAIGN_COMPLETE', $document);
            foreach (['BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
                self::assertStringContainsString($boundary, $document);
            }
        }
    }

    public function testEstablishedConsumerAndCompetingCutsRequireTheirGuards(): void
    {
        $broker = $this->read('src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php');
        self::assertStringContainsString('private NativeBindingReader $bindingReader,', $broker);
        $this->before($broker, '$interpretation = $this->inspectClaim(', '$admissionId =');
        $this->before($broker, '$interpretation = $this->inspectClaim(', '$this->credentials->consume(');
        self::assertStringContainsString('return $this->bindingReader->forClaim(', $broker);
        $command = $this->read('src/Command/AgentMailEmailSendCommand.php');
        self::assertStringContainsString('DeterministicJournalBoundCredentialBroker as JournalConsumer', $command);
        self::assertStringContainsString('$this->consumer->inspectClaim(', $command);
        self::assertStringContainsString('GOVERNED_EMAIL_SEND_EXECUTOR_UNAVAILABLE', $command);
        foreach (['LaCortine/GovernedProviderExecutionAdmissionService.php', 'LaCortine/GovernedProviderExecutionCombinedAdmissionService.php', 'Clavium/GovernedStationaryCredentialResolutionService.php', 'Clavium/GovernedStationaryCredentialResolutionV2Service.php'] as $path) {
            $source = $this->read('src/Imperium/Runtime/'.$path);
            $this->before($source, '->assertLegacyRecord($existing)', '$this->assertExisting($existing,');
            self::assertStringContainsString('return $reader->legacy(', $source);
        }
        $encoder = $this->read('src/Imperium/Runtime/LaCortine/AgentMailProviderRequestEncoder.php');
        $this->before($encoder, '->assertLegacy($binding)', 'return $this->encodeLegacy(');
        self::assertStringContainsString('->hasNativeState()', $encoder);
        $executor = $this->read('src/Imperium/Runtime/LaCortine/DeterministicBoundaryExecutor.php');
        $this->before($executor, 'CCI_EMAIL_REQUEST_HAS_NO_BINDING_ROOT', '$this->ironGate->dispatch(');
        $transport = $this->read('src/Imperium/Runtime/LaCortine/AgentMailEmailTransport.php');
        self::assertStringContainsString('CCI_EMAIL_TRANSPORT_HAS_NO_BINDING_ROOT', $transport);
        self::assertStringNotContainsString('file_get_contents(', $transport);
    }

    public function testProofUsesProductionApplicationAndKeepsReconstructionNonAuthorizing(): void
    {
        $proof = $this->read('tests/Imperium/Runtime/CanonicalConsumerCorrectionBatch3Test.php');
        foreach (['new Application($kernel)', 'new ApplicationTester($application)', '$container->get(DeterministicJournalBoundCredentialBroker::class)->invoke(', 'testSeparateProcessNativePublicationExcludesCompetingLegacyAdmission', 'testProcessInterruptionAfterJournalRemainsUnknownAndDoesNotRestart', 'testAliasedIntactDescriptorCannotChangeTheRootBeingGuarded', 'CCI_BINDING_IDENTITY_MISMATCH', '$cached[\'provider_binding\']'] as $evidence) {
            self::assertStringContainsString($evidence, $proof);
        }
        $kernel = $this->read('tests/Imperium/Runtime/Support/CanonicalConsumerKernel.php');
        self::assertStringContainsString('parent::registerContainerConfiguration($loader)', $kernel);
        self::assertStringNotContainsString('setDefinition(NativeBindingReader::class', $kernel);
        $services = $this->read('config/services.yaml');
        self::assertStringContainsString('App\Imperium\Runtime\ProviderTransition\NativeBindingReader: ~', $services);
        $reconstruction = $this->read('src/Imperium/Runtime/ProviderTransition/NativeReconstructor.php');
        foreach (["'execution_authority' => false", "'retry_authorized' => false", "'provider_effect_started' => false"] as $limit) {
            self::assertStringContainsString($limit, $reconstruction);
        }
    }

    private function before(string $source, string $first, string $second): void
    {
        $left = strpos($source, $first); $right = strpos($source, $second);
        self::assertNotFalse($left, $first); self::assertNotFalse($right, $second);
        self::assertLessThan($right, $left, $first.' must precede '.$second);
    }

    private function read(string $path): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
