<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Guildhall\GuildhallActivationDemandService;
use PHPUnit\Framework\TestCase;

final class GuildhallActivationDemandServiceTest extends TestCase
{
    public function testRecordsExactBlockedActivationDemandWithoutInventingAuthorityOrOccupancy(): void
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-demand-'.bin2hex(random_bytes(6));
        $commissionId = 'planning-guildhall-1234567890abcdef1234';
        $directory = $root.'/var/imperium/offices/guildhall/inbox';
        mkdir($directory, 0770, true);
        $packet = ['commission_id' => $commissionId];
        $packet['record_digest'] = hash('sha256', CanonicalJson::encode($packet));
        $envelope = [
            'schema' => 'imperium.office-inbox-envelope/v1',
            'delivery_id' => 'delivery-test',
            'office' => 'guildhall',
            'target' => 'guildhall.guildmaster',
            'commission_digest' => $packet['record_digest'],
            'status' => 'DELIVERED_PENDING_RECIPIENT',
            'recipient_acceptance' => null,
            'execution_authority' => false,
            'packet' => $packet,
        ];
        $envelope['record_digest'] = hash('sha256', CanonicalJson::encode($envelope));
        file_put_contents($directory.'/'.$commissionId.'.json', json_encode($envelope, JSON_THROW_ON_ERROR));

        try {
            $service = new GuildhallActivationDemandService($root);
            $demand = $service->demand($commissionId);
            self::assertSame($demand, $service->demand($commissionId));
            self::assertSame('BLOCKED_PROFILE_ARTIFACTS', $demand['status']);
            self::assertCount(4, $demand['required_seats']);
            self::assertFalse($demand['spawning_authority']);
            self::assertFalse($demand['recipient_acceptance']);
            self::assertFalse($demand['execution_authority']);
            self::assertFileExists($root.'/var/imperium/mastermason/spawning-requests/'.$demand['demand_id'].'.json');
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
