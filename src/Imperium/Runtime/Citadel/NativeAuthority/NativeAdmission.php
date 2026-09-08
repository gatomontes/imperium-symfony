<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;

/** Native custody and disposition are co-published in the registry, never split files. */
final class NativeAdmission
{
    public static function build(string $id, array $d, array $effective, array $original, array $roster, array $revision): array
    {
        A::intact($d); A::intact($effective);
        A::require(($d['route_class'] ?? null) === 'CANONICAL_GUILDHALL_TO_GARRISON'
            && ($d['status'] ?? null) === 'DELIVERED_PENDING_CONSTABLE_ADMISSION_DISPOSITION'
            && ($d['recipient']['seat'] ?? null) === 'garrison.constable' && ($d['instance_id'] ?? null) === $original['instance_id']
            && ($effective['persona_admission_disposition_authority'] ?? null) === true && ($effective['custody_registration_authority'] ?? null) === true
            && ($effective['selection_authority'] ?? null) === false && ($effective['execution_authority'] ?? null) === false
            && ($d['admission_authority'] ?? false) === false, 'NAT041_ADMISSION_CHAIN_INVALID');
        foreach (['candidate_id', 'persona_name', 'persona_specification_version', 'senate_confirmation_record_id', 'originating_guildhall_commission_id'] as $key) {
            A::require(is_string($d[$key] ?? null) && $d[$key] !== '', 'NAT041_ADMISSION_CHAIN_INVALID');
        }
        foreach (['candidate_digest', 'senate_confirmation_record_digest', 'originating_guildhall_commission_digest'] as $key) { A::hex($d[$key] ?? null); }
        A::require(is_array($d['persona'] ?? null) && A::digest($d['persona']) === $d['candidate_digest'], 'NAT041_ADMISSION_CHAIN_INVALID');
        $dispositionId = 'native-garrison-admission-'.A::digest([$id, $d['record_digest'], $original['record_digest'], $revision['record_digest']]);
        $custody = A::seal(['schema' => 'imperium.garrison-persona-custody/v1', 'custody_id' => 'native-custody-'.A::digest($dispositionId),
            'instance_id' => $d['instance_id'], 'persona_id' => $d['candidate_id'], 'persona_version' => $d['persona_specification_version'],
            'persona_name' => $d['persona_name'], 'persona_digest' => $d['candidate_digest'], 'persona' => $d['persona'],
            'originating_guildhall_commission_id' => $d['originating_guildhall_commission_id'], 'originating_guildhall_commission_digest' => $d['originating_guildhall_commission_digest'],
            'senate_confirmation_record_id' => $d['senate_confirmation_record_id'], 'senate_confirmation_record_digest' => $d['senate_confirmation_record_digest'],
            'admission_disposition_id' => $dispositionId, 'custody_state' => 'ADMITTED_HELD', 'available' => true, 'execution_authority' => false, 'sealed' => true]);
        $disposition = A::seal(['schema' => 'imperium.native-garrison-admission-disposition/v1', 'disposition_id' => $dispositionId,
            'source_delivery_id' => $id, 'source_delivery_digest' => $d['record_digest'], 'instance_id' => $d['instance_id'],
            'candidate_id' => $d['candidate_id'], 'candidate_digest' => $d['candidate_digest'], 'constable' => [
                'binding_id' => $original['binding_id'], 'binding_digest' => $original['record_digest'], 'manifestation_id' => $original['manifestation_id'],
                'occupancy_generation' => $original['occupancy_generation'], 'roster_digest' => $roster['record_digest'], 'authority_revision_digest' => $revision['record_digest']],
            'disposition' => 'ADMITTED', 'custody_created' => true, 'custody_id' => $custody['custody_id'], 'custody_digest' => $custody['record_digest'],
            'admission_authority' => false, 'sealed' => true, ...A::flags()]);
        return ['custody' => $custody, 'disposition' => $disposition];
    }
}
