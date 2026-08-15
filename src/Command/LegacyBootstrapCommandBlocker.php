<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LegacyBootstrapCommandBlocker implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => "refuse"];
    }

    public function refuse(ConsoleCommandEvent $event): void
    {
        $name = $event->getCommand()?->getName();
        if (!is_string($name)) {
            return;
        }
        if (
            str_starts_with($name, "imperium:mastermason:") ||
            str_contains($name, "bootstrap-seed")
        ) {
            throw new \RuntimeException(
                "B242_LEGACY_PRIMORDIAL_BOOTSTRAP_RETIRED: generic v0 occupants replace self-constructing bootstrap personnel.",
            );
        }
    }
}
