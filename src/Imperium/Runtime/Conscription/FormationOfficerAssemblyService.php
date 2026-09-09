<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Imperium\Runtime\Citadel\Formation\FormationJournal;

/** Mechanical formation assembly, downstream of authenticated qualification.
 * A returned object is not an appointment: consumers must revalidate the original
 * candidate chain and exact appointment decision in their own transaction.
 */
final class FormationOfficerAssemblyService
{
    public static function assemble(array $profile, array $candidate, array $qualifier, string $scope, string $seat, array $lifecycle): array
    {
        $manifestation = 'formation-manifestation-'.substr(FormationJournal::digest([$scope, $seat, $candidate, $profile['content_digest']]), 0, 32);
        // The new permanent reception Seat has explicit tenure; old assembled
        // evidence keeps its original shape for exact historical verification.
        return ($seat === 'courtyard.courtthane' ? ['officer_class' => 'LEGATE'] : [])
            + ['manifestation_id' => $manifestation, 'seat' => $seat, 'scope' => $scope, 'candidate' => $candidate,
            'profile_artifact' => $profile, 'profile_lifecycle' => $lifecycle,
            'assembler' => $qualifier, 'assembly_status' => 'ASSEMBLED_FOR_EXACT_APPOINTMENT',
            'substrate' => ['kind' => 'generic-officer', 'version' => 0, 'identity_contribution' => false, 'authority_contribution' => false],
            'cognitive_artifact' => $profile['cognitive_payload']['instructions'],
            'activation_performed' => false, 'execution_authority' => false];
    }
}
