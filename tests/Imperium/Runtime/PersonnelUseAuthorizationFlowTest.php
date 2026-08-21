<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\PersonnelUseAuthorizationDecisionService;
use App\Imperium\Runtime\Curia\PersonnelUseAuthorizationRequestService;
use App\Imperium\Runtime\Curia\ProceedingStore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PersonnelUseAuthorizationFlowTest extends TestCase
{
    public function testAuthorizedActCommitsOnlyOpaqueCapabilityResolvedPersonnelUse(): void
    {
        [$root, $store, $dispositionId] = $this->fixture();
        try {
            $requestService = new PersonnelUseAuthorizationRequestService($root, $store);
            $request = $requestService->request($dispositionId);
            self::assertSame($request, $requestService->request($dispositionId));
            self::assertSame('FUNCTIONAL_CAPABILITIES', $request['source_language']);
            self::assertArrayNotHasKey('persona', $request);
            self::assertArrayNotHasKey('profession', $request);
            self::assertFalse($request['personnel_use_authority']);
            self::assertFalse($request['reservation_authority']);

            $decisionService = new PersonnelUseAuthorizationDecisionService($root);
            $act = $decisionService->decide($request['request_id'], 'AUTHORIZED', 'Authorize the exact capability commitments for seven days.', 'Seven-day maximum; return for any scope expansion.');
            self::assertSame($act, $decisionService->decide($request['request_id'], 'AUTHORIZED', 'Authorize the exact capability commitments for seven days.', 'Seven-day maximum; return for any scope expansion.'));
            self::assertSame('AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE', $act['status']);
            self::assertTrue($act['personnel_use_authority']);
            self::assertTrue($act['personnel_use_authority_exercisable']);
            self::assertFalse($act['reservation_authority']);
            self::assertFalse($act['profile_derivation_authority']);
            self::assertFalse($act['spawning_authority']);
            self::assertFalse($act['seat_binding_authority']);
            self::assertFalse($act['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    #[DataProvider('nonAuthorizingDispositions')]
    public function testEveryConversationalDispositionRemainsNonAuthorizing(string $disposition): void
    {
        [$root, $store, $dispositionId] = $this->fixture();
        try {
            $request = (new PersonnelUseAuthorizationRequestService($root, $store))->request($dispositionId);
            $act = (new PersonnelUseAuthorizationDecisionService($root))->decide($request['request_id'], $disposition, 'Exact Imperator response requiring no authority to be exercised.');
            self::assertSame('NON_AUTHORIZING_IMPERATOR_DISPOSITION_RECORDED', $act['status']);
            self::assertFalse($act['personnel_use_authority']);
            self::assertFalse($act['personnel_use_authority_exercisable']);
            self::assertFalse($act['reservation_authority']);
            self::assertFalse($act['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public static function nonAuthorizingDispositions(): iterable
    {
        foreach (['REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'] as $disposition) yield $disposition => [$disposition];
    }

    private function fixture(): array
    {
        $root = sys_get_temp_dir().'/imperium-personnel-use-auth-'.bin2hex(random_bytes(6));
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-test', 'instance_id' => 'imperium-test']);
        $dispositionId = 'personnel-use-disposition-'.str_repeat('a', 20);
        $disposition = [
            'schema' => 'imperium.guildhall-personnel-use-disposition/v1',
            'disposition_id' => $dispositionId,
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test',
            'translation_boundary' => ['name' => 'CAPABILITY_TO_PROFESSION', 'authority' => 'guildhall.guildmaster'],
            'resolved_capability_slots' => [[
                'capability_slot_id' => 'slot-passive-assessment',
                'capability_requirements' => ['Analyze public application behavior', 'Produce evidence-bound findings'],
                'profession' => 'Web application security assessor',
                'persona' => ['custody_id' => 'persona-custody-test', 'persona_id' => 'persona-test'],
                'guildhall_resolution_digest' => str_repeat('b', 64),
            ]],
            'disposition' => 'CAPABILITY_SLOTS_RESOLVED_PENDING_USE_AUTHORIZATION',
            'final_personnel_disposition' => true,
            'personnel_use_request_authority' => true,
            'reservation_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ];
        $disposition['record_digest'] = hash('sha256', CanonicalJson::encode($disposition));
        $directory = $root.'/var/imperium/offices/guildhall/personnel-use-dispositions';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$dispositionId.'.json', json_encode($disposition, JSON_THROW_ON_ERROR));
        return [$root, $store, $dispositionId];
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
