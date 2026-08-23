<?php

declare(strict_types=1);

namespace App\Command;

use App\Imperium\Runtime\LaCortine\AgentMailEmailTransport;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\LaCortine\DeterministicBoundaryExecutor;
use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'imperium:email:send-agentmail', description: 'Send one exact pre-authorized email through the deterministic AgentMail boundary.')]
final class AgentMailEmailSendCommand extends Command
{
    public function __construct(private readonly CredentialBroker $credentialBroker)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Exact recipient email address.')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Exact subject line.')
            ->addOption('text', null, InputOption::VALUE_REQUIRED, 'Exact plain-text body.')
            ->addOption('attachment', null, InputOption::VALUE_REQUIRED, 'Optional path to one PDF attachment.')
            ->addOption('inbox', null, InputOption::VALUE_REQUIRED, 'AgentMail inbox ID; defaults to AGENTMAIL_INBOX_ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = trim((string) $input->getOption('to'));
        $subject = (string) $input->getOption('subject');
        $text = (string) $input->getOption('text');
        $inbox = trim((string) ($input->getOption('inbox') ?: ($_SERVER['AGENTMAIL_INBOX_ID'] ?? $_ENV['AGENTMAIL_INBOX_ID'] ?? getenv('AGENTMAIL_INBOX_ID'))));

        if ('' === $to || false === filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('REFUSED EMAIL_RECIPIENT_INVALID');
            return Command::FAILURE;
        }
        if ('' === $inbox || str_contains($inbox, '/')) {
            $output->writeln('REFUSED AGENTMAIL_INBOX_ID_INVALID');
            return Command::FAILURE;
        }

        $message = [
            'to' => [$to],
            'subject' => $subject,
            'text' => $text,
        ];

        $attachment = $input->getOption('attachment');
        if (is_string($attachment) && '' !== trim($attachment)) {
            $path = $attachment;
            if (!is_file($path) || !is_readable($path)) {
                $output->writeln('REFUSED EMAIL_ATTACHMENT_UNREADABLE');
                return Command::FAILURE;
            }
            $bytes = file_get_contents($path);
            if (false === $bytes) {
                $output->writeln('REFUSED EMAIL_ATTACHMENT_UNREADABLE');
                return Command::FAILURE;
            }
            if ('pdf' !== strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
                $output->writeln('REFUSED EMAIL_ATTACHMENT_NOT_PDF');
                return Command::FAILURE;
            }
            $message['attachments'] = [[
                'content' => base64_encode($bytes),
                'filename' => basename($path),
                'content_type' => 'application/pdf',
            ]];
        }

        $payload = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $destination = 'https://api.agentmail.to/v0/inboxes/'.rawurlencode($inbox).'/messages/send';
        $now = new \DateTimeImmutable();
        $commissionId = 'commission.email-send.'.bin2hex(random_bytes(8));
        $credential = $this->credentialBroker->issue(
            'env:AGENTMAIL_API_KEY',
            $commissionId,
            'email.send',
            $now->modify('+2 minutes'),
        );

        $request = new OutboundRequest(
            'request.email-send.'.bin2hex(random_bytes(8)),
            'authorization.email-send.'.bin2hex(random_bytes(8)),
            hash('sha256', $payload),
            $commissionId,
            'email.send',
            'Send exact prepared email through AgentMail',
            OutboundExecutionMode::Deterministic,
            [$destination],
            ['email.send'],
            [$credential->capabilityId],
            hash('sha256', $payload),
            'agentmail-message-receipt/v1',
            $now->modify('+2 minutes'),
        );

        try {
            $artifact = (new DeterministicBoundaryExecutor(new IronGate(), $this->credentialBroker, new Lazaretto()))
                ->execute($request, $payload, $credential, new AgentMailEmailTransport(), $now);
        } catch (\Throwable $e) {
            $output->writeln('REFUSED '.$e->getMessage());
            return Command::FAILURE;
        }

        $output->writeln('ADMITTED');
        $output->writeln('AGENTMAIL_EMAIL_SEND_OK');
        $output->writeln('destination='.$destination);
        $output->writeln('recipient='.$to);
        $output->writeln('artifact='.$artifact->artifactId);
        $output->writeln('sortie=NONE');
        $output->writeln('receipt.sha256='.$artifact->rawPayloadDigest);
        $output->writeln('receipt='.$artifact->content);

        return Command::SUCCESS;
    }
}
