<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** Explicit offline owner operation. Initializes no actor or designation. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FormationProfileDesignationInitialization
{
    public const SCHEMA = 'imperium.formation-profile-designations/v1';

    public function __construct(private FormationJournal $journal, private FormationSignatures $signatures) {}

    public function initialize(array $expectedHead, array $decision): array
    {
        return $this->journal->changeAtHead(function (array &$state, array $head) use ($expectedHead, $decision): array {
            if (!FormationJournal::keys($expectedHead, ['generation', 'digest']) || $expectedHead !== $head) {
                throw new \RuntimeException('PPC203_DESIGNATION_STALE_HEAD');
            }
            if (array_key_exists('profile_designations', $state)) {
                throw new \RuntimeException('PPC204_DESIGNATION_ALREADY_INITIALIZED');
            }
            // Conservative boundary: existing affected sessions/custody must be
            // assessed by their owners before initialization, never guessed idle.
            foreach (['sessions', 'claims', 'reservations'] as $field) {
                if (($state[$field] ?? []) !== []) {
                    throw new \RuntimeException('PPC205_DESIGNATION_INITIALIZATION_IN_FLIGHT');
                }
            }
            foreach (['claims', 'source_fences'] as $field) {
                if (($state['onboarding'][$field] ?? []) !== []) {
                    throw new \RuntimeException('PPC205_DESIGNATION_INITIALIZATION_IN_FLIGHT');
                }
            }
            $terms = ['schema' => self::SCHEMA, 'citadel_id' => $state['citadel_id'] ?? null, 'expected_head' => $head];
            $this->signatures->verify($state, $decision, 'INITIALIZE_FORMATION_PROFILE_DESIGNATIONS', $terms);
            $initialization = ['terms' => $terms, 'decision' => $decision];
            $state['profile_designations'] = ['schema' => self::SCHEMA, 'initialization' => $initialization,
                'delegations' => [], 'events' => [], 'current' => []];
            return $initialization;
        });
    }
}
