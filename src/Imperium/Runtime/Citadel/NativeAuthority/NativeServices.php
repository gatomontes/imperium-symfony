<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;
use App\Imperium\Runtime\Citadel\Authority\{GarrisonAuthorityRequest, RecruiterEvidence};
use App\Imperium\Runtime\SystemClock;
final class NativeServices
{
    public static function protocol(string $root): NativeProtocol
    {
        $clock = new SystemClock();
        return new NativeProtocol(new NativeJournal($root), $clock, new GarrisonAuthorityRequest($clock), new RecruiterEvidence($root, $clock));
    }
}
