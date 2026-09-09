<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Command\{CitadelFormationCommand, CitadelIntakeCommand, CitadelPreparationCommand};
use App\Imperium\Runtime\Citadel\Formation\{CitadelIntakeService, CuriaFormationService, FormationPersonnel, FormationSignatures, FormationPreparation};
use App\Imperium\Runtime\Clock;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/** Actual Console name/alias resolution, with only offline infrastructure substituted. */
final class CourtyardApplication
{
    public static function build(FormationCustodyFixture $c, Clock $clock): Application
    {
        $app=new Application();
        $p=$c->container->get(FormationPersonnel::class); $s=$c->container->get(FormationSignatures::class);
        $formation=new CuriaFormationService($c->root,$c->journal,$s,$p,$clock);
        $app->addCommand(new CitadelFormationCommand($c->cognition,$p,$s,$formation));
        $app->addCommand(new CitadelIntakeCommand(new CitadelIntakeService($c->journal)));
        $app->addCommand(new CitadelPreparationCommand(new FormationPreparation($clock)));
        return $app;
    }

    public static function run(Application $app, string $root, string $name, string $operation, array $arguments): mixed
    {
        FormationCustodyFixture::assertRoot($root);
        $path=$root.'/command-'.bin2hex(random_bytes(8)).'.json';
        file_put_contents($path,json_encode(['operation'=>$operation,'arguments'=>$arguments],JSON_THROW_ON_ERROR));
        $tester=new CommandTester($app->find($name));
        if ($tester->execute(['request-file'=>$path]) !== 0) { throw new \RuntimeException(trim($tester->getDisplay())); }
        return json_decode($tester->getDisplay(),true,512,JSON_THROW_ON_ERROR)['result'];
    }
}
