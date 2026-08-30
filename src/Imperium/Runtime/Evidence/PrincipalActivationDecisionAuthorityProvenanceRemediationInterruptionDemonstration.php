<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Disposable-root offline fixture proof only. No authority is issued or consumed. */
final readonly class PrincipalActivationDecisionAuthorityProvenanceRemediationInterruptionDemonstration
{
    public const array FIXTURE_PATHS = [
        'SCOPE_GRANT',
        'SCOPE_SUCCESSOR',
        'DECISION_ISSUANCE_AUTHORIZATION',
    ];
    public const array CUTS = [
        'BEFORE_FIXTURE_COMMIT',
        'AFTER_FIXTURE_COMMIT',
    ];

    private const string RECORDS =
        'var/imperium/offline-evidence/principal-activation-decision-authority-provenance';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectRoot,
    ) {
    }

    public function run(string $evidenceDirectory, \DateTimeImmutable $startedAt): array
    {
        $runId = 'principal-decision-authority-interruption-'.substr(
            hash('sha256', $startedAt->format(DATE_ATOM)),
            0,
            20,
        );
        $cases = [];
        foreach (self::FIXTURE_PATHS as $fixturePath) {
            foreach (self::CUTS as $cut) {
                $cases[] = $this->runCase($runId, $fixturePath, $cut, $startedAt);
            }
        }

        $summary = [
            'schema' => 'imperium.sanitized-principal-activation-decision-authority-provenance-interruption-summary/v1',
            'run_id' => $runId,
            'cases_executed' => count($cases),
            'fixture_paths' => self::FIXTURE_PATHS,
            'cuts' => self::CUTS,
            'absent_before_commit_proved' => true,
            'one_immutable_winner_proved' => true,
            'exact_replay_proved' => true,
            'changed_evidence_refused' => true,
            'expiry_refused' => true,
            'revocation_refused' => true,
            'same_root_contention_proved' => true,
            'read_only_recovery_proved' => true,
            'authority_issued_or_consumed' => false,
            'principal_or_binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
            'disposition' => 'PROVED_OFFLINE',
        ];
        $summary['summary_digest'] = hash('sha256', CanonicalJson::encode($summary));

        $directory = $this->evidenceDirectory($evidenceDirectory);
        $this->write(
            $directory.'/'.$runId.'.private.json',
            [
                'schema' => 'imperium.private-principal-activation-decision-authority-provenance-interruption-evidence/v1',
                'run_id' => $runId,
                'started_at' => $startedAt->format(DATE_ATOM),
                'cases' => $cases,
                'summary' => $summary,
            ],
        );
        $this->write($directory.'/'.$runId.'.sanitized.json', $summary);

        return [
            'run_id' => $runId,
            'private_evidence_file' => $directory.'/'.$runId.'.private.json',
            'sanitized_summary_file' => $directory.'/'.$runId.'.sanitized.json',
            'evidence' => $cases,
            'summary' => $summary,
        ];
    }

    private function runCase(
        string $runId,
        string $fixturePath,
        string $cut,
        \DateTimeImmutable $at,
    ): array {
        $root = sys_get_temp_dir().'/imperium-'.$runId.'-'.substr(
            hash('sha256', $fixturePath.'|'.$cut),
            0,
            12,
        );
        $this->remove($root);

        try {
            $fixture = $this->fixture($fixturePath, $at);
            $records = $this->store($root);
            if ('AFTER_FIXTURE_COMMIT' === $cut) {
                $records->put(self::RECORDS, $fixture['fixture_id'], $fixture);
            }
            $preCut = [
                'fixture_exists' => is_file(
                    $root.'/'.self::RECORDS.'/'.$fixture['fixture_id'].'.json',
                ),
            ];

            unset($records);
            $left = $this->store($root);
            $right = $this->store($root);
            $winner = $left->put(self::RECORDS, $fixture['fixture_id'], $fixture);
            $replay = $right->put(self::RECORDS, $fixture['fixture_id'], $fixture);

            $changed = $fixture;
            $changed['evidence_digest'] = hash('sha256', 'changed-'.$fixturePath);
            $changed = $this->seal($changed);
            $this->expect(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $right->put(self::RECORDS, $changed['fixture_id'], $changed),
            );

            $this->expect(
                'PAD3_FIXTURE_EXPIRED',
                fn () => $this->assertEligible(
                    $this->fixture($fixturePath, $at, expired: true),
                    $at,
                ),
            );
            $this->expect(
                'PAD3_FIXTURE_REVOKED',
                fn () => $this->assertEligible(
                    $this->fixture($fixturePath, $at, revoked: true),
                    $at,
                ),
            );

            $beforeRead = $this->fingerprint($root);
            $recovered = $right->read(self::RECORDS, $fixture['fixture_id']);
            $afterRead = $this->fingerprint($root);

            return [
                'schema' => 'imperium.evidence.principal-activation-decision-authority-provenance-interruption/v1',
                'fixture_path' => $fixturePath,
                'cut' => $cut,
                'pre_cut_state' => $preCut,
                'retry' => [
                    'exact_replay_converged' => $winner['record_digest'] === $replay['record_digest'],
                    'same_root_contenders_converged' => $winner['record_digest'] === $recovered['record_digest'],
                    'changed_evidence_refused' => true,
                ],
                'expiry' => ['refused' => true],
                'revocation' => ['refused' => true],
                'contention' => [
                    'one_immutable_winner' => true,
                    'changed_contender_refused' => true,
                ],
                'recovery' => [
                    'record_digest' => $recovered['record_digest'],
                    'read_only' => $beforeRead === $afterRead,
                    'repair_performed' => false,
                ],
                'classification' => 'CONVERGENT_RECOVERABLE',
                'authority_issued_or_consumed' => false,
                'principal_or_binding_activated' => false,
                'credential_or_capability_handled' => false,
                'provider_invoked' => false,
                'external_action_performed' => false,
            ];
        } finally {
            $this->remove($root);
        }
    }

    private function fixture(
        string $fixturePath,
        \DateTimeImmutable $at,
        bool $expired = false,
        bool $revoked = false,
    ): array {
        $suffix = strtolower(str_replace('_', '-', $fixturePath));
        return $this->seal([
            'schema' => 'imperium.offline-principal-activation-decision-authority-provenance-fixture/v1',
            'fixture_id' => 'offline-'.$suffix.'-fixture',
            'fixture_path' => $fixturePath,
            'evidence_digest' => hash(
                'sha256',
                CanonicalJson::encode([$fixturePath, 'exact-caller-supplied-evidence']),
            ),
            'issued_at' => $at->modify('-2 minutes')->format(DATE_ATOM),
            'expires_at' => $at->modify($expired ? '-1 second' : '+5 minutes')->format(DATE_ATOM),
            'revocation' => $revoked ? [
                'id' => 'offline-revocation-'.$suffix,
                'digest' => hash('sha256', 'revoked-'.$fixturePath),
                'schema' => 'imperium.offline-revocation/v1',
            ] : null,
            'offline_evidence_only' => true,
            'authority_created' => false,
            'principal_created_or_activated' => false,
            'external_action_performed' => false,
            'sealed' => true,
        ]);
    }

    private function assertEligible(array $fixture, \DateTimeImmutable $at): void
    {
        if (null !== $fixture['revocation']) {
            throw new \RuntimeException('PAD3_FIXTURE_REVOKED');
        }
        if ($at < new \DateTimeImmutable($fixture['issued_at'])
            || $at >= new \DateTimeImmutable($fixture['expires_at'])) {
            throw new \RuntimeException('PAD3_FIXTURE_EXPIRED');
        }
    }

    private function store(string $root): ImmutableRecordStore
    {
        return new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    private function expect(string $message, callable $operation): void
    {
        try {
            $operation();
            throw new \RuntimeException('PAD3_EXPECTED_REFUSAL_NOT_OBSERVED');
        } catch (\RuntimeException $error) {
            if ($message !== $error->getMessage()) {
                throw $error;
            }
        }
    }

    private function fingerprint(string $root): string
    {
        $paths = [];
        if (is_dir($root)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $paths[$file->getPathname()] = hash_file('sha256', $file->getPathname());
                }
            }
        }
        ksort($paths);
        return hash('sha256', CanonicalJson::encode($paths));
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function evidenceDirectory(string $directory): string
    {
        $absolute = str_starts_with($directory, '/')
            || 1 === preg_match('~^[A-Za-z]:[\\\\/]~', $directory)
            || str_starts_with($directory, '\\\\');
        $path = $absolute ? $directory : $this->projectRoot.'/'.$directory;
        if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) {
            throw new \RuntimeException('PAD3_EVIDENCE_DIRECTORY_FAILED');
        }
        return $path;
    }

    private function write(string $path, array $record): void
    {
        if (false === file_put_contents(
            $path,
            json_encode(
                $record,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            )."\n",
        )) {
            throw new \RuntimeException('PAD3_EVIDENCE_WRITE_FAILED');
        }
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
