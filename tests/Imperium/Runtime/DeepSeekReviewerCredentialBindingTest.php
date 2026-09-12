<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\DeepSeek\{KeySource, Runtime};
use App\Tests\Imperium\Runtime\Support\DeepSeekFixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\{MockHttpClient, Response\MockResponse};

/** Reviewer regression: use only public construction and synthetic offline custody. */
final class DeepSeekReviewerCredentialBindingTest extends TestCase
{
    public function testDeliverySourceMustMatchTheAdmittedCredentialBinding(): void
    {
        $fixture = new DeepSeekFixture();
        try {
            $fixture->ready();
            $foreign = new class implements KeySource {
                public function generation(): string { return 'foreign-generation'; }
                public function withKey(callable $delivery): void
                {
                    $delivery('synthetic-foreign-review-key');
                }
            };
            $dispatches = 0;
            $foreignKeyUsed = false;
            $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$dispatches, &$foreignKeyUsed): MockResponse {
                ++$dispatches;
                // Record only a boolean, never an authentication value.
                $foreignKeyUsed = in_array('Authorization: Bearer synthetic-foreign-review-key', $options['headers'], true);
                return new MockResponse(DeepSeekFixture::listing());
            });
            $tick = 100;
            try {
                $runtime = new Runtime(
                $fixture->f->store,
                $fixture->adapter, // Validates synthetic-generation in the signed original.
                $foreign,          // Delivers a different, unapproved generation.
                $fixture->envelopes,
                $http,
                static function () use (&$tick): int { return $tick++; },
                );
                $runtime->advance($fixture->f::json($fixture->request('access')));
            } catch (\RuntimeException) {
                // Refusal is allowed; the effect and settlement must still be absent.
            }
            $claims = $fixture->f->store->journal->read()['state']['onboarding']['claims'];
            $settled = false;
            foreach ($claims as $claim) {
                $settled = $settled || $claim['settled'] !== null;
            }
            self::assertSame(
                ['dispatches' => 0, 'foreign_key_used' => false, 'settled' => false],
                ['dispatches' => $dispatches, 'foreign_key_used' => $foreignKeyUsed, 'settled' => $settled],
                'The actual delivery source must be bound to the credential original validated by the adapter.',
            );
        } finally {
            $fixture->close();
        }
    }
}
