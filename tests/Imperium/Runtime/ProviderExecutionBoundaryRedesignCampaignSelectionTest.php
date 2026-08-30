<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignCampaignSelectionTest extends TestCase
{
    public function testSelectionAuthorizesPreparationInventoryOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $next = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/next-campaign-provider-execution-boundary-redesign.md'));
        $ready = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-execution-boundary-redesign-campaign-ready.md'));
        $review = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-execution-boundary-redesign-blackquill-review.md'));

        foreach (['CAMPAIGN_SELECTED_PREPARATION_BATCH_0_ONLY', 'credential possession from execution authority', 'durable authority identity from process-local capability identity', 'same-process execution from cross-process credential transfer', 'Missing evidence, an incoherent boundary or permanent refusal are valid results'] as $proof) {
            self::assertNotFalse(stripos($next, $proof), $proof);
        }

        foreach (['Only Preparation Batch 0', 'Do not define runtime contracts', 'change runtime behavior', 'activate a principal or binding', 'issue or consume authority', 'handle a credential or capability', 'invoke a provider', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) {
            self::assertNotFalse(stripos($next.$ready, $boundary), $boundary);
        }

        foreach (['BOUNDARY_REDRAW_REQUIRED', 'Provider credential', 'Execution authority', 'CredentialCapability', 'trusted-writer', 'does not authorize it'] as $finding) {
            self::assertNotFalse(stripos($review, $finding), $finding);
        }
    }
}
