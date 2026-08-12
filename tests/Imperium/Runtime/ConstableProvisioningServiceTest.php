<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\CanonicalConstableRegistry;
use App\Imperium\Runtime\Garrison\ConstableProvisioningService;
use PHPUnit\Framework\TestCase;

final class ConstableProvisioningServiceTest extends TestCase
{
    public function testOpensNonAuthorizingCanonicalConstableCase(): void
    {
        $root = sys_get_temp_dir().'/imperium-constable-provisioning-'.bin2hex(random_bytes(6));
        $inquiryId = 'garrison-inquiry-1234567890abcdef1234';
        $inquiry = ['schema' => 'imperium.garrison-inventory-inquiry/v1', 'inquiry_id' => $inquiryId, 'instance_id' => 'imperium-test', 'status' => 'CONSTABLE_ACTIVATION_REQUIRED', 'constable_occupancy' => null, 'authoritative_inventory_response' => false, 'execution_authority' => false];
        $inquiry['record_digest'] = hash('sha256', CanonicalJson::encode($inquiry));
        $inbox = $root.'/var/imperium/offices/garrison/inbox';
        mkdir($inbox, 0770, true);
        file_put_contents($inbox.'/'.$inquiryId.'.json', json_encode($inquiry, JSON_THROW_ON_ERROR));
        try {
            $registry = new CanonicalConstableRegistry(dirname(__DIR__, 3));
            $service = new ConstableProvisioningService($root, $registry);
            $case = $service->open($inquiryId);
            self::assertSame($case, $service->open($inquiryId));
            self::assertSame('CANONICAL_CONSTABLE_READY', $case['status']);
            self::assertSame('garrison.constable', $case['target_seat']);
            self::assertFalse($case['mission_persona_selection_required']);
            self::assertFalse($case['per_mission_profile_derivation_required']);
            self::assertFalse($case['spawning_authority']);
            self::assertFalse($case['seat_binding_authority']);
            self::assertFalse($case['inventory_response_authority']);
            self::assertFalse($case['execution_authority']);
            self::assertSame('garrison.canonical-constable', $case['canonical_constable_package']['package_id']);
            self::assertFileExists($root.'/var/imperium/mastermason/activation-cases/'.$case['case_id'].'.json');
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); }
        rmdir($path);
    }
}
