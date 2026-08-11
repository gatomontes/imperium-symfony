<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\AgentMailWebhookVerifier;
use PHPUnit\Framework\TestCase;

final class AgentMailWebhookVerifierTest extends TestCase
{
    public function testVerifiesPublishedSvixSignatureVector(): void
    {
        $verifier = new AgentMailWebhookVerifier('whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw');
        $body = '{"test": 2432232314}';

        self::assertSame('msg_p5jXN8AQM9LWM0D4loKWxJek', $verifier->verify($body, [
            'svix-id' => 'msg_p5jXN8AQM9LWM0D4loKWxJek',
            'svix-timestamp' => '1614265330',
            'svix-signature' => 'v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=',
        ], 1614265330));
    }

    public function testRejectsTamperedRawBody(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AGENTMAIL_WEBHOOK_SIGNATURE_INVALID');

        (new AgentMailWebhookVerifier('whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw'))->verify('{"test": 2432232315}', [
            'svix-id' => 'msg_p5jXN8AQM9LWM0D4loKWxJek',
            'svix-timestamp' => '1614265330',
            'svix-signature' => 'v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=',
        ], 1614265330);
    }

    public function testRejectsStaleSignedRequest(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AGENTMAIL_WEBHOOK_TIMESTAMP_REJECTED');

        (new AgentMailWebhookVerifier('whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw'))->verify('{"test": 2432232314}', [
            'svix-id' => 'msg_p5jXN8AQM9LWM0D4loKWxJek',
            'svix-timestamp' => '1614265330',
            'svix-signature' => 'v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=',
        ], 1614265631);
    }
}
