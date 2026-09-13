<?php
declare(strict_types=1);
namespace App\Command;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'imperium:provider:onboard', description: 'Preview or advance one provider onboarding request.')]
final class ProviderOnboardCommand extends ProviderOnboardingCommand
{
    protected function operation(): string { return 'onboard'; }
}
