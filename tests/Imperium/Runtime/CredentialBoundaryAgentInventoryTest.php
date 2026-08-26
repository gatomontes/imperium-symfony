<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CredentialBoundaryAgentInventoryTest extends TestCase
{
    public function testEveryConfiguredDirectAgentAndSourceInjectionIsClassifiedExactlyOnce(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = json_decode((string) file_get_contents($root.'/docs/credential-boundary-agent-inventory.json'), true, 64, JSON_THROW_ON_ERROR);
        self::assertSame('imperium.credential-boundary-agent-inventory/v1', $inventory['schema'] ?? null);
        self::assertFalse($inventory['system_wide_gate_closed'] ?? true);

        $classified = [];
        $declaredBindings = [];
        foreach ($inventory['definitions'] ?? [] as $definition) {
            $agent = $definition['agent'] ?? null;
            self::assertIsString($agent);
            self::assertArrayNotHasKey($agent, $classified);
            self::assertIsString($definition['cluster'] ?? null);
            self::assertIsInt($definition['batch'] ?? null);
            self::assertGreaterThanOrEqual(8, $definition['batch']);
            self::assertNotEmpty($definition['gateways'] ?? []);
            foreach ($definition['gateways'] as $gateway) {
                self::assertFileExists($root.'/'.$gateway);
                $declaredBindings[$agent.'|'.$gateway] = true;
            }
            $classified[$agent] = $definition;
        }

        $configuration = (string) file_get_contents($root.'/config/packages/ai.yaml')
            ."\n".(string) file_get_contents($root.'/config/packages/sortie/ai.yaml');
        preg_match_all('/^        ([a-zA-Z0-9_]+):\R            platform:/m', $configuration, $matches);
        $configured = $matches[1];
        sort($configured, SORT_STRING);
        $documented = array_keys($classified);
        sort($documented, SORT_STRING);
        self::assertCount(28, $configured);
        self::assertSame($configured, $documented);

        foreach ($classified as $agent => $definition) {
            $found = false;
            foreach ($definition['gateways'] as $gateway) {
                $source = (string) file_get_contents($root.'/'.$gateway);
                $services = (string) file_get_contents($root.'/config/services.yaml')
                    ."\n".(string) file_get_contents($root.'/config/services_sortie.yaml');
                if (str_contains($source, 'ai.agent.'.$agent) || str_contains($services, '@ai.agent.'.$agent)) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, 'No declared injection was found for '.$agent.'.');
        }

        $actualBindings = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src'));
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            preg_match_all('/ai\.agent\.([a-zA-Z0-9_]+)/', $source, $references);
            $relative = 'src/'.str_replace('\\', '/', substr($file->getPathname(), strlen($root.'/src/')));
            foreach ($references[1] as $agent) {
                $actualBindings[$agent.'|'.$relative] = true;
            }
        }
        foreach (['config/services.yaml', 'config/services_sortie.yaml'] as $serviceFile) {
            $service = null;
            foreach (file($root.'/'.$serviceFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (preg_match('/^    (App\\\\[^:]+):$/', $line, $serviceMatch)) {
                    $service = $serviceMatch[1];
                }
                if (null !== $service && preg_match('/@ai\.agent\.([a-zA-Z0-9_]+)/', $line, $agentMatch)) {
                    $gateway = 'src/'.str_replace('\\', '/', substr($service, 4)).'.php';
                    $actualBindings[$agentMatch[1].'|'.$gateway] = true;
                }
            }
        }
        ksort($declaredBindings, SORT_STRING);
        ksort($actualBindings, SORT_STRING);
        self::assertSame(array_keys($declaredBindings), array_keys($actualBindings));
    }
}
