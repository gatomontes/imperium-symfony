<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;

final readonly class AuthorshipCommissionAcceptanceService
{
    private string $officeRoot; private string $productionCaseDirectory;
    public function __construct(string $projectDir) { $this->officeRoot = $projectDir.'/var/imperium/offices'; $this->productionCaseDirectory = $projectDir.'/var/imperium/offices/foundry/production-cases'; }
    public function accept(string $office, string $commissionId, string $bindingId): array
    {
        [$role, $seat, $subordinate] = match ($office) { 'hagiography' => ['sanctographer', 'hagiography.sanctographer', 'Chronicler'], 'studium' => ['chancellor', 'studium.chancellor', 'Notary'], default => throw new \InvalidArgumentException('A69_AUTHORSHIP_OFFICE_INVALID') };
        if (!preg_match('/^authorship-'.$office.'-[a-f0-9]{20}$/', $commissionId)) throw new \InvalidArgumentException('A70_COMMISSION_INVALID');
        if (!preg_match('/^'.$office.'-'.$role.'-binding-[a-f0-9]{20}$/', $bindingId)) throw new \InvalidArgumentException('A71_BINDING_INVALID');
        $commission = $this->read($this->officeRoot.'/'.$office.'/inbox/'.$commissionId.'.json', 'A72_COMMISSION_ABSENT');
        if (!$this->digestMatches($commission) || $commissionId !== ($commission['commission_id'] ?? null) || 'imperium.specialized-authorship-commission/v1' !== ($commission['schema'] ?? null)
            || $office !== ($commission['office'] ?? null) || $seat !== ($commission['target_seat'] ?? null) || 'ISSUED_PENDING_RECIPIENT' !== ($commission['status'] ?? null)
            || true !== ($commission['authorship_authority'] ?? null) || null !== ($commission['recipient_acceptance'] ?? null) || true === ($commission['persona_selection_authority'] ?? null)
            || true === ($commission['persona_assembly_authority'] ?? null) || true === ($commission['spawning_authority'] ?? null) || true === ($commission['admission_authority'] ?? null)
            || true === ($commission['seat_binding_authority'] ?? null) || true === ($commission['execution_authority'] ?? null)) throw new \RuntimeException('A73_COMMISSION_INVALID');
        $binding = $this->read($this->officeRoot.'/'.$office.'/occupancy/'.$bindingId.'.json', 'A74_BINDING_ABSENT');
        if (!$this->digestMatches($binding) || $bindingId !== ($binding['binding_id'] ?? null) || 'imperium.authorship-resident-occupancy/v1' !== ($binding['schema'] ?? null)
            || $office !== ($binding['office'] ?? null) || $seat !== ($binding['seat'] ?? null) || 'ACTIVE' !== ($binding['status'] ?? null) || true !== ($binding['binding_atomic'] ?? null)
            || 1 !== ($binding['occupancy_generation'] ?? null) || true !== ($binding['authorship_authority'] ?? null) || true !== ($binding['subordinate_staff_resolution_authority'] ?? null)
            || true === ($binding['recipient_acceptance'] ?? null) || true === ($binding['execution_authority'] ?? null) || ($commission['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)) throw new \RuntimeException('A75_BINDING_INVALID');
        $caseId = $commission['production_case_id'] ?? null; $case = is_string($caseId) ? $this->read($this->productionCaseDirectory.'/'.$caseId.'.json', 'A76_COMMISSION_CHAIN_INVALID') : [];
        if (!$this->digestMatches($case) || ($commission['production_case_digest'] ?? null) !== ($case['record_digest'] ?? null) || ($commission['instance_id'] ?? null) !== ($case['instance_id'] ?? null)
            || 'OPEN_PENDING_SPECIALIZED_INPUTS' !== ($case['status'] ?? null) || ($commission['profession'] ?? null) !== ($case['profession'] ?? null)) throw new \RuntimeException('A76_COMMISSION_CHAIN_INVALID');
        return $this->persist($office, ['schema' => 'imperium.authorship-commission-acceptance/v1', 'acceptance_id' => $office.'-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $bindingId, $binding['record_digest']])), 0, 20), 'instance_id' => $binding['instance_id'], 'office' => $office, 'commission_id' => $commissionId, 'commission_digest' => $commission['record_digest'], 'binding_id' => $bindingId, 'binding_digest' => $binding['record_digest'], 'production_case_id' => $caseId, 'production_case_digest' => $case['record_digest'], 'actor' => ['seat' => $seat, 'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation']], 'disposition' => 'ACCEPTED_FOR_RESIDENT_AUTHORSHIP', 'authorship_class' => $commission['authorship_class'], 'required_product' => $commission['required_product'], 'forbidden_authorship' => $commission['forbidden_authorship'], 'recipient_acceptance' => true, 'authorship_authority' => true, 'authorship_authority_exercisable' => true, 'subordinate_staff_class' => $subordinate, 'subordinate_staff_resolution_authority' => true, 'subordinate_staff_resolution_pending' => true, 'persona_selection_authority' => false, 'persona_assembly_authority' => false, 'spawning_authority' => false, 'admission_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false]);
    }
    private function read(string $path,string $error):array{if(!is_file($path))throw new \RuntimeException($error);return json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}
    private function digestMatches(array $record):bool{$digest=$record['record_digest']??null;unset($record['record_digest']);return is_string($digest)&&hash_equals($digest,hash('sha256',CanonicalJson::encode($record)));}
    private function persist(string $office,array $record):array{$directory=$this->officeRoot.'/'.$office.'/acceptances';if(!is_dir($directory)&&!mkdir($directory,0770,true)&&!is_dir($directory))throw new \RuntimeException('Authorship acceptance directory cannot be created.');$record['record_digest']=hash('sha256',CanonicalJson::encode($record));$path=$directory.'/'.$record['acceptance_id'].'.json';if(is_file($path)){$old=$this->read($path,'A77_ACCEPTANCE_REPLAY_CONFLICT');if(CanonicalJson::encode($old)!==CanonicalJson::encode($record))throw new \RuntimeException('A77_ACCEPTANCE_REPLAY_CONFLICT');return $old;}foreach(glob($directory.'/'.$office.'-acceptance-*.json')?:[]as$existing){$old=$this->read($existing,'A77_ACCEPTANCE_REPLAY_CONFLICT');if($record['commission_id']===($old['commission_id']??null))throw new \RuntimeException('A78_COMMISSION_ALREADY_DISPOSED');}$tmp=$path.'.tmp.'.bin2hex(random_bytes(6));if(false===file_put_contents($tmp,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)||!rename($tmp,$path)){@unlink($tmp);throw new \RuntimeException('Authorship acceptance cannot be committed atomically.');}return $record;}
}
