<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\StorageSuccessorRecord as Record;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class StorageSuccessorRecordTest extends TestCase
{
    public function testExactBoundedCandidateRetainsBothPredecessors(): void
    {
        self::assertCount(2, Record::load(dirname(__DIR__, 3)));
    }
    public static function invalid(): iterable
    {
        foreach (['predecessor','unlisted','candidate-hash','approval-hash','missing-reference','duplicate','proposal-bytes','unapproved','acceptance','base','copy'] as $case) { yield $case => [$case]; }
    }
    #[DataProvider('invalid')]
    public function testTamperedCandidateNeverBecomesAPinExemption(string $case): void
    {
        $root=dirname(__DIR__,3);
        $r=json_decode(file_get_contents($root.'/docs/provider-storage-successor-source-record-v1.json'),true);
        $a=json_decode(file_get_contents($root.'/docs/handoffs/provider-storage-successor-approval.json'),true);
        $p=file_get_contents($root.'/docs/provider-storage-successor-contract-v1.md');
        switch($case) {
            case 'predecessor':$r['sources'][0]['predecessor_sha256']=str_repeat('0',64);break;
            case 'unlisted':$r['sources'][0]['path']='src/Imperium/Runtime/Persistence/AtomicTransition.php';break;
            case 'candidate-hash':$r['sources'][0]['normalized_sha256']=str_repeat('0',64);break;
            case 'approval-hash':$r['approved_proposal_sha256']=str_repeat('0',64);break;
            case 'missing-reference':unset($r['approval_reference']);break;
            case 'duplicate':$r['sources'][1]=$r['sources'][0];break;
            case 'proposal-bytes':$p.="\n";break;
            case 'unapproved':$a['status']='PENDING';break;
            case 'acceptance':$r['implementation_accepted']=true;break;
            case 'base':$r['base']['commit']=str_repeat('0',40);break;
            case 'copy':$r['sources'][0]['predecessor_copy']='missing.txt';break;
        }
        $this->expectExceptionMessage('PPC4_SUCCESSOR_RECORD_INVALID');
        Record::validate($root,$r,$a,$p);
    }
}
