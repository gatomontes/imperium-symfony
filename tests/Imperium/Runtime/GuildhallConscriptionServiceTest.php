<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Conscription\GuildhallConscriptionService;
use App\Imperium\Runtime\Guildhall\CanonicalGuildhallStaffRegistry;
use App\Imperium\Runtime\Guildhall\ProfileDefinitionRegistry;
use PHPUnit\Framework\TestCase;

final class GuildhallConscriptionServiceTest extends TestCase
{
    public function testConsumesExactCommissionsAndReturnsFourQualifiedUnboundPackets(): void
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-conscription-'.bin2hex(random_bytes(6));
        $project = dirname(__DIR__, 3);
        $bootstrap = new StateStore($root);
        $definitions = new ProfileDefinitionRegistry($project);
        $staff = new CanonicalGuildhallStaffRegistry($project, $definitions);
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

        try {
            $service = new GuildhallConscriptionService($root, $bootstrap, $staff, $substrate);
            $result = $service->fulfill($summonsId);

            self::assertSame($result, $service->fulfill($summonsId));
            self::assertSame('imperium-test.officer.ordinary-recruiter.1', $result['recruiter']['manifestation_id']);
            self::assertCount(4, $result['deliveries']);
            foreach ($result['deliveries'] as $delivery) {
                self::assertSame('QUALIFIED_UNBOUND', $delivery['candidate']['status']);
                self::assertSame('PROFILE_INSTALLED', $delivery['candidate']['substrate_instance']['status']);
                self::assertSame('QUALIFIED', $delivery['qualification']['disposition']);
                self::assertTrue($delivery['sealed']);
                self::assertFalse($delivery['seat_binding_authority']);
                self::assertFalse($delivery['recipient_acceptance']);
                self::assertFalse($delivery['execution_authority']);
                self::assertFileExists($root.'/var/imperium/mastermason/qualified-manifestations/'.$delivery['delivery_id'].'.json');
            }
        } finally {
            $this->removeTree($root);
        }
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
