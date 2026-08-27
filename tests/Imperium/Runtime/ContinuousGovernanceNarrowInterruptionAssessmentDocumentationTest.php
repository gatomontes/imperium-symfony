<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;
final class ContinuousGovernanceNarrowInterruptionAssessmentDocumentationTest extends TestCase
{
 public function testAssessmentSelectsOnlyUnclaimedGovernanceLeaseAndKeepsDeferredBoundariesClosed():void{$d=(string)file_get_contents(dirname(__DIR__,3).'/docs/continuous-governance-narrow-interruption-slice-assessment.md');foreach(['`EXISTS_CANONICALLY`','`EXISTS_FRAGMENTED`','`ABSENT`','`DEFERRED_BOUNDARY`']as$status)self::assertStringContainsString($status,$d);self::assertStringContainsString('`IN_PROGRESS_SELECTED_SCOPE`',$d);self::assertSame(1,substr_count($d,'`IN_PROGRESS_SELECTED_SCOPE`'));self::assertStringContainsString('DENY_DURABLE_GOVERNANCE_INVOCATION_CLAIM_FOR_EXACT_LEASE',$d);self::assertStringContainsString('cannot close, rewrite, expire, supersede, or revoke the immutable lease',$d);foreach(['General propagation','kill switches','telemetry','containment','incidents','Iron Gate','Lazaretto','sorties','new credential-platform work']as$boundary)self::assertStringContainsString($boundary,$d);self::assertStringContainsString('Each step remains a separate batch.',$d);}
}
