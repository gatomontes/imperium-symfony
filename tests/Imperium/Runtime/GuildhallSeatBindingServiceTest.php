<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Conscription\GuildhallConscriptionService;
use App\Imperium\Runtime\Guildhall\CanonicalGuildhallStaffRegistry;
use App\Imperium\Runtime\Guildhall\GuildhallSeatBindingService;
use App\Imperium\Runtime\Guildhall\ProfileDefinitionRegistry;
use PHPUnit\Framework\TestCase;

final class GuildhallSeatBindingServiceTest extends TestCase
{
    public function testAtomicallyBindsExactQualifiedCohortWithoutAcceptanceOrExecution(): void
    {
        [$root, $bootstrap, $staff, $substrate, $summonsId] = $this->fixture();

        try {
            (new GuildhallConscriptionService($root, $bootstrap, $staff, $substrate))->fulfill($summonsId);
            $service = new GuildhallSeatBindingService($root, $bootstrap, $staff, $substrate);
            $cohort = $service->bind($summonsId);

            self::assertSame($cohort, $service->bind($summonsId));
            self::assertSame('imperium.guildhall-seat-binding-cohort/v1', $cohort['schema']);
            self::assertSame('ACTIVE_AWAITING_COMMISSION_ACCEPTANCE', $cohort['office_status']);
            self::assertTrue($cohort['binding_atomic']);
            self::assertTrue($cohort['seat_binding_authority']);
            self::assertSame('CONSUMED_BY_ATOMIC_BINDING', $cohort['seat_binding_disposition']);
            self::assertFalse($cohort['recipient_acceptance']);
            self::assertFalse($cohort['execution_authority']);
            self::assertCount(4, $cohort['bindings']);
            foreach ($cohort['bindings'] as $seat => $binding) {
                self::assertSame($seat, $binding['seat']);
                self::assertSame(0, $binding['prior_occupancy_generation']);
                self::assertSame(1, $binding['occupancy_generation']);
                self::assertSame('BOUND_PENDING_COMMISSION_ACCEPTANCE', $binding['status']);
            }
            self::assertFileExists($root.'/var/imperium/offices/guildhall/occupancy/'.$cohort['binding_id'].'.json');
        } finally {
            $this->removeTree($root);
        }
    }

    public function testOneInvalidPacketPreventsEverySeatBinding(): void
    {
        [$root, $bootstrap, $staff, $substrate, $summonsId] = $this->fixture();

        try {
            $result = (new GuildhallConscriptionService($root, $bootstrap, $staff, $substrate))->fulfill($summonsId);
            $path = $root.'/var/imperium/mastermason/qualified-manifestations/'.$result['deliveries'][0]['delivery_id'].'.json';
            $packet = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $packet['candidate']['status'] = 'CORRUPTED';
            file_put_contents($path, json_encode($packet, JSON_THROW_ON_ERROR));

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('M35_QUALIFIED_PACKET_INVALID');
            try {
                (new GuildhallSeatBindingService($root, $bootstrap, $staff, $substrate))->bind($summonsId);
            } finally {
                self::assertDirectoryDoesNotExist($root.'/var/imperium/offices/guildhall/occupancy');
            }
        } finally {
            $this->removeTree($root);
        }
    }

    private function fixture(): array
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-binding-'.bin2hex(random_bytes(6));
        $project = dirname(__DIR__, 3);
        $bootstrap = new StateStore($root);
        $staff = new CanonicalGuildhallStaffRegistry($project, new ProfileDefinitionRegistry($project));
        $substrate = new GenericOfficerSubstrateRegistry($project);
        $summonsId = 'guildhall-summons-1234567890abcdef1234';
        $bootstrap->locked(static function () use ($bootstrap): void {
            $bootstrap->write([
                'state' => 'CURIA_READY',
                'binding' => ['instance_id' => 'imperium-test'],
                'events' => [[
                    'transition' => 'T04', 'result' => 'SUCCESS',
                    'output' => ['successor' => [
                        'manifestation_id' => 'imperium-test.officer.ordinary-recruiter.1',
                        'seat' => 'conscription.recruiter',
                        'occupancy_generation' => 2,
                        'authority' => 'ordinary-recruiter',
                    ]],
                ]],
            ]);
        });
        $summons = [
            'schema' => 'imperium.guildhall-summons/v1',
            'summons_id' => $summonsId,
            'instance_id' => 'imperium-test',
            'mastermason' => ['disposition' => 'EXACT_SUMMONS_VALIDATED'],
            'canonical_staff_package' => $staff->current(),
            'generic_officer_substrate' => $substrate->current(),
            'spawning_authority' => true,
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];
        $summons['record_digest'] = hash('sha256', CanonicalJson::encode($summons));
        $proceedings = $root.'/var/imperium/curia/proceedings';
        mkdir($proceedings, 0770, true);
        file_put_contents($proceedings.'/proceeding-test.summons.'.$summonsId.'.json', json_encode($summons, JSON_THROW_ON_ERROR));
        $inbox = $root.'/var/imperium/offices/conscription/inbox';
        mkdir($inbox, 0770, true);
        foreach ($staff->members() as $index => $member) {
            $commission = [
                'schema' => 'imperium.construction-commission/v1',
                'commission_id' => 'guildhall-construction-'.str_pad((string) ($index + 1), 20, '0', STR_PAD_LEFT),
                'issuer' => 'mastermason',
                'source_summons_id' => $summonsId,
                'source_summons_digest' => $summons['record_digest'],
                'instance_id' => 'imperium-test',
                'office' => 'guildhall',
                'target_seat' => $member['seat'],
                'persona' => $member['persona'],
                'profile' => $member['profile'],
                'qualification_contract' => $member['qualification_contract'],
                'substrate' => $substrate->current(),
                'status' => 'ISSUED_PENDING_CONSCRIPTION',
                'spawning_authority' => true,
                'seat_binding_authority' => false,
                'execution_authority' => false,
            ];
            $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission));
            file_put_contents($inbox.'/'.$commission['commission_id'].'.json', json_encode($commission, JSON_THROW_ON_ERROR));
        }

        return [$root, $bootstrap, $staff, $substrate, $summonsId];
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
