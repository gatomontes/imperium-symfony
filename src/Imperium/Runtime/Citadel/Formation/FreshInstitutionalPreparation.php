<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore, Rules as R};

/** Additive exact initialization adapter: retained completed custody is not in-flight.
 * Original PPC5/PPC6 initializers and their conservative guards remain untouched.
 * Each initialization still requires its own existing exact owner competence.
 */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FreshInstitutionalPreparation
{
    public const SCHEMA = 'imperium.fresh-institutional-preparation-initialization/v1';
    public function __construct(private string $root, private AuthorityStore $store) {}
    public function initializeModel(array $terms, array $decision): array
    {
        return $this->initialize('model_preparation', $terms, $decision);
    }
    public function initializeDesignations(array $terms, array $decision): array
    {
        return $this->initialize('profile_designations', $terms, $decision);
    }
    private function initialize(string $kind, array $terms, array $decision): array
    {
        FormationFreshEstablishment::bounded([$terms, $decision]);
        return $this->store->journal->changeAtHead(function (array &$state, array $head, FormationOwnerFrame $owner) use ($kind, $terms, $decision): array {
            $owner->assertOwner(new FormationJournal($this->root));
            FormationFreshEstablishment::completedInOwner($this->root, $owner);
            R::require(!array_key_exists($kind, $state), 'FRESH_PREPARATION_ALREADY_INITIALIZED');
            foreach (['sessions', 'claims', 'reservations'] as $field) { R::require(($state[$field] ?? []) === [], 'FRESH_PREPARATION_IN_FLIGHT'); }
            // AuthorityStore::state invokes the unchanged complete LedgerState validator:
            // five checkpoint originals, response bytes, settlement usage, command,
            // consumed slot and actual completion must agree for every settled claim.
            $onboarding = $this->store->state($state);
            R::require($onboarding['source_fences'] === [], 'FRESH_PREPARATION_IN_FLIGHT');
            foreach ($onboarding['claims'] as $claim) {
                R::require($claim['settled'] !== null && count($claim['custody']) === 5
                    && $claim['custody'][4]['body']['stage'] === 'RESPONSE_RETAINED', 'FRESH_PREPARATION_IN_FLIGHT');
            }
            $model = $kind === 'model_preparation';
            R::object($terms, $model ? ['schema', 'instance_id', 'citadel_id', 'expected_head'] : ['schema', 'citadel_id', 'expected_head']);
            $schema = $model ? FormationModelPreparation::STATE : FormationProfileDesignationInitialization::SCHEMA;
            R::require($terms['schema'] === $schema && $terms['citadel_id'] === $this->store->citadel
                && R::same($terms['expected_head'], $head), 'FRESH_PREPARATION_HEAD');
            $actor = (new FormationInstitution($this->root))->actorInOwner($owner, $model ? 'conscription' : 'laboratorium');
            R::require($actor['instance_id'] === $this->store->instance
                && (!$model || $terms['instance_id'] === $this->store->instance), 'FRESH_PREPARATION_IDENTITY');
            $effect = $model ? 'INITIALIZE_FORMATION_MODEL_PREPARATION' : 'INITIALIZE_FORMATION_PROFILE_DESIGNATIONS';
            (new FormationSignatures($this->store->journal, $this->store->clock))->verify($state, $decision, $effect, $terms);
            $initialization = ['terms' => $terms, 'decision' => $decision];
            $state[$kind] = ['schema' => $schema, 'initialization' => $initialization];
            foreach ($model ? ['bindings', 'lineages', 'delegations', 'authorizations', 'seals', 'consumed', 'nonces'] : ['delegations', 'events', 'current'] as $field) {
                $state[$kind][$field] = [];
            }
            $proof = ['schema' => self::SCHEMA, 'kind' => $kind, 'initialization' => $initialization,
                'custody_digest' => FormationJournal::digest($onboarding['claims']), 'expected_head' => $head];
            $proof['record_digest'] = FormationJournal::digest($proof);
            $state['fresh_institutions']['preparations'][$kind] = $proof;
            return $initialization;
        });
    }
}
