<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Imperium\Runtime\LaCortine\{CredentialBroker, CredentialCapability};

final class NoEffectCredentials implements CredentialBroker
{
    public int $calls = 0;
    public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability
    { ++$this->calls; throw new \LogicException('Credential issue reached'); }
    public function consume(CredentialCapability $capability, callable $providerOperation): mixed
    { ++$this->calls; throw new \LogicException('Credential consume reached'); }
}
