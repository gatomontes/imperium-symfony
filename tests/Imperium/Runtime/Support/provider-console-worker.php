<?php
declare(strict_types=1);

require dirname(__DIR__,4).'/vendor/autoload.php';

use App\Command\ProviderStatusCommand;
use App\Imperium\Runtime\Onboarding\Console\DeploymentGateway;
use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use Symfony\Component\Console\Application;

$root = realpath($argv[1] ?? '');
$temporary = realpath(sys_get_temp_dir());
if ($root === false || $temporary === false || !str_starts_with(strtolower($root),strtolower($temporary.DIRECTORY_SEPARATOR).'imperium-o2-test-')) { exit(91); }
$now = filter_var($argv[2] ?? '',FILTER_VALIDATE_INT);
if (!is_int($now) || $now <= 0) { exit(92); }
$clock = new class($now) implements \App\Imperium\Runtime\Clock {
    public function __construct(private int $now) {}
    public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->now); }
};
// Reconstruct the deployment reader in a fresh process, without any key or adapter.
$app = new Application();
$app->addCommand(new ProviderStatusCommand(new DeploymentGateway(new FormationJournal($root),$clock)));
$input = new \Symfony\Component\Console\Input\ArrayInput(['command'=>'imperium:provider:status','sequence-id'=>'sequence-test','--format'=>'json']);
$app->run($input);
