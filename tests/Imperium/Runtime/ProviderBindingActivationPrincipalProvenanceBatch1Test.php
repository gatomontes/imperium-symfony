<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorPrincipalConstitutionAuthorityContract;
use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleDispositionContract;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationPrincipalProvenanceBatch1Test extends TestCase
{
    public function testContractsAreSeparateVersionedAndAuthorityEmpty(): void
    {
        self::assertNotSame(ImperatorPrincipalConstitutionAuthorityContract::SCHEMA, ImperatorRuntimePrincipalVersionContract::SCHEMA);
        self::assertNotSame(ImperatorRuntimePrincipalVersionContract::SCHEMA, ImperatorPrincipalLifecycleDispositionContract::SCHEMA);
        self::assertSame(['FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'EXISTING_INSTANCE_REMEDIATION'], ImperatorPrincipalConstitutionAuthorityContract::ROUTES);
        self::assertSame(['CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL', 'REMEDIATE_MISSING_IMPERATOR_PRINCIPAL'], ImperatorPrincipalConstitutionAuthorityContract::TRANSITIONS);
        foreach (['PENDING_ACTIVATION', 'ACTIVE', 'SUSPENDED', 'SUPERSEDED', 'REVOKED', 'EXPIRED', 'RETIRED'] as $status) self::assertContains($status, ImperatorRuntimePrincipalVersionContract::STATUSES);
        foreach (['ACTIVATE', 'RENEW', 'SUSPEND', 'SUPERSEDE', 'REVOKE', 'EXPIRE', 'RETIRE'] as $disposition) self::assertContains($disposition, ImperatorPrincipalLifecycleDispositionContract::DISPOSITIONS);
        foreach ([ImperatorPrincipalConstitutionAuthorityContract::NON_AUTHORITIES, ImperatorRuntimePrincipalVersionContract::NON_AUTHORITIES, ImperatorPrincipalLifecycleDispositionContract::NON_AUTHORITIES] as $posture) foreach ($posture as $permission) self::assertFalse($permission);
        foreach (ImperatorRuntimePrincipalVersionContract::SECRET_EXCLUSION as $permission) self::assertFalse($permission);
    }

    public function testDocumentationAuthorizesValidatorsAndStoresOnly(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-principal-provenance-remediation-batch-1-complete.md');
        foreach (['Only Batch 2 is authorized', 'fail-closed validators', 'canonical immutable stores', 'may not implement an authority issuer', 'principal producer', 'current-state index', 'recovery service', 'reconstruction service', 'runtime consumer', 'reconsider corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
