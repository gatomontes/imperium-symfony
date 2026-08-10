<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

enum OutboundExecutionMode: string
{
    case Deterministic = 'deterministic';
    case Sortie = 'sortie';
}
