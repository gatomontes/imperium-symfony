<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\TransitionContract as Contract;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

final class ExecutableTransitionBatch1Test extends TestCase
{
    public static function grant(): array
    {
        $grant = ['schema' => Contract::SCHEMA];
        foreach (['instance', 'principal', 'principal_activation', 'binding', 'binding_digest', 'successor',
            'successor_digest', 'successor_creation', 'assurance', 'execution_boundary', 'operation'] as $field) {
            $grant[$field] = hash('sha256', 'disposable-'.$field);
        }
        $grant['generation'] = 1;
        $grant['scope'] = Contract::SCOPE;
        $grant['effective_at'] = 100;
        $grant['expires_at'] = 200;
        return $grant;
    }

    public function testExactGrantAndStableContentionRoot(): void
    {
        $grant = self::grant();
        Contract::grant($grant, Contract::digest($grant));
        $changed = $grant;
        $changed['successor'] = hash('sha256', 'other');
        self::assertSame(Contract::root($grant), Contract::root($changed));
        self::assertNotSame(Contract::digest($grant), Contract::digest($changed));
        self::assertCount(7, Contract::WRITE_SET);
    }

    public function testChangedGrantCannotUseAnExistingPin(): void
    {
        $grant = self::grant(); $pin = Contract::digest($grant);
        $grant['generation'] = 2;
        $this->expectExceptionMessage('EAT_GRANT_NOT_TRUSTED');
        Contract::grant($grant, $pin);
    }

    public function testExpiryIsExclusive(): void
    {
        Contract::current(self::grant(), 100);
        Contract::current(self::grant(), 199);
        $this->expectExceptionMessage('EAT_AUTHORITY_NOT_CURRENT');
        Contract::current(self::grant(), 200);
    }

    public function testUnknownFieldsAreExcludedEvenWithMatchingPin(): void
    {
        $grant = self::grant(); $grant['payload'] = ['unapproved' => 'data'];
        $this->expectExceptionMessage('EAT_UNEXPECTED_FIELDS');
        Contract::grant($grant, Contract::digest($grant));
    }
}
