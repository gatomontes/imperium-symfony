<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

/** Narrow documentary candidate chain. This never records receiving acceptance. */
final class StorageSuccessorRecord
{
    public const PROPOSAL = '9c8a8d8e4fde15ef6aabddfab83fa4a6deb569837ca3ead457d6b9f297bbf464';
    private const PREDECESSORS = [
        'src/Imperium/Runtime/Persistence/ImmutableRecordStore.php' => '51acaf101d0c14301cd2d29f19581473ff93fdc82179fa34225566282f97ae7a',
        'src/Imperium/Runtime/Persistence/MutableStateStore.php' => 'ca5feb40101e18b0577c84b7df265f4067d13c67213a222cf9b39e4e0520d99e',
    ];
    public static function load(string $root): array
    {
        return self::validate($root,
            json_decode(file_get_contents($root.'/docs/provider-storage-successor-source-record-v1.json'), true, 512, JSON_THROW_ON_ERROR),
            json_decode(file_get_contents($root.'/docs/handoffs/provider-storage-successor-approval.json'), true, 512, JSON_THROW_ON_ERROR),
            file_get_contents($root.'/docs/provider-storage-successor-contract-v1.md'));
    }
    public static function validate(string $root, array $record, array $approval, string $proposal): array
    {
        $require = static function (bool $ok): void { if (!$ok) { throw new \RuntimeException('PPC4_SUCCESSOR_RECORD_INVALID'); } };
        $require(hash('sha256', $proposal) === self::PROPOSAL);
        $require(($approval['status'] ?? null) === 'OWNER_APPROVED_BOUNDED_OFFLINE_IMPLEMENTATION'
            && ($approval['clauses'] ?? null) === ['A','B','C','D','E','F']
            && ($approval['approved_proposal_sha256'] ?? null) === self::PROPOSAL
            && ($approval['proposal_sha256'] ?? null) === self::PROPOSAL);
        $ref = $approval['approval_conversational_reference'] ?? [];
        $require(($ref['owner_reply_exact'] ?? null) === 'approved'
            && ($ref['in_response_to_exact_question'] ?? null) === 'Do you approve clauses A–F for bounded offline implementation?'
            && ($ref['proposal_publication_pr'] ?? null) === 820
            && ($ref['proposal_publication_commit'] ?? null) === '65e8e27570a3899dec73affc70a72b2936223dde'
            && ($ref['proposal_publication_merge'] ?? null) === '20d27ad3b216b1e3c6588279fc20d1d5444bc77f');
        $require(($record['schema'] ?? null) === 'imperium.documentary.provider-storage-successor-candidate/v1'
            && ($record['status'] ?? null) === 'AUTHORIZED_IMPLEMENTATION_CANDIDATE_NOT_ACCEPTED'
            && ($record['implementation_accepted'] ?? null) === false
            && ($record['approved_proposal_sha256'] ?? null) === self::PROPOSAL
            && ($record['proposal_path'] ?? null) === 'docs/provider-storage-successor-contract-v1.md'
            && ($record['approval_reference'] ?? null) === $ref
            && ($record['base'] ?? null) === ['commit'=>'816f0ffd14d6cc4d2c5d2c4605ec306aecffbf44','tree'=>'0cbf06a20928ce6be664b33925a220be1e87ab50']);
        $require(array_key_exists('tested_identity', $record));
        if ($record['tested_identity'] !== null) {
            $require(is_array($record['tested_identity']) && count($record['tested_identity']) === 2);
            foreach (['commit','tree'] as $key) { $require(preg_match('/^[a-f0-9]{40}$/D', $record['tested_identity'][$key] ?? '') === 1); }
        }
        $require(is_array($record['sources'] ?? null) && count($record['sources']) === 2);
        $result = [];
        foreach ($record['sources'] as $row) {
            $path = $row['path'] ?? '';
            $require(isset(self::PREDECESSORS[$path]) && !isset($result[$path]) && count($row) === 4);
            $copy = 'docs/handoffs/provider-storage-successor-predecessors/'.basename($path).'.txt';
            $require(($row['predecessor_copy'] ?? null) === $copy
                && ($row['predecessor_sha256'] ?? null) === self::PREDECESSORS[$path]
                && hash('sha256', file_get_contents($root.'/'.$copy)) === self::PREDECESSORS[$path]);
            $actual = hash('sha256', str_replace(["\r\n", "\r"], "\n", file_get_contents($root.'/'.$path)));
            $require(($row['normalized_sha256'] ?? null) === $actual);
            $result[$path] = $row;
        }
        return $result;
    }
}
