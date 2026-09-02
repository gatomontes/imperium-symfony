<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer, TransitionContract, TransitionReconstructor};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/ExecutableTransitionBatch1Test.php';

final class ExecutableTransitionBatch7Test extends TestCase
{
    public static function substitutions(): iterable
    {
        foreach (TransitionContract::WRITE_SET as $field) { yield $field => [$field]; }
    }

    #[DataProvider('substitutions')]
    public function testResealedSubstitutionInEveryCommittedRecordRefuses(string $field): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            [$store, $custody, $pin] = $this->seed($directory);
            $commit = (new TransitionConsumer($store, $custody, static fn () => 150))->execute($pin);
            $commit['records'][$field]['unapproved'] = true;
            file_put_contents($directory.'/commit.json', json_encode(['body' => $commit, 'digest' => TransitionContract::digest($commit)], JSON_THROW_ON_ERROR));
            self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new TransitionReconstructor($store, $pin))->reconstruct()['status']);
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public function testRequestSubstitutionWritesNothingAndGrantSubstitutionRefuses(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            [$store, $custody, $pin] = $this->seed($directory);
            try { (new TransitionConsumer($store, $custody, static fn () => 150))->execute(str_repeat('0', 64)); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_REQUEST_SUBSTITUTION', $e->getMessage()); }
            self::assertNull($store->read('journal'));
            $grant = $store->read('grant'); $grant['successor'] = hash('sha256', 'changed');
            file_put_contents($directory.'/grant.json', json_encode(['body' => $grant, 'digest' => TransitionContract::digest($grant)], JSON_THROW_ON_ERROR));
            self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new TransitionReconstructor($store, $pin))->reconstruct()['status']);
            try { $custody->issue(); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_GRANT_NOT_TRUSTED', $e->getMessage()); }
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public function testClockIsRecheckedAfterJournalAndCannotBeSuppliedByExecuteRequest(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            [$store, $custody, $pin] = $this->seed($directory); $tick = 0;
            $consumer = new TransitionConsumer($store, $custody, static function () use (&$tick): int { return ++$tick === 1 ? 199 : 200; });
            try { $consumer->execute($pin); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_AUTHORITY_NOT_CURRENT', $e->getMessage()); }
            self::assertNull($store->read('commit'));
            self::assertSame('INCOMPLETE', (new TransitionReconstructor($store, $pin))->reconstruct()['status']);
            self::assertCount(1, (new \ReflectionMethod(TransitionConsumer::class, 'execute'))->getParameters());
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public function testCommittedAuthorityCannotBeRevokedOrReissued(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            [$store, $custody, $pin] = $this->seed($directory);
            (new TransitionConsumer($store, $custody, static fn () => 150))->execute($pin);
            try { $custody->revoke(); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_COMMIT_PRECLUDES_REVOCATION', $e->getMessage()); }
            try { $custody->issue(); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_ISSUANCE_AFTER_ATTEMPT_REFUSED', $e->getMessage()); }
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    private function seed(string $directory): array
    {
        $grant = ExecutableTransitionBatch1Test::grant($directory); $pin = TransitionContract::digest($grant);
        $store = new TransitionStore($directory); $store->locked(fn () => $store->put('grant', $grant));
        $custody = new TransitionAuthority($store, $pin, static fn () => 150); $custody->issue();
        return [$store, $custody, $pin];
    }

    public function testCopyingPinnedAuthorityToAnotherStorageRootRefuses(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        $other = $directory.'-other'; mkdir($other);
        try {
            [$store, , $pin] = $this->seed($directory);
            $copy = new TransitionStore($other);
            $copy->locked(fn () => $copy->put('grant', $store->read('grant')));
            $copy->locked(fn () => $copy->put('authority', $store->read('authority')));
            try { (new TransitionConsumer($copy, new TransitionAuthority($copy, $pin), static fn () => 150))->execute($pin); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_STORAGE_ROOT_SUBSTITUTION', $e->getMessage()); }
            self::assertNull($copy->read('journal'));
        } finally {
            foreach ([$directory, $other] as $root) { foreach (glob($root.'/*') as $file) { unlink($file); } rmdir($root); }
        }
    }
}
