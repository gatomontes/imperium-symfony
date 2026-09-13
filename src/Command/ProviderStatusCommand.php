<?php
declare(strict_types=1);
namespace App\Command;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'imperium:provider:status', description: 'Inspect retained provider onboarding progress.')]
final class ProviderStatusCommand extends ProviderOnboardingCommand
{
    protected function operation(): string { return 'status'; }
}
