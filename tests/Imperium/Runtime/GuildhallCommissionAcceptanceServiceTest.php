<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Guildhall\GuildhallCommissionAcceptanceService;
use PHPUnit\Framework\TestCase;

final class GuildhallCommissionAcceptanceServiceTest extends TestCase
{
    public function testBoundGuildmasterAcceptsExactCommissionWithoutExecutionAuthority(): void
    {
        [$root, $commissionId, $bindingId] = $this->fixture();

        try {
            $service = new GuildhallCommissionAcceptanceService($root);
            $acceptance = $service->accept($commissionId, $bindingId);

            self::assertSame($acceptance, $service->accept($commissionId, $bindingId));
            self::assertSame('imperium.guildhall-commission-acceptance/v1', $acceptance['schema']);
            self::assertSame('guildhall.guildmaster', $acceptance['actor']['seat']);
            self::assertSame('manifestation-guildmaster', $acceptance['actor']['manifestation_id']);
            self::assertSame(1, $acceptance['actor']['occupancy_generation']);
            self::assertSame('ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION', $acceptance['disposition']);
            self::assertTrue($acceptance['recipient_acceptance']);
            self::assertTrue($acceptance['deliberation_authority']);
            self::assertTrue($acceptance['personnel_disposition_authority']);
            self::assertFalse($acceptance['spawning_authority']);
            self::assertFalse($acceptance['seat_binding_authority']);
            self::assertFalse($acceptance['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/guildhall/acceptances/'.$acceptance['acceptance_id'].'.json');
        } finally {
            $this->removeTree($root);
        }
    }

    public function testMismatchedCommissionLineageProducesNoAcceptance(): void
    {
        [$root, $commissionId, $bindingId, $summonsPath] = $this->fixture();

        try {
            $summons = json_decode((string) file_get_contents($summonsPath), true, 512, JSON_THROW_ON_ERROR);
            $summons['planning_commission_digest'] = str_repeat('0', 64);
            unset($summons['record_digest']);
            $summons['record_digest'] = hash('sha256', CanonicalJson::encode($summons));
            file_put_contents($summonsPath, json_encode($summons, JSON_THROW_ON_ERROR));

            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('G46_ACCEPTANCE_CHAIN_INVALID');
            try {
                (new GuildhallCommissionAcceptanceService($root))->accept($commissionId, $bindingId);
            } finally {
                self::assertDirectoryDoesNotExist($root.'/var/imperium/offices/guildhall/acceptances');
            }
        } finally {
            $this->removeTree($root);
        }
    }

    private function fixture(): array
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-acceptance-'.bin2hex(random_bytes(6));
        $commissionId = 'planning-guildhall-1234567890abcdef1234';
        $bindingId = 'guildhall-binding-1234567890abcdef1234';
        $summonsId = 'guildhall-summons-1234567890abcdef1234';
        $commission = [
            'schema' => 'imperium.planning-commission/v1',
            'commission_id' => $commissionId,
            'phase' => 'planning-only',
            'proceeding_id' => 'proceeding-test',
            'instance_id' => 'imperium-test',
            'issuer' => ['seat' => 'curia.seneschal'],
            'target' => 'guildhall.guildmaster',
            'purpose' => 'Determine professions and personnel suitability.',
            'authorized_resources' => ['Guildhall personnel disposition'],
            'expected_products' => ['Personnel Disposition'],
            'forbidden_effects' => ['manifestation', 'deployment'],
            'status' => 'ISSUED_PENDING_RECIPIENT',
            'execution_authority' => false,
        ];
        $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission));
        $envelope = [
            'schema' => 'imperium.office-inbox-envelope/v1',
            'delivery_id' => 'delivery-test',
            'office' => 'guildhall',
            'target' => 'guildhall.guildmaster',
            'commission_id' => $commissionId,
            'commission_digest' => $commission['record_digest'],
            'status' => 'DELIVERED_PENDING_RECIPIENT',
            'recipient_acceptance' => null,
            'execution_authority' => false,
            'packet' => $commission,
        ];
        $envelope['record_digest'] = hash('sha256', CanonicalJson::encode($envelope));
        $inbox = $root.'/var/imperium/offices/guildhall/inbox';
        mkdir($inbox, 0770, true);
        file_put_contents($inbox.'/'.$commissionId.'.json', json_encode($envelope, JSON_THROW_ON_ERROR));

        $summons = [
            'schema' => 'imperium.guildhall-summons/v1',
            'summons_id' => $summonsId,
            'proceeding_id' => 'proceeding-test',
            'instance_id' => 'imperium-test',
            'planning_commission_id' => $commissionId,
            'planning_commission_digest' => $commission['record_digest'],
            'mastermason' => ['disposition' => 'EXACT_SUMMONS_VALIDATED'],
            'spawning_authority' => true,
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];
        $summons['record_digest'] = hash('sha256', CanonicalJson::encode($summons));
        $proceedings = $root.'/var/imperium/curia/proceedings';
        mkdir($proceedings, 0770, true);
        $summonsPath = $proceedings.'/proceeding-test.summons.'.$summonsId.'.json';
        file_put_contents($summonsPath, json_encode($summons, JSON_THROW_ON_ERROR));

        $bindings = [];
        foreach (['guildhall.guildmaster', 'guildhall.committee.disciplinary-fit', 'guildhall.committee.composition', 'guildhall.committee.boundary-challenge'] as $seat) {
            $bindings[$seat] = [
                'seat' => $seat,
                'manifestation_id' => 'guildhall.guildmaster' === $seat ? 'manifestation-guildmaster' : 'manifestation-'.substr(hash('sha256', $seat), 0, 10),
                'prior_occupancy_generation' => 0,
                'occupancy_generation' => 1,
                'status' => 'BOUND_PENDING_COMMISSION_ACCEPTANCE',
            ];
        }
        $binding = [
            'schema' => 'imperium.guildhall-seat-binding-cohort/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'office' => 'guildhall',
            'source_summons_id' => $summonsId,
            'source_summons_digest' => $summons['record_digest'],
            'bindings' => $bindings,
            'office_status' => 'ACTIVE_AWAITING_COMMISSION_ACCEPTANCE',
            'binding_atomic' => true,
            'seat_binding_authority' => true,
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];
        $binding['record_digest'] = hash('sha256', CanonicalJson::encode($binding));
        $occupancy = $root.'/var/imperium/offices/guildhall/occupancy';
        mkdir($occupancy, 0770, true);
        file_put_contents($occupancy.'/'.$bindingId.'.json', json_encode($binding, JSON_THROW_ON_ERROR));

        return [$root, $commissionId, $bindingId, $summonsPath];
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
