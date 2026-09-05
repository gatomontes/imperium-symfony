<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\CanonicalMissionTransitionService;
use App\Imperium\Runtime\Mission\MissionCapability;
use App\Imperium\Runtime\Mission\MissionCapabilityIssuanceService;
use App\Tests\Imperium\Runtime\Support\CanonicalMissionAuthorizationFixture;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionAuthenticityBatch2Test extends TestCase
{
    public function testCapabilitiesBindTheCompleteAuthorityAndLifecycleTuple(): void
    {
        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $at = new \DateTimeImmutable('2026-09-04T12:02:00+00:00');
            $capabilities = (new MissionCapabilityIssuanceService($root))->issue($fixture['authorizationId'], $at);
            self::assertCount(3, $capabilities);

            $capability = $capabilities[0];
            $record = $capability->toArray();
            self::assertSame($fixture['authorizationId'], $record['authorization_id']);
            self::assertSame($fixture['authorization']['record_digest'], $record['authorization_digest']);
            self::assertSame($fixture['dossier']['record_digest'], $record['dossier_digest']);
            self::assertSame($fixture['mission']['mission_id'], $record['mission_id']);
            self::assertSame('admit', $record['action']);
            self::assertSame('mission-admission-controller', $record['actor']);
            self::assertSame('mission', $record['target']);
            self::assertSame(MissionCapabilityIssuanceService::ISSUER, $record['issuer']);
            self::assertSame('AUTHORIZED', $record['required_state']);
            self::assertSame('ADMITTED', $record['resulting_state']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $record['nonce']);
            self::assertSame($fixture['mission']['lifecycle_transitions'][0], (new CanonicalMissionTransitionService($root))->verify(
                $capability, $fixture['authorizationId'], $at,
            ));
        } finally { $this->remove($root); }
    }

    public function testAuthorityBearingServiceHasNoCallerSelectableVerifierOrConsumer(): void
    {
        $constructor = new \ReflectionMethod(CanonicalMissionTransitionService::class, '__construct');
        self::assertCount(1, $constructor->getParameters());
        self::assertSame('string', (string) $constructor->getParameters()[0]->getType());
        $verify = new \ReflectionMethod(CanonicalMissionTransitionService::class, 'verify');
        self::assertCount(3, $verify->getParameters());
        foreach ($verify->getParameters() as $parameter) {
            self::assertNotSame('MissionCapabilityVerifier', $parameter->getType()?->getName());
            self::assertNotSame('MissionCapabilityConsumer', $parameter->getType()?->getName());
        }
    }

    public function testMaliciousConsumerAndForgedBindingsCannotReachTheVerifiedCut(): void
    {
        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $at = new \DateTimeImmutable('2026-09-04T12:02:00+00:00');
            $capability = (new MissionCapabilityIssuanceService($root))->issue($fixture['authorizationId'], $at)[0];
            $forged = $capability->toArray();
            $forged['actor'] = 'malicious-consumer';
            $forgedCapability = MissionCapability::fromArray($forged);

            $this->fails('MIS412_CAPABILITY_FORGED', fn () => (new CanonicalMissionTransitionService($root))->verify(
                $forgedCapability, $fixture['authorizationId'], $at,
            ));
            self::assertDirectoryDoesNotExist($root.'/var/imperium/runtime/canonical-mission/states');
        } finally { $this->remove($root); }
    }

    public function testFabricatedAuthorizationFailsBeforeIssuerCustodyIsCreated(): void
    {
        $root = $this->root();
        try {
            $this->fails('MIS406_MISSION_AUTHORIZATION_RECORD_ABSENT', fn () => (new MissionCapabilityIssuanceService($root))->issue(
                'mission-authorization-'.str_repeat('f', 20), new \DateTimeImmutable('2026-09-04T12:02:00+00:00'),
            ));
            self::assertFileDoesNotExist($root.'/var/imperium/runtime/canonical-mission/capability-issuer.key');
        } finally { $this->remove($root); }
    }

    private function root(): string { return sys_get_temp_dir().'/imperium-canonical-auth-b2-'.bin2hex(random_bytes(8)); }

    private function fails(string $message, callable $call): void
    {
        try { $call(); self::fail('Expected '.$message); }
        catch (\RuntimeException $error) { self::assertSame($message, $error->getMessage()); }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
