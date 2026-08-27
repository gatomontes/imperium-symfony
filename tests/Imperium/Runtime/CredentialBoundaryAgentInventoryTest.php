<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CredentialBoundaryAgentInventoryTest extends TestCase
{
    public function testSystemWideCredentialPlatformGateIsClosedByExecutableScan(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = json_decode((string) file_get_contents($root.'/docs/credential-boundary-agent-inventory.json'), true, 64, JSON_THROW_ON_ERROR);

        self::assertSame('imperium.credential-boundary-agent-inventory/v1', $inventory['schema'] ?? null);
        self::assertTrue($inventory['system_wide_gate_closed'] ?? false);
        self::assertSame([], $inventory['platform_definitions'] ?? null);
        self::assertSame([], $inventory['definitions'] ?? null);
        self::assertSame('env:DEEPSEEK_API_KEY', $inventory['credential_source'] ?? null);

        $sourceFiles = $this->files($root.'/src', ['php']);
        $configurationFiles = $this->files($root.'/config', ['php', 'yaml', 'yml']);

        self::assertSame([], $this->containing($sourceFiles, 'Symfony\\AI\\Agent\\AgentInterface'));
        self::assertSame([], $this->containing($sourceFiles, 'ai.agent.'));
        self::assertSame([], $this->containing($configurationFiles, 'ai.agent.'));
        self::assertSame([], $this->containing($configurationFiles, 'DEEPSEEK_API_KEY'));
        self::assertSame([], $this->containing($configurationFiles, 'api_key:'));

        self::assertSame(
            [$inventory['claim_bound_provider_adapter']],
            $this->relative($root, $this->containing($sourceFiles, 'Factory::createPlatform(')),
        );
        self::assertSame(
            $inventory['credential_reference_sites'],
            $this->relative($root, $this->containing($sourceFiles, 'DEEPSEEK_API_KEY')),
        );
        self::assertSame(
            $inventory['provider_invokers'],
            $this->relative($root, $this->containing($sourceFiles, '$this->platform->invoke(')),
        );

        $adapter = (string) file_get_contents($root.'/'.$inventory['claim_bound_provider_adapter']);
        self::assertStringContainsString('apiKey: $secret', $adapter);
        self::assertStringContainsString("name: 'deepseek'", $adapter);

        $delegate = (string) file_get_contents($root.'/src/Imperium/Runtime/Citadel/SymfonyAiBrokeredDelegateProviderInvoker.php');
        self::assertStringContainsString('ClaimBoundCredentialBroker', $delegate);
        self::assertStringContainsString('journal->markUnknown', $delegate);
        self::assertStringContainsString('responses->seal', $delegate);

        $legate = (string) file_get_contents($root.'/src/Imperium/Runtime/Citadel/SymfonyAiLegateCognitionGateway.php');
        self::assertStringContainsString('LegateClaimBoundCredentialBroker', $legate);
        self::assertStringContainsString('credentials->consume', $legate);

        $governance = (string) file_get_contents($root.'/src/Imperium/Runtime/Clavium/GovernanceCognitionInvoker.php');
        self::assertStringContainsString('GovernanceClaimBoundCredentialBroker', $governance);
        self::assertStringContainsString('reserveGovernance', $governance);
        self::assertStringContainsString('journal->markUnknown', $governance);
        self::assertStringContainsString('responses->seal', $governance);

        $operational = (string) file_get_contents($root.'/src/Imperium/Runtime/Mission/SymfonyAiOperationalExecutionCognitionGateway.php');
        self::assertStringContainsString('OperationalClaimBoundCredentialBroker', $operational);
        self::assertStringContainsString('reserveOperational', $operational);
        self::assertStringContainsString('journal->markUnknown', $operational);
        self::assertStringContainsString('responses->seal', $operational);

        $sortie = (string) file_get_contents($root.'/src/Imperium/Runtime/Sortie/BrokeredSortieCognitionProviderInvoker.php');
        self::assertStringContainsString('CredentialBroker', $sortie);
        self::assertStringContainsString("'automatic_replay_permitted' => false", $sortie);
        self::assertStringContainsString('PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED', $sortie);
    }

    /** @param list<string> $extensions @return list<string> */
    private function files(string $directory, array $extensions): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /** @param list<string> $files @return list<string> */
    private function containing(array $files, string $needle): array
    {
        $matches = [];
        foreach ($files as $file) {
            if (str_contains((string) file_get_contents($file), $needle)) {
                $matches[] = $file;
            }
        }

        return $matches;
    }

    /** @param list<string> $files @return list<string> */
    private function relative(string $root, array $files): array
    {
        $relative = array_map(
            static fn (string $file): string => str_replace('\\', '/', substr($file, strlen($root) + 1)),
            $files,
        );
        sort($relative, SORT_STRING);

        return $relative;
    }
}
