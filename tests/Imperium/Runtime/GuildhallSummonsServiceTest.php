<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Guildhall\CanonicalGuildhallStaffRegistry;
use App\Imperium\Runtime\Guildhall\GuildhallActivationDemandService;
use App\Imperium\Runtime\Guildhall\GuildhallProvisioningCaseService;
use App\Imperium\Runtime\Guildhall\GuildhallSummonsService;
use App\Imperium\Runtime\Guildhall\ProfileDefinitionRegistry;
use PHPUnit\Framework\TestCase;

final class GuildhallSummonsServiceTest extends TestCase
{
    public function testRecordsAttributedSummonsAndIssuesFourBoundedConstructionCommissions(): void
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-summons-'.bin2hex(random_bytes(6));
        $project = dirname(__DIR__, 3);
        $definitions = new ProfileDefinitionRegistry($project);
        $staff = new CanonicalGuildhallStaffRegistry($project, $definitions);
        $bootstrap = new StateStore($root);
        $proceedings = new ProceedingStore($root);
        $proceedingId = 'proceeding-summons-test';
        $commissionId = 'planning-guildhall-1234567890abcdef1234';
        $occupants = $this->occupants();
        $bootstrap->locked(static function () use ($bootstrap, $occupants): void {
            $bootstrap->write([
                'state' => 'CURIA_READY',
                'binding' => ['instance_id' => 'imperium-test', 'manifest_id' => str_repeat('a', 64)],
                'events' => [[
                    'transition' => 'T10', 'result' => 'SUCCESS',
                    'output' => ['runtime' => ['addressable' => true, 'occupants' => $occupants]],
                ]],
            ]);
        });
        $proceeding = [
            'proceeding_id' => $proceedingId,
            'instance_id' => 'imperium-test',
            'seneschal' => ['occupant' => $this->occupantRef($occupants['seneschal'], 'seneschal')],
            'chamberlain' => ['occupant' => $this->occupantRef($occupants['chamberlain'], 'chamberlain')],
        ];
        $proceeding['record_digest'] = hash('sha256', CanonicalJson::encode($proceeding));
        $proceedings->persist($proceeding);
        $packet = [
            'schema' => 'imperium.planning-commission/v1',
            'commission_id' => $commissionId,
            'proceeding_id' => $proceedingId,
            'issuer' => ['seat' => 'curia.seneschal'],
            'target' => 'guildhall.guildmaster',
            'execution_authority' => false,
        ];
        $packet['record_digest'] = hash('sha256', CanonicalJson::encode($packet));
        $envelope = [
            'schema' => 'imperium.office-inbox-envelope/v1',
            'delivery_id' => 'delivery-summons-test',
            'office' => 'guildhall',
            'target' => 'guildhall.guildmaster',
            'commission_digest' => $packet['record_digest'],
            'status' => 'DELIVERED_PENDING_RECIPIENT',
            'recipient_acceptance' => null,
            'execution_authority' => false,
            'packet' => $packet,
        ];
        $envelope['record_digest'] = hash('sha256', CanonicalJson::encode($envelope));
        $inbox = $root.'/var/imperium/offices/guildhall/inbox';
        mkdir($inbox, 0770, true);
        file_put_contents($inbox.'/'.$commissionId.'.json', json_encode($envelope, JSON_THROW_ON_ERROR));

        try {
            $demand = (new GuildhallActivationDemandService($root, $definitions))->demand($commissionId);
            $case = (new GuildhallProvisioningCaseService($root, $definitions, $staff))->open($demand['demand_id']);
            $service = new GuildhallSummonsService($root, $bootstrap, $proceedings, $staff);
            $result = $service->summon($case['case_id']);

            self::assertSame($result, $service->summon($case['case_id']));
            self::assertSame('GUILDHALL_ACTIVATION_REQUESTED', $result['summons']['seneschal']['disposition']);
            self::assertSame('GUILDHALL_SUMMONS_RECORDED_AND_ROUTED', $result['summons']['chamberlain']['disposition']);
            self::assertTrue($result['summons']['spawning_authority']);
            self::assertFalse($result['summons']['recipient_acceptance']);
            self::assertFalse($result['summons']['execution_authority']);
            self::assertFileExists($root.'/var/imperium/curia/proceedings/'.$proceedingId.'.summons.'.$result['summons']['summons_id'].'.json');
            self::assertCount(4, $result['commissions']);
            foreach ($result['commissions'] as $commission) {
                self::assertSame('ISSUED_PENDING_CONSCRIPTION', $commission['status']);
                self::assertTrue($commission['spawning_authority']);
                self::assertFalse($commission['seat_binding_authority']);
                self::assertFalse($commission['execution_authority']);
                self::assertFileExists($root.'/var/imperium/offices/conscription/inbox/'.$commission['commission_id'].'.json');
            }
        } finally {
            $this->removeTree($root);
        }
    }

    private function occupants(): array
    {
        $occupants = [];
        foreach (['seneschal', 'chamberlain', 'secretary'] as $role) {
            $occupants[$role] = ['manifestation_id' => 'imperium-test.officer.'.$role.'.1', 'seat' => 'curia.'.$role, 'occupancy_generation' => 1, 'status' => 'active'];
        }

        return $occupants;
    }

    private function occupantRef(array $occupant, string $role): array
    {
        return ['seat' => 'curia.'.$role, 'manifestation_id' => $occupant['manifestation_id'], 'occupancy_generation' => $occupant['occupancy_generation']];
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
