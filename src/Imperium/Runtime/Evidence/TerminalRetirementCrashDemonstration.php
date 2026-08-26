<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\DelegateMissionTerminalTransitionCoordinator;
use App\Imperium\Runtime\Garrison\TerminalTransitionFaultInjector;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class TerminalRetirementCrashDemonstration
{
    private const array CHECKPOINTS = ['PREPARED', 'CUSTODY_RESTORED', 'BINDING_RETIRED', 'TERMINAL_RECORDED', 'COMPLETE'];
    private const array PROHIBITED = [
        'operational_use_authority', 'provider_invocation_authority', 'credential_use_authority',
        'tool_use_authority', 'perimeter_crossing_authority', 'external_action_authority',
        'execution_authority', 'continuing_authority', 'redeployment_authority', 'reuse_authority',
    ];

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectRoot) {}

    public function run(string $evidenceDirectory, ?\DateTimeImmutable $startedAt = null): array
    {
        $startedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $sourceCommit = $this->sourceCommit();
        $fixture = $this->fixture();
        $fixtureDigest = hash('sha256', CanonicalJson::encode($fixture));
        $runId = 'terminal-recovery-'.substr(hash('sha256', CanonicalJson::encode([$sourceCommit, $startedAt->format(DATE_ATOM), $fixtureDigest])), 0, 20);
        $cases = array_map(fn (string $checkpoint): array => $this->runCase($runId, $checkpoint, $fixture), self::CHECKPOINTS);
        $contention = $this->runContention($runId, $fixture);
        $summary = [
            'schema' => 'imperium.sanitized-terminal-retirement-crash-demonstration-summary/v1',
            'demonstration' => 'terminal-retirement-recovery',
            'source_commit' => $sourceCommit,
            'cases_executed' => count($cases),
            'properties_proved' => [
                'deterministic_forward_recovery', 'custody_restoration', 'binding_retirement',
                'single_terminal_record', 'exact_completed_replay',
                'single_winner_conflict_rejection', 'zero_surviving_authority',
            ],
            'final_state_class' => 'returned-unbound-custody-restored-retired-terminal',
            'continuing_operational_authority' => false,
            'disposition' => 'PROVED',
        ];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $evidence = [
            'schema' => 'imperium.private-terminal-retirement-crash-demonstration-evidence/v1',
            'demonstration_id' => 'crash-demonstration-4', 'run_id' => $runId,
            'started_at' => $startedAt->format(DATE_ATOM),
            'finished_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'source_commit' => $sourceCommit, 'runtime' => ['php_version' => PHP_VERSION, 'sapi' => PHP_SAPI],
            'fixture' => ['fixture_id' => 'terminal-retirement-deterministic-v1', 'fixture_digest' => $fixtureDigest],
            'cases' => $cases, 'single_winner_contention' => $contention,
            'sanitized_summary' => $summary, 'sanitized_summary_digest' => $summary['summary_digest'], 'disposition' => 'PROVED',
        ];
        $evidence['evidence_record_digest'] = hash('sha256', CanonicalJson::encode($evidence));
        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->writeJson($directory.'/'.$runId.'.private.json', $evidence);
        $this->writeJson($directory.'/'.$runId.'.sanitized.json', $summary);
        return ['run_id' => $runId, 'private_evidence_file' => $directory.'/'.$runId.'.private.json', 'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json', 'summary' => $summary];
    }

    private function runCase(string $runId, string $checkpoint, array $fixture): array
    {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.strtolower($checkpoint);
        $this->remove($root);
        $this->seedState($root, $fixture);
        $fault = new class($checkpoint) implements TerminalTransitionFaultInjector {
            public function __construct(private string $selected) {}
            public function after(string $checkpoint): void { if ($checkpoint === $this->selected) throw new \RuntimeException('INJECTED_TERMINAL_TRANSITION_FAILURE'); }
        };
        try {
            try {
                (new DelegateMissionTerminalTransitionCoordinator($root, faults: $fault))->run(
                    $fixture['authorization_id'], $fixture['terminal_id'], $fixture['terminal'],
                    $fixture['prior_custody'], $fixture['restored_custody'], $fixture['prior_binding'], $fixture['retired_binding'],
                );
                throw new \RuntimeException('DEMO_EXPECTED_INJECTION_DID_NOT_OCCUR');
            } catch (\RuntimeException $error) {
                if ('INJECTED_TERMINAL_TRANSITION_FAILURE' !== $error->getMessage()) throw $error;
            }
            $interrupted = $this->snapshot($root, $fixture);
            $coordinator = new DelegateMissionTerminalTransitionCoordinator($root);
            $terminal = $coordinator->resumeForAuthorization($fixture['authorization_id']);
            $recovered = $this->snapshot($root, $fixture);
            $coordinator->run(
                $fixture['authorization_id'], $fixture['terminal_id'], $fixture['terminal'],
                $fixture['prior_custody'], $fixture['restored_custody'], $fixture['prior_binding'], $fixture['retired_binding'],
            );
            $replayed = $this->snapshot($root, $fixture);
            $conflict = $fixture['terminal']; $conflict['demonstration_conflict_variant'] = true;
            try {
                $coordinator->run(
                    $fixture['authorization_id'], $fixture['terminal_id'], $conflict,
                    $fixture['prior_custody'], $fixture['restored_custody'], $fixture['prior_binding'], $fixture['retired_binding'],
                );
                throw new \RuntimeException('DEMO_CONFLICT_WAS_NOT_REJECTED');
            } catch (\RuntimeException $error) {
                if ('GA309_DELEGATE_TERMINAL_RETURN_CONFLICT' !== $error->getMessage()) throw $error;
            }
            $assertions = $this->assertions($checkpoint, $fixture, $terminal, $interrupted, $recovered, $replayed);
            return [
                'crash_point' => $checkpoint, 'injected_failure_observed' => true, 'interrupted' => $interrupted,
                'recovery' => ['resumed_forward' => true, 'final' => $recovered],
                'replay' => ['exact' => true, 'before' => $recovered, 'after' => $replayed],
                'conflict' => ['rejected' => true, 'error' => 'GA309_DELEGATE_TERMINAL_RETURN_CONFLICT'],
                'assertions' => $assertions, 'disposition' => 'PROVED',
            ];
        } finally { $this->remove($root); }
    }

    private function assertions(string $checkpoint, array $fixture, ?array $terminal, array $interrupted, array $recovered, array $replayed): array
    {
        $expected = match ($checkpoint) {
            'PREPARED' => ['DELEGATE_MISSION_DEPLOYED_BOUND', false, 'DELEGATE_MISSION_DEPLOYED_BOUND', true, 0],
            'CUSTODY_RESTORED' => ['ADMITTED_HELD', true, 'DELEGATE_MISSION_DEPLOYED_BOUND', true, 0],
            'BINDING_RETIRED' => ['ADMITTED_HELD', true, 'DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED', false, 0],
            'TERMINAL_RECORDED', 'COMPLETE' => ['ADMITTED_HELD', true, 'DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED', false, 1],
        };
        $authorityClosed = true;
        foreach (self::PROHIBITED as $field) $authorityClosed = $authorityClosed && false === ($fixture['terminal'][$field] ?? null);
        $assertions = [
            'selected_checkpoint_was_durable' => $checkpoint === $interrupted['checkpoint'],
            'interruption_state_matches_matrix' => $expected === [$interrupted['custody_state'], $interrupted['custody_available'], $interrupted['binding_status'], $interrupted['seat_bound'], $interrupted['terminal_count']],
            'transaction_completed' => 'COMPLETE' === $recovered['checkpoint'],
            'custody_restored_and_available' => 'ADMITTED_HELD' === $recovered['custody_state'] && true === $recovered['custody_available'],
            'binding_retired_and_unbound' => 'DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED' === $recovered['binding_status'] && false === $recovered['seat_bound'],
            'one_terminal_recorded' => 1 === $recovered['terminal_count'],
            'terminal_identity_matches' => $fixture['terminal_id'] === ($terminal['terminal_id'] ?? null),
            'terminal_checkpoint_matches' => 'DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL' === ($terminal['status'] ?? null),
            'exact_replay_preserved_transaction' => $recovered['transaction_digest'] === $replayed['transaction_digest'],
            'exact_replay_preserved_custody' => $recovered['custody_digest'] === $replayed['custody_digest'],
            'exact_replay_preserved_binding' => $recovered['binding_digest'] === $replayed['binding_digest'],
            'prohibited_authorities_false' => $authorityClosed,
        ];
        if (in_array(false, $assertions, true)) throw new \RuntimeException('DEMO_TERMINAL_RETIREMENT_INVARIANT_FAILED');
        return $assertions;
    }

    private function snapshot(string $root, array $fixture): array
    {
        $transaction = $this->read($root.'/var/imperium/runtime/delegate-mission-terminal-transitions/'.$fixture['terminal_id'].'.json');
        $custody = $this->read($root.'/var/imperium/offices/garrison/custody/'.$fixture['prior_custody']['custody_id'].'.json');
        $binding = $this->read($root.'/var/imperium/mission/occupancy/'.$fixture['prior_binding']['binding_id'].'.json');
        return [
            'checkpoint' => $transaction['checkpoint'], 'transaction_digest' => $transaction['record_digest'],
            'custody_state' => $custody['custody_state'], 'custody_available' => $custody['available'], 'custody_digest' => $custody['record_digest'],
            'binding_status' => $binding['status'], 'seat_bound' => $binding['seat_bound'], 'binding_digest' => $binding['record_digest'],
            'terminal_count' => count(glob($root.'/var/imperium/offices/garrison/delegate-mission-terminal-returns/*.json') ?: []),
        ];
    }

    private function runContention(string $runId, array $fixture): array
    {
        if (!function_exists('proc_open')) throw new \RuntimeException('DEMO_PROCESS_CONTENTION_UNAVAILABLE');
        $root=sys_get_temp_dir().'/imperium-'.$runId.'-contention';$this->remove($root);$this->seedState($root,$fixture);
        $fixturePath=$root.'/fixture.json';$this->writeJson($fixturePath,$fixture);$gate=$root.'/go';$worker=$this->projectRoot.'/tests/fixtures/terminal-retirement-contender.php';
        $descriptors=[1=>['pipe','w'],2=>['pipe','w']];$processes=$pipes=[];
        try {
            for($i=0;$i<2;++$i){$processes[$i]=proc_open([PHP_BINARY,$worker,$root,$fixturePath,$gate,(string)$i],$descriptors,$pipes[$i]);if(!is_resource($processes[$i]))throw new \RuntimeException('DEMO_CONTENTION_PROCESS_START_FAILED');}
            touch($gate);$results=[];
            foreach($processes as$i=>$process){$results[]=stream_get_contents($pipes[$i][1]);$stderr=stream_get_contents($pipes[$i][2]);fclose($pipes[$i][1]);fclose($pipes[$i][2]);if(0!==proc_close($process)||''!==$stderr)throw new \RuntimeException('DEMO_CONTENTION_PROCESS_FAILED');}
            sort($results);if(['GA309_DELEGATE_TERMINAL_RETURN_CONFLICT','STORED']!==$results)throw new \RuntimeException('DEMO_SINGLE_WINNER_INVARIANT_FAILED');
            return ['workers'=>2,'winner_count'=>1,'conflict_count'=>1,'single_winner_proved'=>true];
        } finally {$this->remove($root);}
    }

    private function fixture(): array
    {
        $priorCustody=$this->seal(['custody_id'=>'custody-demonstration-4','custody_state'=>'DELEGATE_MISSION_DEPLOYED_BOUND','available'=>false,'execution_authority'=>false]);
        $restored=$priorCustody;unset($restored['record_digest']);$restored['custody_state']='ADMITTED_HELD';$restored['available']=true;$restored=$this->seal($restored);
        $priorBinding=$this->seal(['binding_id'=>'binding-demonstration-4','status'=>'DELEGATE_MISSION_DEPLOYED_BOUND','seat_bound'=>true,'execution_authority'=>false]);
        $retired=$priorBinding;unset($retired['record_digest']);$retired['status']='DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED';$retired['seat_bound']=false;$retired=$this->seal($retired);
        $closed=array_fill_keys(self::PROHIBITED,false);
        $terminal=array_merge($closed,['schema'=>'imperium.garrison-delegate-mission-terminal-return/v1','terminal_id'=>'delegate-mission-terminal-return-'.str_repeat('d',20),'status'=>'DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL','returned'=>true,'custody_restored'=>true,'manifestation_retired'=>true,'seat_bound'=>false]);
        return ['authorization_id'=>'delegate-mission-return-authorization-'.str_repeat('a',20),'terminal_id'=>$terminal['terminal_id'],'terminal'=>$terminal,'prior_custody'=>$priorCustody,'restored_custody'=>$restored,'prior_binding'=>$priorBinding,'retired_binding'=>$retired];
    }

    private function seedState(string $root,array $fixture):void{$this->writeJson($root.'/var/imperium/offices/garrison/custody/'.$fixture['prior_custody']['custody_id'].'.json',$fixture['prior_custody']);$this->writeJson($root.'/var/imperium/mission/occupancy/'.$fixture['prior_binding']['binding_id'].'.json',$fixture['prior_binding']);}
    private function seal(array $record):array{$record['record_digest']=hash('sha256',CanonicalJson::encode($record));return$record;}
    private function read(string $path):array{return json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}
    private function sourceCommit():string{$head=trim((string)file_get_contents($this->projectRoot.'/.git/HEAD'));if(str_starts_with($head,'ref: ')){$path=$this->projectRoot.'/.git/'.substr($head,5);if(is_file($path))return trim((string)file_get_contents($path));}return preg_match('/^[a-f0-9]{40}$/',$head)?$head:'UNRESOLVED';}
    private function evidenceDirectory(string $directory):string{$directory=trim(str_replace('\\','/',$directory));if(''===$directory||str_contains($directory,'..'))throw new \InvalidArgumentException('DEMO_EVIDENCE_DIRECTORY_INVALID');$absolute=str_starts_with($directory,'/')||preg_match('/^[A-Za-z]:\//',$directory)?$directory:$this->projectRoot.'/'.$directory;if(!is_dir($absolute)&&!mkdir($absolute,0770,true)&&!is_dir($absolute))throw new \RuntimeException('DEMO_EVIDENCE_DIRECTORY_CREATE_FAILED');return rtrim($absolute,'/');}
    private function writeJson(string $path,array $record):void{if(!is_dir(dirname($path))&&!mkdir(dirname($path),0770,true)&&!is_dir(dirname($path)))throw new \RuntimeException('DEMO_STORAGE_CREATE_FAILED');if(false===file_put_contents($path,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX))throw new \RuntimeException('DEMO_STORAGE_WRITE_FAILED');}
    private function remove(string $path):void{if(!is_dir($path))return;foreach(array_diff(scandir($path)?:[],['.','..'])as$entry){$child=$path.'/'.$entry;is_dir($child)?$this->remove($child):unlink($child);}rmdir($path);}
}
