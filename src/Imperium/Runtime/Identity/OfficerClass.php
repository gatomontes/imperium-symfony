<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Identity;

enum OfficerClass: string
{
    case Legate = 'LEGATE';
    case Delegate = 'DELEGATE';

    public function isPermanent(): bool
    {
        return self::Legate === $this;
    }

    public function isCommissionBound(): bool
    {
        return self::Delegate === $this;
    }
}
