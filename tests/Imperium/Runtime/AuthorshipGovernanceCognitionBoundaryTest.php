<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\AuthorshipGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorshipGovernanceCognitionBoundaryTest extends TestCase
{
    #[DataProvider('offices')]
    public function testAcceptedCommissionCreatesOneOfficeSpecificNativeAuthority(string $office, string $type, string $seat, string $subordinate): void
    {
        $root = sys_get_temp_dir().'/imperium-authorship-governance-'.bin2hex(random_bytes(5));
        try {
            $acceptanceId = $office.'-acceptance-'.str_repeat('a', 20);
            $commissionId = 'authorship-'.$office.'-'.str_repeat('b', 20);
            $bindingId = $office.'-binding-'.str_repeat('c', 20);
            $commission = $this->seal(['commission_id' => $commissionId, 'target_seat' => $seat, 'authorship_authority' => true, 'execution_authority' => false]);
            $occupancy = $this->seal(['binding_id' => $bindingId, 'seat' => $seat, 'status' => 'ACTIVE', 'subordinate_staff_resolution_authority' => true, 'execution_authority' => false]);
            $acceptance = $this->seal([
                'acceptance_id' => $acceptanceId, 'instance_id' => 'imperium-test', 'office' => $office,
                'production_case_id' => 'production-case-test', 'production_case_digest' => str_repeat('d', 64),
                'commission_id' => $commissionId, 'commission_digest' => $commission['record_digest'],
                'binding_id' => $bindingId, 'binding_digest' => $occupancy['record_digest'],
                'disposition' => 'ACCEPTED_FOR_RESIDENT_AUTHORSHIP', 'recipient_acceptance' => true,
                'authorship_authority_exercisable' => true, 'subordinate_staff_resolution_authority' => true,
                'subordinate_staff_resolution_pending' => true, 'subordinate_staff_class' => $subordinate, 'execution_authority' => false,
            ]);
            $this->write($root, $office.'/inbox', $commissionId, $commission);
            $this->write($root, $office.'/occupancy', $bindingId, $occupancy);
            $this->write($root, $office.'/acceptances', $acceptanceId, $acceptance);

            $resolver = new AuthorshipGovernanceCognitionAuthorityResolver($root);
            $authority = $resolver->resolve('resident-requirements', $type, $acceptanceId);
            self::assertSame($seat, $authority['seat']);
            self::assertSame(hash('sha256', CanonicalJson::encode([$acceptance, $commission, $occupancy])), $authority['input_digest']);
            self::assertFalse($authority['consumed']);
            $otherType = 'hagiography' === $office ? 'studium-subordinate-requirements' : 'hagiography-subordinate-requirements';
            try { $resolver->resolve('resident-requirements', $otherType, $acceptanceId); self::fail('Expected cross-office authority refusal.'); }
            catch (\RuntimeException $exception) { self::assertSame('GCA501_AUTHORSHIP_GOVERNANCE_AUTHORITY_ABSENT', $exception->getMessage()); }

            $resolution = $this->seal(['resolution_id' => $office.'-subordinate-resolution-'.str_repeat('e', 20), 'acceptance_id' => $acceptanceId]);
            $this->write($root, $office.'/subordinate-resolutions', $resolution['resolution_id'], $resolution);
            self::assertTrue($resolver->resolve('resident-requirements', $type, $acceptanceId)['consumed']);
        } finally { $this->remove($root); }
    }

    public function testResidentGatewayContainsNoDirectAgentOrCredentialPath(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Authorship/SymfonyAiAuthorshipSubordinateCognitionGateway.php');
        foreach (['AgentInterface', 'Autowire(service:', 'DEEPSEEK_API_KEY', '$sanctographer', '$chancellor'] as $forbidden) { self::assertStringNotContainsString($forbidden, $source); }
        self::assertStringContainsString('GovernanceCognitionInvoker', $source);
    }

    public static function offices(): iterable
    {
        yield 'Hagiography' => ['hagiography', 'hagiography-subordinate-requirements', 'hagiography.sanctographer', 'Chronicler'];
        yield 'Studium' => ['studium', 'studium-subordinate-requirements', 'studium.chancellor', 'Notary'];
    }

    private function seal(array $record): array { $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
    private function write(string $root, string $directory, string $id, array $record): void { $path = $root.'/var/imperium/offices/'.$directory; if (!is_dir($path)) { mkdir($path, 0770, true); } file_put_contents($path.'/'.$id.'.json', json_encode($record, JSON_THROW_ON_ERROR)); }
    private function remove(string $path): void { if (!is_dir($path)) { return; } foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->remove($child) : unlink($child); } rmdir($path); }
}
