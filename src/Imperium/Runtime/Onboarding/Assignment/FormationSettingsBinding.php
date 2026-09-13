<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal,FormationEffectiveConfiguration};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** Fixed deployment composition. Like FormationSessionAuthority, callers supply
 * only their own authoritative journal frame, under its existing lock. This
 * verifier neither opens a journal nor grants personnel/session/custody rights.
 */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FormationSettingsBinding
{
    public function __construct(private PersistentSettings $settings, private string $role) {}
    public function verify(FormationJournal $journal, array $state, array $request, array $terms,
        object $adapter, ?array $operation = null): void
    {
        $this->settings->assertFormationOwner($journal,$state);
        R::require($adapter instanceof FormationEffectiveConfiguration,'SETTINGS_EFFECTIVE_CONFIGURATION_REQUIRED');
        $this->settings->verifyFormation($journal,$state,$this->role,$request,$terms,
            $adapter->effectiveConfiguration($request,$terms,$operation));
    }
}
