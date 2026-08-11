<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Curia\ImperatorActs;
use App\Imperium\Runtime\Curia\ProceedingStore;
use PHPUnit\Framework\TestCase;

final class ImperatorActsTest extends TestCase
{
    public function testApprovalAndAuthorizationRemainSeparateAndJointlyEnableCommissioning(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-acts-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $store = new ProceedingStore($root);
        $store->persist([
            'proceeding_id' => 'proceeding-test-acts',
            'instance_id' => 'imperium-test',
            'manifest_id' => str_repeat('a', 64),
        ]);
        $turn = $store->appendTurn('proceeding-test-acts', 'response-test-plan', 1, [
            'schema' => 'imperium.curian-turn/v1',
            'proceeding_id' => 'proceeding-test-acts',
            'response_id' => 'response-test-plan',
            'seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED'],
            'resource_demands' => ['passive assessment tooling', 'secure document storage'],
        ]);
        $acts = new ImperatorActs($store);

        try {
            $approval = $acts->approvePlan('proceeding-test-acts', 1, 'approval-test-0001');
            self::assertTrue($approval['readiness']['plan_approved']);
            self::assertFalse($approval['readiness']['resource_demands_satisfied']);
            self::assertFalse($approval['readiness']['commissioning_ready']);
            self::assertFalse($approval['grants_resource_authority']);

            $authorization = $acts->authorizeResources(
                'proceeding-test-acts',
                1,
                $turn['resource_demands'],
                'Passive and non-invasive use only.',
                'authorization-test-0001',
            );
            self::assertTrue($authorization['readiness']['plan_approved']);
            self::assertTrue($authorization['readiness']['resource_demands_satisfied']);
            self::assertTrue($authorization['readiness']['commissioning_ready']);
            self::assertFalse($authorization['grants_execution_authority']);
            self::assertFalse($authorization['approves_plan']);
            self::assertCount(2, $store->acts('proceeding-test-acts'));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testRefusesUndeclaredResource(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-acts-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-test-refusal', 'instance_id' => 'imperium-test']);
        $store->appendTurn('proceeding-test-refusal', 'response-test-plan', 1, [
            'seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED'],
            'resource_demands' => ['declared resource'],
        ]);
        $acts = new ImperatorActs($store);

        try {
            $this->expectExceptionMessage('C32_UNDECLARED_RESOURCE');
            $acts->authorizeResources('proceeding-test-refusal', 1, ['unlimited access']);
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $child = $path.DIRECTORY_SEPARATOR.$entry;
            is_dir($child) ? $this->removeTree($child) : @unlink($child);
        }
        @rmdir($path);
    }
}
