<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;

/** Actual current native projection; admission and assignment remain separate owners. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FreshInstitutionalProfileMapping
{
    public function __construct(private string $root, private AuthorityStore $store) {}
    public function prepareInOwner(FormationOwnerFrame $owner, string $seat): array
    {
        $owner->assertOwner($this->store->journal);
        FormationFreshEstablishment::completedInOwner($this->root, $owner);
        if (!in_array($seat, FormationProfileDesignation::TARGETS, true)) { throw new \RuntimeException('PPC7_MAPPING_SEAT'); }
        $institution = new FormationInstitution($this->root);
        $signatures = new FormationSignatures($this->store->journal, $this->store->clock);
        $personnel = new FormationPersonnel($this->store->journal, $signatures, $this->store->clock, $institution);
        $designations = new FormationProfileDesignation($this->store->journal, $signatures, $this->store->clock, $institution, $personnel);
        $designation = $designations->currentInOwner($owner, $seat);
        $holder = $seat === 'courtyard.courtthane' ? $personnel->currentCourtthaneInOwner($owner) : $personnel->currentLocksmithInOwner($owner);
        if (FormationJournal::digest($holder['candidate']) !== FormationJournal::digest($designation['event']['candidate'])) {
            throw new \RuntimeException('PPC7_MAPPING_INDEPENDENT_HOLDER');
        }
        $mapping = ['schema' => 'imperium.formation-profile-mapping/v1', 'instance_id' => $this->store->instance,
            'formation_citadel_id' => $this->store->citadel, 'seat' => $seat, 'holder_generation' => $holder['generation'],
            'profile_evidence_digest' => $holder['candidate']['profile'], 'profile_artifact' => $holder['profile_artifact']];
        $bytes = CanonicalJson::encode($mapping);
        return $this->store->make('imperium.bootstrap-source/v1', 'fresh-mapping-'.FormationJournal::digest($mapping),
            ['kind' => 'formation-profile-mapping', 'content' => $bytes, 'content_digest' => 'sha256:'.hash('sha256', $bytes),
                'limitations' => 'Native current projection only. Separate O4 admission, application and use remain required. No dispatch authority.']);
    }
}
