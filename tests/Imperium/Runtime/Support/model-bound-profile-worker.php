<?php
declare(strict_types=1);
require dirname(__DIR__, 4).'/vendor/autoload.php';

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationSignatures, FormationInstitution, FormationPersonnel, FormationProfileDesignationInitialization};
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture, SyntheticFormationClock};

class_exists(CitadelFormationFixture::class);
[$script, $root, $inputPath] = $argv;
$input = json_decode((string) file_get_contents($inputPath), true, 512, JSON_THROW_ON_ERROR);
$clock = new SyntheticFormationClock(); $clock->at = $input['at'];
$journal = new FormationJournal($root);
$signatures = new FormationSignatures($journal, $clock);
try {
    if ($input['operation'] === 'initialize') {
        (new FormationProfileDesignationInitialization($journal, $signatures))->initialize($input['head'], $input['decision']);
    } else {
        $personnel = new FormationPersonnel($journal, $signatures, $clock, new FormationInstitution($root));
        $journal->inspect(fn(array $frame): array => $personnel->candidate($frame['state'], $input['candidate'], $frame['state']['citadel_id'], $input['seat']));
    }
    echo "VERIFIED_COMPONENT_ONLY\n";
} catch (\Throwable $e) {
    echo 'REFUSED '.$e->getMessage()."\n";
    exit(1);
}
