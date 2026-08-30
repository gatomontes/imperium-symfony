<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;
final class ProviderBindingActivationCorridorDispositionReconsiderationCampaignSelectionTest extends TestCase
{
    public function testSelectionAuthorizesPreparationInventoryOnly():void
    {
        $root=dirname(__DIR__,3);$ready=(string)file_get_contents($root.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-campaign-ready.md');$next=(string)file_get_contents($root.'/docs/next-campaign-provider-binding-activation-corridor-disposition-reconsideration.md');
        foreach(['Only Preparation Batch 0 is authorized','cross-process custody refusal remains authoritative','grants no runtime producer','Iron Gate','Lazaretto','Provider Execution Assurance remains paused']as$b)self::assertNotFalse(stripos($ready,$b),$b);
        foreach(['CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY','QUARANTINED_PENDING_REMEDIATION','RETIRE_CORRIDOR','Missing evidence or authority is a valid refusal','may not create or activate a principal','seal a corridor disposition','external I/O']as$b)self::assertNotFalse(stripos($next,$b),$b);
    }
}
