<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\FoundryGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\TestCase;

final class FoundryGovernanceCognitionBoundaryTest extends TestCase
{
    public function testInitialSpecificationAuthorityIsNativeStageSpecificAndSingleUse(): void
    {
        $root = sys_get_temp_dir().'/imperium-foundry-governance-'.bin2hex(random_bytes(5));
        try {
            $caseId = 'subordinate-construction-case-'.str_repeat('a', 20);
            $case = $this->seal([
                'schema' => 'imperium.foundry-subordinate-construction-case/v1',
                'case_id' => $caseId,
                'instance_id' => 'imperium-test',
                'status' => 'OPEN_PENDING_PERSONA_SPECIFICATION',
                'construction_authority' => true,
            ]);
            $this->write($root, 'subordinate-construction-cases', $caseId, $case);

            $resolver = new FoundryGovernanceCognitionAuthorityResolver($root);
            $authority = $resolver->resolve('foundry', 'persona-specification', $caseId);

            self::assertSame('foundry.artificer', $authority['seat']);
            self::assertSame('specify-persona', $authority['purpose']);
            self::assertSame(hash('sha256', CanonicalJson::encode([$case])), $authority['input_digest']);
            self::assertFalse($authority['consumed']);
            self::assertFalse($resolver->supports('foundry', 'persona-review-cross-stage'));

            $specification = $this->seal(['specification_id' => 'subordinate-persona-specification-'.str_repeat('b', 20), 'case_id' => $caseId]);
            $this->write($root, 'subordinate-persona-specifications', $specification['specification_id'], $specification);
            self::assertTrue($resolver->resolve('foundry', 'persona-specification', $caseId)['consumed']);
        } finally {
            $this->remove($root);
        }
    }

    public function testFoundryGatewaysContainNoDirectAgentOrCredentialPath(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (glob($root.'/src/Imperium/Runtime/Foundry/SymfonyAi*Persona*CognitionGateway.php') ?: [] as $path) {
            $source = (string) file_get_contents($path);
            self::assertStringNotContainsString('AgentInterface', $source);
            self::assertStringNotContainsString('Autowire(service:', $source);
            self::assertStringNotContainsString('DEEPSEEK_API_KEY', $source);
            self::assertStringContainsString('FoundryGovernanceCognitionInvoker', $source);
        }
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function write(string $root, string $directory, string $id, array $record): void
    {
        $path = $root.'/var/imperium/offices/foundry/'.$directory;
        if (!is_dir($path)) { mkdir($path, 0770, true); }
        file_put_contents($path.'/'.$id.'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
