<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Command\CitadelIntakeCommand;
use App\Imperium\Runtime\Citadel\Formation\{CitadelIntakeService, FormationJournal};
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$root = realpath($argv[1] ?? '');
$prefix = str_replace('\\', '/', (string) realpath(sys_get_temp_dir())).'/imperium-citadel-proof-';
if ($root === false || !str_starts_with(str_replace('\\', '/', $root), $prefix)) { exit(2); }
$container = new ContainerBuilder();
$container->register(FormationJournal::class)->setArguments([$root]);
$container->register(CitadelIntakeService::class)->setAutowired(true);
$container->register(CitadelIntakeCommand::class)->setAutowired(true)->setPublic(true);
$container->compile();
$command = new CommandTester($container->get(CitadelIntakeCommand::class));
$status = $command->execute(['submission-id' => 'concurrent-intake-0001', 'request-file' => $root.'/concurrent-request.txt']);
echo $command->getDisplay();
exit($status);
