<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Audit;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @deprecated Use DelegateMissionOperationalEvidenceAuditService; this compatibility name makes no comprehensive lifecycle-audit claim. */
final readonly class DelegateMissionTerminalAuditService
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
    }

    public function audit(string $terminalId): array
    {
        return (new DelegateMissionOperationalEvidenceAuditService($this->root))->audit($terminalId);
    }
}
