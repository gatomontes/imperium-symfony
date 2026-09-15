<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FreshInstitutionalPreparation, FormationModelPreparation, FormationProfileDesignationInitialization, FormationInstitution, FormationFreshEstablishment as P};
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentFixture as F;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
final class FreshEstablishmentPreparationTest extends TestCase
{
    private function refuses(callable $operation): void {
        try { $operation(); } catch (\RuntimeException $e) {
            if ($e instanceof \PHPUnit\Framework\Exception) { throw $e; }
            self::assertNotSame('', $e->getMessage()); return;
        }
        self::fail('Required refusal');
    }
    public function testSeparateInitializerPreservesCompletedClaimsAndOriginalConservativeGuards(): void
    {
        $f = new F();
        try {
            $before = $f->journal->read()['state']['onboarding'];
            $terms = ['schema' => FormationModelPreparation::STATE, 'instance_id' => $f->store->instance, 'citadel_id' => $f->store->citadel, 'expected_head' => $f->head()];
            $decision = $f->sign('INITIALIZE_FORMATION_MODEL_PREPARATION', $terms);
            $legacy = new FormationModelPreparation($f->journal, $f->signatures, $f->clock, new FormationInstitution($f->root));
            $this->refuses(fn() => $legacy->initialize($terms, $decision));
            $adapter = new FreshInstitutionalPreparation($f->root, $f->store);
            $this->refuses(fn() => $adapter->initializeModel($terms, $f->sign(P::EFFECT, $terms)));
            self::assertSame(['terms' => $terms, 'decision' => $decision], $adapter->initializeModel($terms, $decision));
            $terms = ['schema' => FormationProfileDesignationInitialization::SCHEMA, 'citadel_id' => $f->store->citadel, 'expected_head' => $f->head()];
            $decision = $f->sign('INITIALIZE_FORMATION_PROFILE_DESIGNATIONS', $terms);
            $this->refuses(fn() => (new FormationProfileDesignationInitialization($f->journal, $f->signatures))->initialize($terms['expected_head'], $decision));
            self::assertSame(['terms' => $terms, 'decision' => $decision], $adapter->initializeDesignations($terms, $decision));
            self::assertSame($before, $f->journal->read()['state']['onboarding']);
            self::assertCount(2, P::history($f->journal->read()['state'])['preparations']);
        } finally { $f->close(); }
    }
    public static function damage(): iterable { foreach (['source-fence', 'unsettled-claim', 'corrupt-checkpoint', 'outer-session'] as $case) { yield $case => [$case]; } }
    #[DataProvider('damage')]
    public function testAmbiguousOrAffectedCustodyDoesNotInitialize(string $case): void
    {
        $f = new F();
        try {
            // Explicit negative storage fault injection; never used by positive authority construction.
            $f->journal->change(static function (array &$state) use ($case): void {
                $key = array_key_first($state['onboarding']['claims']);
                if ($case === 'source-fence') { $state['onboarding']['source_fences']['unknown'] = []; }
                elseif ($case === 'unsettled-claim') { $state['onboarding']['claims'][$key]['settled'] = null; }
                elseif ($case === 'corrupt-checkpoint') { $state['onboarding']['claims'][$key]['custody'][4]['body']['stage'] = 'UNKNOWN'; }
                else { $state['sessions']['unknown'] = []; }
            });
            $terms = ['schema' => FormationModelPreparation::STATE, 'instance_id' => $f->store->instance, 'citadel_id' => $f->store->citadel, 'expected_head' => $f->head()];
            $head = $f->head(); $this->refuses(fn() => (new FreshInstitutionalPreparation($f->root, $f->store))->initializeModel($terms, $f->sign('INITIALIZE_FORMATION_MODEL_PREPARATION', $terms)));
            self::assertSame($head, $f->head()); self::assertArrayNotHasKey('model_preparation', $f->journal->read()['state']);
        } finally { $f->close(); }
    }
}
