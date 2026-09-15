<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationInstitution, FormationOwnerFrame};
try {
    $root = $argv[1];
    $actors = (new FormationJournal($root))->inspect(static function (array $frame, FormationOwnerFrame $owner) use ($root): array {
        $institution = new FormationInstitution($root); $result = [];
        foreach (FormationInstitution::SEATS as $role => $seat) { $result[$seat] = $institution->actorInOwner($owner, $role); }
        return $result;
    });
    echo json_encode(['actors' => $actors, 'current_at_locked_observation_only' => true], JSON_THROW_ON_ERROR);
    exit(0);
} catch (\Throwable $e) { fwrite(STDERR, get_class($e).': '.$e->getMessage()); exit(1); }
