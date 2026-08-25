<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Imperium\Runtime\Conscription\DelegateMissionOperationalTransitionCoordinator;

[$script, $root, $gate, $variant] = $argv;
while (!is_file($gate)) {
    usleep(1000);
}
$id = 'delegate-mission-operational-profile-qualification-'.str_repeat('a', 20);
$record = [
    'schema' => 'imperium.conscription-delegate-mission-operational-profile-qualification/v1',
    'qualification_id' => $id,
    'instance_id' => 'imperium-contention-test',
    'qualified_at' => '2026-08-25T16:00:0'.$variant.'+00:00',
    'status' => 'DELEGATE_MISSION_PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY',
];

try {
    (new DelegateMissionOperationalTransitionCoordinator($root))->commitQualification($id, $record);
    echo 'STORED';
} catch (\RuntimeException $exception) {
    echo $exception->getMessage();
}
