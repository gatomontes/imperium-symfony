<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\DelegateMissionDeploymentCustodyTransitionCoordinator;
use App\Imperium\Runtime\Garrison\DeploymentCustodyTransitionFaultInjector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelegateMissionDeploymentCustodyTransitionCoordinatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-deployment-custody-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public static function checkpoints(): iterable
    {
        foreach (['PREPARED', 'CUSTODY_DEPLOYED', 'TRANSITION_RECORDED', 'COMPLETE'] as $checkpoint) {
            yield $checkpoint => [$checkpoint];
        }
    }

    #[DataProvider('checkpoints')]
    public function testEveryInterruptedCheckpointResumesToOneDeploymentState(string $checkpoint): void
    {
        $prior = $this->write('var/imperium/offices/garrison/custody/custody-123.json', ['custody_id' => 'custody-123', 'custody_state' => 'ADMITTED_HELD', 'available' => true]);
        $deployed = $this->seal(['custody_id' => 'custody-123', 'custody_state' => 'DELEGATE_MISSION_DEPLOYED_BOUND', 'available' => false]);
        $transition = ['transition_id' => 'transition-123', 'source_deployment_authorization' => ['id' => 'authorization-123', 'digest' => str_repeat('a', 64)], 'status' => 'DEPLOYED'];
        $fault = new class($checkpoint) implements DeploymentCustodyTransitionFaultInjector {
            public function __construct(private string $checkpoint) {}
            public function after(string $checkpoint): void { if ($checkpoint === $this->checkpoint) throw new \RuntimeException('INJECTED_DEPLOYMENT_FAILURE'); }
        };
        try {
            (new DelegateMissionDeploymentCustodyTransitionCoordinator($this->root, faults: $fault))->run('authorization-123', 'transition-123', $transition, $prior, $deployed);
            self::fail('Expected injected transition failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('INJECTED_DEPLOYMENT_FAILURE', $exception->getMessage());
        }
        $record = (new DelegateMissionDeploymentCustodyTransitionCoordinator($this->root))->resumeForAuthorization('authorization-123');
        self::assertSame('DEPLOYED', $record['status']);
        self::assertSame($deployed, $this->read('var/imperium/offices/garrison/custody/custody-123.json'));
        self::assertSame('COMPLETE', $this->read('var/imperium/runtime/delegate-mission-deployment-custody-transitions/transition-123.json')['checkpoint']);
        self::assertCount(1, glob($this->root.'/var/imperium/offices/garrison/delegate-mission-operational-custody-transitions/*.json') ?: []);
    }

    private function seal(array $record): array { $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
    private function write(string $path, array $record): array { $record=$this->seal($record);$absolute=$this->root.'/'.$path;if(!is_dir(dirname($absolute)))mkdir(dirname($absolute),0770,true);file_put_contents($absolute,json_encode($record,JSON_THROW_ON_ERROR));return$record; }
    private function read(string $path): array { return json_decode((string)file_get_contents($this->root.'/'.$path),true,512,JSON_THROW_ON_ERROR); }
    private function remove(string $path): void { if(!is_dir($path))return;foreach(array_diff(scandir($path)?:[],['.','..'])as$entry){$child=$path.'/'.$entry;is_dir($child)?$this->remove($child):unlink($child);}rmdir($path); }
}
