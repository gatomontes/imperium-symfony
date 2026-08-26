<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Clavium\ClaimBoundCredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use PHPUnit\Framework\TestCase;

final class ClaimBoundCredentialBrokerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-claim-bound-credential-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testExactDurableClaimPermitsOneBrokeredCredentialCallback(): void
    {
        $claim = $this->claim();
        $credentials = $this->credentials();
        $result = (new ClaimBoundCredentialBroker($this->root, $credentials))->consume(
            $claim,
            new \DateTimeImmutable('2026-08-25T13:00:00+00:00'),
            static fn (mixed $secret): string => 'received-'.$secret,
        );

        self::assertSame('received-sterile-test-secret', $result);
        self::assertSame(DeepSeekDelegatePlatformAdapter::CREDENTIAL_REFERENCE, $credentials->issued[0]);
        self::assertSame($claim['claim_id'], $credentials->issued[1]);
        self::assertSame(DeepSeekDelegatePlatformAdapter::OPERATION, $credentials->issued[2]);
    }

    public function testUnpersistedClaimCannotIssueCredentialCapability(): void
    {
        $credentials = $this->credentials();
        $claim = $this->claim(false);

        try {
            (new ClaimBoundCredentialBroker($this->root, $credentials))->consume(
                $claim,
                new \DateTimeImmutable('2026-08-25T13:00:00+00:00'),
                static fn (): never => throw new \LogicException('Callback must not run.'),
            );
            self::fail('Expected the absent claim to fail stopped.');
        } catch (\RuntimeException $exception) {
            self::assertSame('CLV430_CREDENTIAL_GRANT_INVALID', $exception->getMessage());
        }

        self::assertSame([], $credentials->issued);
    }

    public function testChangedOrExpiredClaimCannotIssueCredentialCapability(): void
    {
        $claim = $this->claim();
        foreach (['changed', 'expired'] as $case) {
            $credentials = $this->credentials();
            $presented = $claim;
            if ('changed' === $case) {
                $presented['target']['commission_id'] = 'substituted';
            }
            $at = new \DateTimeImmutable('expired' === $case ? '2026-08-25T14:00:00+00:00' : '2026-08-25T13:00:00+00:00');
            try {
                (new ClaimBoundCredentialBroker($this->root, $credentials))->consume(
                    $presented,
                    $at,
                    static fn (): never => throw new \LogicException('Callback must not run.'),
                );
                self::fail('Expected '.$case.' claim to fail stopped.');
            } catch (\RuntimeException $exception) {
                self::assertSame('CLV430_CREDENTIAL_GRANT_INVALID', $exception->getMessage());
            }
            self::assertSame([], $credentials->issued);
        }
    }

    private function credentials(): CredentialBroker
    {
        return new class implements CredentialBroker {
            public array $issued = [];

            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
            {
                $this->issued = [$credentialRef, $commissionId, $operation, $expiresAt->format(DATE_ATOM)];

                return new CredentialCapability('capability', $credentialRef, $commissionId, $operation, $expiresAt, $maxUses);
            }

            public function consume(CredentialCapability $capability, callable $providerOperation): mixed
            {
                return $providerOperation('sterile-test-secret');
            }
        };
    }

    private function claim(bool $persist = true): array
    {
        $id = 'provider-invocation-'.str_repeat('a', 20);
        $claim = [
            'schema' => 'imperium.clavium-provider-invocation-claim/v1',
            'claim_id' => $id,
            'model' => ['runtime_binding' => [
                'provider' => DeepSeekDelegatePlatformAdapter::PROVIDER,
                'platform_service' => DeepSeekDelegatePlatformAdapter::PLATFORM_SERVICE,
                'runtime_model' => DeepSeekDelegatePlatformAdapter::RUNTIME_MODEL,
            ]],
            'target' => ['commission_id' => 'commission'],
            'lease_consumption' => ['lease_id' => 'lease', 'consumed' => true, 'expires_at' => '2026-08-25T14:00:00+00:00', 'continuing_authority' => false],
            'turn_authority_consumption' => ['authority_id' => 'turn', 'consumed' => true, 'continuing_authority' => false],
            'provider_request' => ['idempotency_key' => 'imperium-'.$id, 'external_io_started' => false],
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
            'credential_material_present' => false,
        ];
        $claim['record_digest'] = hash('sha256', CanonicalJson::encode($claim));
        if ($persist) {
            $path = $this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json';
            mkdir(dirname($path), 0770, true);
            file_put_contents($path, json_encode($claim, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        }

        return $claim;
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
