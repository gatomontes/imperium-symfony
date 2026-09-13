<?php
declare(strict_types=1);
namespace App\Command;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'imperium:provider:resume', description: 'Recognize retained evidence without new dispatch.')]
final class ProviderResumeCommand extends ProviderOnboardingCommand
{
    protected function operation(): string { return 'resume'; }
}
