<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\AuthenticatedMissionAuthorizationBridge;
use App\Tests\Imperium\Runtime\Support\CanonicalMissionAuthorizationFixture;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionAuthenticityBatch1Test extends TestCase
{
    public function testExactPersistedMissionAuthorizationAndApprovalLineageAreAuthenticated(): void
    {
        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $accepted = (new AuthenticatedMissionAuthorizationBridge($root))->authenticate(
                $fixture['authorizationId'], new \DateTimeImmutable('2026-09-04T12:02:00+00:00'),
            );

            self::assertSame($fixture['authorizationId'], $accepted->authorizationId);
            self::assertSame($fixture['authorization']['record_digest'], $accepted->authorizationDigest);
            self::assertSame($fixture['dossier']['record_digest'], $accepted->dossierDigest);
            self::assertSame($fixture['review']['record_digest'], $accepted->reviewDigest);
            self::assertSame('imperator-development-root', $accepted->operatorIdentity);
            self::assertSame($fixture['mission']['mission_id'], $accepted->mission->id());
            self::assertSame(hash('sha256', \App\Bootstrap\CanonicalJson::encode($fixture['mission'])), $accepted->mission->digest());
        } finally { $this->remove($root); }
    }

    public function testFabricatedActorApprovalAndMutatedPlanFailBeforeRuntimeMutation(): void
    {
        foreach ([
            ['actor' => ['kind' => 'consumer', 'id' => 'self-appointed'], 'expected' => 'MIS403_MISSION_AUTHORIZATION_LINEAGE_INVALID'],
            ['mission' => ['target_commit' => str_repeat('f', 39)], 'expected' => 'MIS400_CANONICAL_MISSION_PLAN_INVALID'],
            ['mission' => ['inspection_paths' => ['../secrets']], 'expected' => 'MIS400_CANONICAL_MISSION_PLAN_INVALID'],
            ['forged_signature' => true, 'expected' => 'MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED'],
        ] as $case) {
            $root = $this->root();
            try {
                $expected = $case['expected']; unset($case['expected']);
                $fixture = CanonicalMissionAuthorizationFixture::persist($root, $case);
                $before = $this->files($root);
                $this->fails($expected, fn () => (new AuthenticatedMissionAuthorizationBridge($root))->authenticate(
                    $fixture['authorizationId'], new \DateTimeImmutable('2026-09-04T12:02:00+00:00'),
                ));
                self::assertSame($before, $this->files($root));
            } finally { $this->remove($root); }
        }
    }

    public function testTamperingExpiryRevocationAndSupersessionFailClosed(): void
    {
        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $path = $root.'/var/imperium/authorizations/missions/'.$fixture['authorizationId'].'.json';
            $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $record['mission_plan']['canonical_mission']['target_commit'] = str_repeat('f', 40);
            file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
            $this->fails('MIS402_MISSION_AUTHORIZATION_TAMPERED', fn () => (new AuthenticatedMissionAuthorizationBridge($root))->authenticate(
                $fixture['authorizationId'], new \DateTimeImmutable('2026-09-04T12:02:00+00:00'),
            ));
        } finally { $this->remove($root); }

        foreach (['revoked', 'superseded', 'expired'] as $flag) {
            $root = $this->root();
            try {
                $fixture = CanonicalMissionAuthorizationFixture::persist($root);
                $path = $root.'/var/imperium/authorizations/missions/'.$fixture['authorizationId'].'.json';
                $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                unset($record['record_digest']); $record[$flag] = true;
                $record['record_digest'] = hash('sha256', \App\Bootstrap\CanonicalJson::encode($record));
                file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
                $this->fails('MIS405_MISSION_AUTHORIZATION_INACTIVE', fn () => (new AuthenticatedMissionAuthorizationBridge($root))->authenticate(
                    $fixture['authorizationId'], new \DateTimeImmutable('2026-09-04T12:02:00+00:00'),
                ));
            } finally { $this->remove($root); }
        }

        $root = $this->root();
        try {
            $fixture = CanonicalMissionAuthorizationFixture::persist($root);
            $this->fails('MIS404_MISSION_AUTHORIZATION_TIME_INVALID', fn () => (new AuthenticatedMissionAuthorizationBridge($root))->authenticate(
                $fixture['authorizationId'], new \DateTimeImmutable('2026-09-04T13:00:00+00:00'),
            ));
        } finally { $this->remove($root); }
    }

    private function root(): string
    {
        return sys_get_temp_dir().'/imperium-canonical-auth-b1-'.bin2hex(random_bytes(8));
    }

    private function files(string $root): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) { if ($file->isFile()) { $files[] = $file->getPathname(); } }
        sort($files);
        return $files;
    }

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
