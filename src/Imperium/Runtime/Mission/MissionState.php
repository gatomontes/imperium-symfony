<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

enum MissionState: string
{
    case PROPOSED = 'PROPOSED';
    case AUTHORIZED = 'AUTHORIZED';
    case ADMITTED = 'ADMITTED';
    case EXECUTING = 'EXECUTING';
    case EVIDENCE_ASSEMBLED = 'EVIDENCE_ASSEMBLED';
    case COMPLETED = 'COMPLETED';
    case REFUSED = 'REFUSED';
    case ABORTED = 'ABORTED';
    case EXPIRED = 'EXPIRED';

    public function terminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::REFUSED, self::ABORTED, self::EXPIRED], true);
    }
}

