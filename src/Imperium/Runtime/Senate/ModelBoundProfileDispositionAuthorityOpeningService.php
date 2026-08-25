<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;

final readonly class ModelBoundProfileDispositionAuthorityOpeningService
{
    private string $reconciliations;
    private string $occupancy;
    private string $openings;
    public function __construct(string $root)
    {
        $s = $root.'/var/imperium/offices/senate';
        $this->reconciliations = $s.'/model-bound-profile-reconciliations';
        $this->occupancy = $s.'/occupancy';
        $this->openings = $s.'/model-bound-profile-disposition-authority-openings';
    }
    public function open(string $id,
    string $authorityId,
    string $bindingId): array {
        $r = $this->read($this->reconciliations.'/'.$id.'.json',
        'S295_MODEL_BOUND_RECONCILIATION_ABSENT');
        $l = $this->read($this->occupancy.'/'.$bindingId.'.json',
        'S296_LORD_SPEAKER_UNAVAILABLE');
        $a = $r['disposition_phase_opening_authority'] ?? [];
        if (!$this->ok($r) ||
        !$this->ok($l) ||
        'PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING' !== ($r['status'] ?? null) ||
        $authorityId !== ($a['authority_id'] ?? null) ||
        true !== ($a['authority_single_use'] ?? null) ||
        'senate.lord-speaker' !== ($l['seat'] ?? null) ||
        'ACTIVE' !== ($l['status'] ?? null) ||
        true !== ($l['binding_atomic'] ?? null) ||
        true !== ($l['profile_examination_disposition_phase_opening_authority'] ?? null) ||
        true === ($l['execution_authority'] ?? null)) throw new \RuntimeException('S297_MODEL_BOUND_DISPOSITION_OPENING_CHAIN_INVALID');
        $actor = ['seat' => 'senate.lord-speaker',
        'binding_id' => $bindingId,
        'binding_digest' => $l['record_digest'],
        'manifestation_id' => $l['manifestation_id'],
        'occupancy_generation' => $l['occupancy_generation']];
        $did = 'model-bound-profile-disposition-authority-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $r['record_digest'],
        $actor])),
        0,
        20);
        $oid = 'model-bound-profile-disposition-authority-opening-'.substr(hash('sha256',
        CanonicalJson::encode([$did,
        $authorityId])),
        0,
        20);
        return $this->save($oid,
        ['schema' => 'imperium.senate-model-bound-profile-disposition-authority-opening/v1',
        'opening_id' => $oid,
        'instance_id' => $r['instance_id'],
        'case_id' => $r['case_id'],
        'case_digest' => $r['case_digest'],
        'source_reconciliation' => ['id' => $id,
        'digest' => $r['record_digest']],
        'subject_profile' => $r['subject_profile'],
        'mandatory_security_blocking_condition' => $r['mandatory_security_blocking_condition'],
        'lord_speaker' => $actor,
        'phase_opening_authority' => ['id' => $authorityId,
        'consumed' => true],
        'disposition_authority' => ['authority_id' => $did,
        'authority_single_use' => true,
        'permitted_dispositions' => ['APPROVED',
        'RETURN_FOR_REVISION',
        'REFUSED',
        'UNRESOLVED'],
        'security_block_must_be_preserved' => true],
        'status' => 'PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION',
        'senate_disposition_authority' => true,
        'disposition' => null,
        'vote_authority' => false,
        'aggregation_authority' => false,
        'profile_approval_authority' => false,
        'profile_activation_authority' => false,
        'deployment_authority' => false,
        'execution_authority' => false,
        'sealed' => true]);
    }
    private function read($p,
    $e): array {
        if (!is_file($p)) throw new \RuntimeException($e);
        return json_decode((string)file_get_contents($p),
        true,
        512,
        JSON_THROW_ON_ERROR);
    }
    private function ok(array $r): bool
    {
        $d = $r['record_digest'] ?? null;
        unset($r['record_digest']);
        return is_string($d) &&
        hash_equals($d,
        hash('sha256',
        CanonicalJson::encode($r)));
    }
    private function save($id,
    array $r): array {
        if (!is_dir($this->openings))mkdir($this->openings,
        0770,
        true);
        $r['record_digest'] = hash('sha256',
        CanonicalJson::encode($r));
        $p = $this->openings.'/'.$id.'.json';
        if (is_file($p)) return $this->read($p,
        'S298_MODEL_BOUND_DISPOSITION_OPENING_CONFLICT');
        file_put_contents($p,
        json_encode($r,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",
        LOCK_EX);
        return $r;
    }
}
