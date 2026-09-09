<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\MasterMason;

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationPersonnel, FormationSignatures, FormationInstitution, FormationPublicationEvidence};
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Persistence\{AtomicTransition, ImmutableRecordStore};
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Child constitution is not T01 founding or restoration of self-construction.
 * The only input is an existing Citadel reservation identity, never a root path,
 * caller-authored constitution, or a fabricated qualification record.
 */
final readonly class ChildCuriaFormationService
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private FormationJournal $journal,
        private FormationSignatures $signatures, private FormationPersonnel $personnel, private Clock $clock) {}

    public function publish(string $intakeId): array
    {
        // Serialize the last authority check and bounded local write against revocation.
        // No provider, credential, or external transport participates in this lock.
        return $this->journal->inspect(function (array $frame) use ($intakeId): array {
            $state = $frame['state'];
            $reservation = $state['reservations'][$intakeId] ?? [];
            $prepared = $reservation['prepared'] ?? [];
            if (!in_array($reservation['status'] ?? null, ['EFFECT_UNCERTAIN_IDENTITY_FENCED', 'DELIVERED'], true)
                || ($prepared['curia_id'] ?? null) !== ($reservation['curia_id'] ?? null)
                || ($prepared['packet']['parent_instance_id'] ?? null) !== ($state['parent_instance_id'] ?? null)) {
                throw new \RuntimeException('CMF127_EXACT_CONSTITUTION_FENCE_REQUIRED');
            }
            $child = self::childRoot($this->root, $prepared['curia_id']);
            if (file_exists($child.'/var/imperium/curia/handoffs/'.$prepared['handoff_id'].'.json')) {
                throw new \RuntimeException('CMF131_EXISTING_CHILD_REQUIRES_RECONCILIATION');
            }
            if ($reservation['status'] === 'DELIVERED') {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: delivered receipt missing');
            }
            $constitution = $prepared['constitution'];
            if ($constitution['terms']['expires_at'] <= $this->clock->now()->getTimestamp()) {
                throw new \RuntimeException('CMF094_EXACT_MISSION_APPROVAL_REQUIRED');
            }
            $this->signatures->verify($state, $constitution['decision'], 'APPROVE_MISSION_AND_CONSTITUTION', $constitution['signed_review']);
            $dossiers = $state['dossiers'][$intakeId] ?? [];
            if (($dossiers[count($dossiers) - 1] ?? null) !== $constitution['terms']['dossier']
                || $prepared['packet']['intake']['intent_version'] !== $state['intakes'][$intakeId]['intent_version']
                || $constitution['terms']['dossier']['holder_digest'] !== FormationJournal::digest($this->personnel->currentCourtthane($state))) {
                throw new \RuntimeException('CMF065_DRAFTING_LINEAGE_CHANGED');
            }
            foreach ($constitution['terms']['appointments'] as $seat => $candidate) {
                $holder = $this->personnel->candidate($state, $candidate, $prepared['mission_id'], $seat)
                    + ['generation' => 1, 'curia_id' => $prepared['curia_id']];
                if ($holder !== $constitution['occupants'][$seat]) { throw new \RuntimeException('CMF128_EXACT_CONSTITUTION_HOLDER_REQUIRED'); }
            }
            $institutions = [];
            $institution = new FormationInstitution($this->root);
            foreach (array_keys(FormationInstitution::SEATS) as $role) { $institutions[$role] = $institution->witness($role); }
            $publication = ['schema' => 'imperium.citadel-child-publication/v1',
                'authority_generation' => $frame['generation'], 'authority_frame_digest' => $frame['record_digest'],
                'authorized_at' => $this->clock->now()->getTimestamp(),
                'reservation_digest' => FormationJournal::digest($reservation), 'prepared_digest' => FormationJournal::digest($prepared),
                'institutions' => $institutions];
            // Verify the retained proof before publishing; the same verifier is read-only on recovery.
            FormationPublicationEvidence::verify($this->journal, $frame, $reservation, $publication, $this->clock->now()->getTimestamp());
            return (new ImmutableRecordStore($child, new AtomicTransition($child)))->put('var/imperium/curia/handoffs', $prepared['handoff_id'], [...$prepared, 'publication' => $publication]);
        });
    }

    /** Read-only recognition. Absence grants nothing; the caller must use current authority. */
    public function reconcile(string $intakeId): ?array
    {
        $state = $this->journal->read()['state'];
        $reservation = $state['reservations'][$intakeId] ?? [];
        $prepared = $reservation['prepared'] ?? null;
        if (!is_array($prepared)) { return null; }
        $child = self::childRoot($this->root, $prepared['curia_id']);
        $path = $child.'/var/imperium/curia/handoffs/'.$prepared['handoff_id'].'.json';
        if (!file_exists($path)) { return null; }
        try {
            $receipt = (new ImmutableRecordStore($child, new AtomicTransition($child)))->read('var/imperium/curia/handoffs', $prepared['handoff_id']);
            $content = $receipt;
            unset($content['record_digest'], $content['publication']);
            if (FormationJournal::digest($content) !== FormationJournal::digest($prepared)
                || !is_array($receipt['publication'] ?? null)) { throw new \RuntimeException('exact receipt or publication provenance absent'); }
            $p = $receipt['publication'];
            $frame = $this->journal->historical($p['authority_generation'], $p['authority_frame_digest']);
            FormationPublicationEvidence::verify($this->journal, $frame, $reservation, $p, $this->clock->now()->getTimestamp());
            return $receipt;
        } catch (\Throwable $e) {
            throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE: '.$e->getMessage(), 0, $e);
        }
    }

    public static function childRoot(string $root, string $id): string
    {
        if (!preg_match('/^curia-[a-f0-9]{32}$/D', $id)) { throw new \RuntimeException('CMF095_CHILD_ID_INVALID'); }
        $path = realpath($root);
        if ($path === false) { throw new \RuntimeException('CMF096_CHILD_ROOT_ALIAS_REFUSED'); }
        foreach (['var', 'imperium', 'citadel', 'children', $id] as $segment) {
            $path .= '/'.$segment;
            $resolved = realpath($path);
            if (is_link($path) || ($resolved !== false && strcasecmp(str_replace('\\', '/', $resolved), str_replace('\\', '/', $path)) !== 0)) {
                throw new \RuntimeException('CMF096_CHILD_ROOT_ALIAS_REFUSED');
            }
        }
        return $path;
    }
}
