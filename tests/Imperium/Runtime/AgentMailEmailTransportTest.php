<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\AgentMailEmailTransport;
use PHPUnit\Framework\TestCase;

final class AgentMailEmailTransportTest extends TestCase
{
    public function testSupportsOnlyEmailSend(): void
    {
        $transport = new AgentMailEmailTransport();

        self::assertTrue($transport->supports('email.send'));
        self::assertFalse($transport->supports('http.post.json'));
    }

    public function testRejectsNonAgentMailDestinationBeforeNetworkAccess(): void
    {
        $transport = new AgentMailEmailTransport();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AGENTMAIL_DESTINATION_REJECTED');

        $transport->execute(
            'email.send',
            'https://example.com/v0/inboxes/inbox/messages/send',
            '{"to":["recipient@example.test"]}',
            'secret',
        );
    }

    public function testRejectsMissingRecipientBeforeNetworkAccess(): void
    {
        $transport = new AgentMailEmailTransport();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AGENTMAIL_PAYLOAD_INVALID');

        $transport->execute(
            'email.send',
            'https://api.agentmail.to/v0/inboxes/inbox/messages/send',
            '{"subject":"No recipient"}',
            'secret',
        );
    }

    public function testRejectsMissingCredentialBeforeNetworkAccess(): void
    {
        $transport = new AgentMailEmailTransport();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AGENTMAIL_AUTHENTICATION_UNAVAILABLE');

        $transport->execute(
            'email.send',
            'https://api.agentmail.to/v0/inboxes/inbox/messages/send',
            '{"to":["recipient@example.test"]}',
            '',
        );
    }
}
