<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Armory\CanonicalEmailSendToolDefinitionService;
use App\Imperium\Runtime\Armory\GovernedToolOperationContract;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch2Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-tool-definition-'.bin2hex(random_bytes(5));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testArmoryDefinesOneCanonicalProviderNeutralEmailSendTool(): void
    {
        $service = new CanonicalEmailSendToolDefinitionService($this->root);
        $definition = $service->define(new \DateTimeImmutable('2026-08-29T00:00:00+00:00'));

        self::assertSame(GovernedToolOperationContract::REQUIRED_FIELDS, array_keys($definition));
        self::assertSame(GovernedToolOperationContract::SCHEMA, $definition['schema']);
        self::assertSame('email.send', $definition['tool_id']);
        self::assertSame('email.send', $definition['operation']);
        self::assertSame(['office' => 'armory', 'seat' => 'armory.armorer'], $definition['owner']);
        self::assertSame('IRREVERSIBLE_EXTERNAL_COMMUNICATION', $definition['effect_class']);
        self::assertTrue($definition['provider_policy']['provider_neutral']);
        self::assertTrue($definition['provider_policy']['provider_binding_required']);
        self::assertFalse($definition['provider_policy']['provider_substitution_permitted']);
        self::assertFalse($definition['secret_policy']['payload_may_contain_credentials']);
        self::assertSame('DEFINED_INACTIVE', $definition['status']);
        self::assertSame($definition, $service->read());
    }

    public function testExactReplayConvergesAndChangedDefinitionConflicts(): void
    {
        $service = new CanonicalEmailSendToolDefinitionService($this->root);
        $at = new \DateTimeImmutable('2026-08-29T00:00:00+00:00');
        $first = $service->define($at);

        self::assertSame($first, $service->define($at));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PST111_IMMUTABLE_RECORD_CONFLICT');
        $service->define($at->modify('+1 second'));
    }

    public function testPayloadAndResultSemanticsExcludeProviderAndCredentialAssembly(): void
    {
        self::assertSame(['to', 'subject'], CanonicalEmailSendToolDefinitionService::PAYLOAD_SEMANTICS['required_fields']);
        self::assertTrue(CanonicalEmailSendToolDefinitionService::PAYLOAD_SEMANTICS['at_least_one_content_field_required']);
        self::assertTrue(CanonicalEmailSendToolDefinitionService::PAYLOAD_SEMANTICS['exact_serialized_bytes_authorized']);
        self::assertFalse(CanonicalEmailSendToolDefinitionService::PAYLOAD_SEMANTICS['credential_material_permitted']);
        self::assertFalse(CanonicalEmailSendToolDefinitionService::PAYLOAD_SEMANTICS['provider_fields_permitted']);
        self::assertFalse(CanonicalEmailSendToolDefinitionService::NORMALIZED_RESULT_SEMANTICS['provider_reinvocation_permitted']);
        self::assertFalse(CanonicalEmailSendToolDefinitionService::NORMALIZED_RESULT_SEMANTICS['automatic_replay_permitted']);
    }

    public function testBatchTwoDocumentationKeepsProviderAndExecutionClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $definition = (string) file_get_contents($root.'/docs/governed-email-send-tool-definition.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-batch-2-complete.md');

        foreach (['`BATCH_2_CANONICAL_EMAIL_SEND_TOOL_DEFINED_INACTIVE`', '`email.send`', '`IRREVERSIBLE_EXTERNAL_COMMUNICATION`', '`DEFINED_INACTIVE`', 'No provider identity', 'exact replay'] as $proof) {
            self::assertStringContainsString($proof, $definition);
        }
        foreach (['Only Batch 3 may next be considered', 'No provider was selected', 'Runtime behavior is unchanged', 'Batch 3 is not authorized'] as $proof) {
            self::assertStringContainsString($proof, $handoff);
        }
    }
}
