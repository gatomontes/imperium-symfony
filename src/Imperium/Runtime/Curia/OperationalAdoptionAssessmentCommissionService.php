<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionAssessmentCommissionService
{
    private const JURISDICTIONS = ['EVIDENCE_SUFFICIENCY', 'MISSION_OPERATIONAL_FIT', 'RISK_AUTHORITY_REVERSIBILITY'];

    private string $openings;
    private string $occupancy;
    private string $issuances;
    private string $commissions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->openings = $root.'/var/imperium/operational/legate-result-adoption-evaluation-openings';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->issuances = $root.'/var/imperium/operational/legate-result-adoption-assessment-issuances';
        $this->commissions = $root.'/var/imperium/operational/legate-result-adoption-assessment-commissions';
    }

    public function issue(string $openingId, string $seneschalBindingId, array $curialisBindingsByJurisdiction, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^legate-result-adoption-evaluation-opening-[a-f0-9]{20}$/', $openingId)) {
            throw new \InvalidArgumentException('CUR470_ADOPTION_EVALUATION_OPENING_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) {
            throw new \InvalidArgumentException('CUR471_SENESCHAL_BINDING_ID_INVALID');
        }
        if (self::JURISDICTIONS !== array_keys($curialisBindingsByJurisdiction)
            || 3 !== count(array_unique(array_values($curialisBindingsByJurisdiction)))) {
            throw new \InvalidArgumentException('CUR472_CURIAL_COMPOSITION_INVALID');
        }

        $opening = $this->read($this->openings.'/'.$openingId.'.json', 'CUR473_ADOPTION_EVALUATION_OPENING_ABSENT');
        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR474_SENESCHAL_OCCUPANCY_ABSENT');
        $this->validateOpening($openingId, $opening, $seneschalBindingId, $seneschal);

        $composition = [];
        foreach ($curialisBindingsByJurisdiction as $jurisdiction => $bindingId) {
            if (!is_string($bindingId) || !preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $bindingId)) {
                throw new \InvalidArgumentException('CUR472_CURIAL_COMPOSITION_INVALID');
            }
            $occupant = $this->read($this->occupancy.'/'.$bindingId.'.json', 'CUR475_CURIALIS_OCCUPANCY_ABSENT');
            $this->validateCurialis($opening, $jurisdiction, $bindingId, $occupant);
            $this->assertSoleCurrentOccupancy($occupant);
            $composition[$jurisdiction] = [
                'seat' => $occupant['seat'], 'binding_id' => $bindingId, 'binding_digest' => $occupant['record_digest'],
                'manifestation_id' => $occupant['manifestation_id'], 'occupancy_generation' => $occupant['occupancy_generation'],
                'officer_class' => $occupant['officer_class'], 'source_qualification' => $occupant['source_qualification'],
            ];
        }

        $issuanceId = 'legate-result-adoption-assessment-issuance-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $seneschalBindingId, $composition])), 0, 20);
        $existing = $this->issuances.'/'.$issuanceId.'.json';
        if (is_file($existing)) {
            return $this->read($existing, 'CUR479_ASSESSMENT_COMMISSION_CONFLICT');
        }
        foreach (glob($this->issuances.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR479_ASSESSMENT_COMMISSION_CONFLICT');
            if (($prior['source_evaluation_opening']['id'] ?? null) === $openingId) {
                throw new \RuntimeException('CUR479_ASSESSMENT_COMMISSION_CONFLICT');
            }
        }

        $commissions = [];
        foreach ($opening['evaluation_contract']['required_judgments'] as $judgment) {
            $jurisdiction = $judgment['jurisdiction'];
            $commissionId = 'legate-result-adoption-assessment-commission-'.substr(hash('sha256', CanonicalJson::encode([$issuanceId, $jurisdiction, $composition[$jurisdiction]])), 0, 20);
            $commissions[$jurisdiction] = $this->save($this->commissions, $commissionId, [
                'schema' => 'imperium.legate-result-adoption-assessment-commission/v1',
                'commission_id' => $commissionId, 'issuance_id' => $issuanceId, 'instance_id' => $opening['instance_id'],
                'source_evaluation_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
                'issuer' => $opening['presiding_seneschal'], 'target' => $composition[$jurisdiction],
                'jurisdiction' => $jurisdiction, 'question' => $judgment['question'],
                'result' => $opening['result'], 'contract' => $opening['contract'],
                'status' => 'ISSUED_PENDING_CURIALIS_ACCEPTANCE', 'recipient_acceptance_required' => true,
                'commission_exercisable' => false, 'assessment_authority' => false, 'governed_cognition_authority' => false,
                'provider_invocation_authority' => false, 'credential_use_authority' => false, 'tool_use_authority' => false,
                'operational_use_permitted' => false, 'external_action_authority' => false, 'execution_authority' => false,
                'continuing_turn_authority' => false, 'sealed' => true,
            ]);
        }

        return $this->save($this->issuances, $issuanceId, [
            'schema' => 'imperium.legate-result-adoption-assessment-issuance/v1',
            'issuance_id' => $issuanceId, 'instance_id' => $opening['instance_id'],
            'source_evaluation_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'presiding_seneschal' => $opening['presiding_seneschal'], 'curial_composition' => $composition,
            'commissions' => array_map(static fn (array $record): array => ['id' => $record['commission_id'], 'digest' => $record['record_digest']], $commissions),
            'issued_at' => $issuedAt->format(DATE_ATOM),
            'status' => 'LEGATE_RESULT_ADOPTION_CURIAL_COMPOSITION_RESOLVED_ASSESSMENT_COMMISSIONS_ISSUED_PENDING_ACCEPTANCE',
            'commission_closed' => true, 'curial_composition_resolved' => true, 'assessment_commissions_issued' => true,
            'assessment_commissions_accepted' => false, 'assessment_authority' => false, 'result_evaluated_for_adoption' => false,
            'result_operationally_adopted' => false, 'planning_amendment_authority' => false, 'governed_cognition_authority' => false,
            'provider_invocation_authority' => false, 'credential_use_authority' => false, 'operational_use_permitted' => false,
            'tool_use_authority' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'continuing_turn_authority' => false, 'sealed' => true,
        ]);
    }

    private function validateOpening(string $id, array $opening, string $seneschalId, array $seneschal): void
    {
        if (!$this->valid($opening) || 'imperium.legate-result-adoption-evaluation-opening/v1' !== ($opening['schema'] ?? null)
            || $id !== ($opening['opening_id'] ?? null) || 'LEGATE_RESULT_ADOPTION_EVALUATION_OPENED_PENDING_CURIAL_COMPOSITION_NO_ASSESSMENT_AUTHORITY' !== ($opening['status'] ?? null)
            || false !== ($opening['curial_composition_resolved'] ?? null) || false !== ($opening['assessment_commissions_issued'] ?? null)
            || true === ($opening['assessment_authority'] ?? null) || true === ($opening['result_operationally_adopted'] ?? null)
            || true === ($opening['execution_authority'] ?? null) || true !== ($opening['sealed'] ?? null)
            || $seneschalId !== ($opening['presiding_seneschal']['binding_id'] ?? null) || !$this->valid($seneschal)
            || $seneschalId !== ($seneschal['binding_id'] ?? null) || 'curia.seneschal' !== ($seneschal['seat'] ?? null)
            || ($opening['presiding_seneschal']['binding_digest'] ?? null) !== ($seneschal['record_digest'] ?? null)
            || 'ACTIVE' !== ($seneschal['status'] ?? null)) {
            throw new \RuntimeException('CUR476_ASSESSMENT_COMMISSION_CHAIN_INVALID');
        }
        $this->assertSoleCurrentOccupancy($seneschal);
    }

    private function validateCurialis(array $opening, string $jurisdiction, string $bindingId, array $occupant): void
    {
        if (!$this->valid($occupant) || $bindingId !== ($occupant['binding_id'] ?? null)
            || ($opening['instance_id'] ?? null) !== ($occupant['instance_id'] ?? null)
            || 1 !== preg_match('/^curia\.curialis\.[a-z0-9-]+$/', (string) ($occupant['seat'] ?? ''))
            || !in_array($occupant['officer_class'] ?? null, [OfficerClass::Legate->value, OfficerClass::Delegate->value], true)
            || !is_array($occupant['source_qualification'] ?? null) || !is_string($occupant['source_qualification']['id'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', (string) ($occupant['source_qualification']['digest'] ?? ''))
            || 'ACTIVE' !== ($occupant['status'] ?? null) || true !== ($occupant['binding_atomic'] ?? null)
            || true !== ($occupant['sealed'] ?? null) || !in_array($jurisdiction, self::JURISDICTIONS, true)) {
            throw new \RuntimeException('CUR477_CURIALIS_QUALIFICATION_OR_OCCUPANCY_INVALID');
        }
    }

    private function assertSoleCurrentOccupancy(array $occupant): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CUR478_CURIAL_OCCUPANCY_CONFLICT');
            if (($other['seat'] ?? null) === ($occupant['seat'] ?? null) && ($other['binding_id'] ?? null) !== ($occupant['binding_id'] ?? null) && 'ACTIVE' === ($other['status'] ?? null)) {
                throw new \RuntimeException('CUR478_CURIAL_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $directory, string $id, array $record): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException('CUR480_ASSESSMENT_COMMISSION_PERSISTENCE_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $directory.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CUR479_ASSESSMENT_COMMISSION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) throw new \RuntimeException('CUR479_ASSESSMENT_COMMISSION_CONFLICT');
            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) throw new \RuntimeException('CUR480_ASSESSMENT_COMMISSION_PERSISTENCE_FAILED');
        return $record;
    }
}
