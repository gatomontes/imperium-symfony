<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\NativeProfileOriginalCase;

final class NativeLocksmithOriginalBoundaryTest extends NativeProfileOriginalCase
{
    public function testLocksmithCompetentOriginalRefusalsAndSuccessorMapping(): void
    {
        $this->proveOriginalBoundary('clavium.locksmith');
    }
}
