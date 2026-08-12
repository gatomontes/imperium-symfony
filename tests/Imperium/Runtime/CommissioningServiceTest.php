<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Curia\CommissioningService;
use App\Imperium\Runtime\Curia\ImperatorActs;
use App\Imperium\Runtime\Curia\ProceedingStore;
use PHPUnit\Framework\TestCase;

final class CommissioningServiceTest extends TestCase
{
    public function testIssuesOnlyBoundedPendingPlanningCommissions(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-commissioning-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-commission-test', 'instance_id' => 'imperium-test']);
        $demands = [
            'Guildhall professional and personnel disposition',
            'Garrison protected personnel inventory facts supplied to Guildhall',
            'Armory tooling disposition for passive methodology',
            'Secure document storage for draft outputs',
            'Standard office productivity tooling for drafting',
        ];
        $store->appendTurn('proceeding-commission-test', 'response-plan-test', 1, [
            'seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED', 'mission_plan' => $this->plan()],
            'resource_demands' => $demands,
        ]);
        $acts = new ImperatorActs($store);
        $acts->approvePlan('proceeding-commission-test', 1, 'approval-commission-test');
        $acts->authorizeResources('proceeding-commission-test', 1, $demands, 'Planning inquiries only.', 'authorization-commission-test');

        try {
            $result = (new CommissioningService($store))->issue('proceeding-commission-test', 1);
            self::assertSame(['guildhall', 'armory'], array_keys($result['commissions']));
            self::assertSame('ISSUED_PENDING_RECIPIENT', $result['commissions']['guildhall']['status']);
            self::assertSame('planning-only', $result['commissions']['armory']['phase']);
            self::assertFalse($result['execution_authority']);
            self::assertContains('persona construction', $result['commissions']['guildhall']['forbidden_effects']);
            self::assertContains('tool activation', $result['commissions']['armory']['forbidden_effects']);
            self::assertCount(2, $result['mechanical_support']);
            self::assertCount(2, $store->commissions('proceeding-commission-test'));

            self::assertSame($result, (new CommissioningService($store))->issue('proceeding-commission-test', 1));
            self::assertCount(2, $store->commissions('proceeding-commission-test'));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testNormalizesExplicitDestinationsInsteadOfAssumingProsePrefixes(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-commissioning-normalized-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-normalized-test', 'instance_id' => 'imperium-test']);
        $demands = [
            'Profession and personnel disposition: Guildhall authority for personnel disposition and as institutional source of independent review',
            'Protected inventory access: Garrison supply of admitted Persona and personnel inventory and availability facts to Guildhall',
            'Tooling disposition: Armory authority to dispose passive observation tools, methodology, and checklists',
            'Storage: secure document storage for assessment outputs',
            'Mechanical support: standard office productivity tooling for drafting and tracking',
        ];
        $plan = $this->plan();
        $plan['office_participation'] = [
            'Profession and personnel disposition: Guildhall authority for personnel disposition',
            'Tooling disposition: Armory authority for passive assessment tooling',
        ];
        $store->appendTurn('proceeding-normalized-test', 'response-normalized-test', 1, [
            'seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED', 'mission_plan' => $plan],
            'resource_demands' => $demands,
        ]);
        $acts = new ImperatorActs($store);
        $acts->approvePlan('proceeding-normalized-test', 1, 'approval-normalized-test');
        $acts->authorizeResources('proceeding-normalized-test', 1, $demands, 'Planning inquiries only.', 'authorization-normalized-test');

        try {
            $result = (new CommissioningService($store))->issue('proceeding-normalized-test', 1);

            self::assertCount(2, $result['commissions']['guildhall']['authorized_resources']);
            self::assertCount(1, $result['commissions']['armory']['authorized_resources']);
            self::assertCount(2, $result['mechanical_support']);
            self::assertFalse($result['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    private function plan(): array
    {
        return [
            'objective' => 'Assess a public web application.',
            'scope' => ['Supplied public URLs'],
            'deliverables' => ['Risk report'],
            'constraints' => ['Passive only'],
            'required_inputs' => ['Target URL'],
            'personnel_requirements' => ['Security assessor'],
            'tool_requirements' => ['Passive checklist'],
            'data_requirements' => ['Public responses'],
            'office_participation' => [
                'Guildhall: profession and personnel disposition',
                'Armory: tooling disposition',
            ],
            'stop_conditions' => ['Authentication required'],
        ];
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
