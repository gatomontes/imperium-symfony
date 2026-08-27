<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ContinuousGovernanceRevocationRecordSchemaTest extends TestCase
{
    public function testSchemasKeepJudgmentSeparateFromSingleUseEnforcement(): void
    {
        $root = dirname(__DIR__, 3).'/contracts/';
        $disposition = json_decode((string) file_get_contents($root.'continuous-governance-revocation-disposition.schema.json'), true, 64, JSON_THROW_ON_ERROR);
        $authority = json_decode((string) file_get_contents($root.'continuous-governance-enforcement-authority.schema.json'), true, 64, JSON_THROW_ON_ERROR);
        $result = json_decode((string) file_get_contents($root.'continuous-governance-enforcement-result.schema.json'), true, 64, JSON_THROW_ON_ERROR);
        $leaseResult = json_decode((string) file_get_contents($root.'continuous-governance-lease-enforcement-result.schema.json'), true, 64, JSON_THROW_ON_ERROR);
        self::assertSame(false, $disposition['properties']['enforcement_authority_opened']['const']);
        self::assertSame(false, $disposition['properties']['state_mutated']['const']);
        self::assertSame(false, $disposition['properties']['authority_granted']['const']);
        self::assertContains('record_digest', $disposition['required']);
        self::assertSame('^[a-f0-9]{64}$', $disposition['properties']['record_digest']['pattern']);
        self::assertSame(true, $authority['properties']['single_use']['const']);
        self::assertContains('issuer', $authority['required']);
        self::assertContains('record_digest', $authority['required']);
        self::assertSame(false, $authority['properties']['consumed']['const']);
        self::assertSame(false, $authority['properties']['continuing_authority']['const']);
        self::assertSame(false, $authority['properties']['perimeter_authority']['const']);
        self::assertSame('^[a-f0-9]{64}$', $authority['properties']['record_digest']['pattern']);
        self::assertSame(true, $result['properties']['authority_consumed']['const']);
        self::assertSame(false, $result['properties']['journal_created']['const']);
        self::assertSame(false, $result['properties']['external_io_started']['const']);
        self::assertSame(false, $result['properties']['propagation_performed']['const']);
        self::assertSame(true, $leaseResult['properties']['authority_consumed']['const']);
        self::assertSame(false, $leaseResult['properties']['claim_created']['const']);
        self::assertSame(false, $leaseResult['properties']['lease_consumed']['const']);
        self::assertSame(false, $leaseResult['properties']['lease_closed']['const']);
        self::assertSame(false, $leaseResult['properties']['propagation_performed']['const']);
    }
}
