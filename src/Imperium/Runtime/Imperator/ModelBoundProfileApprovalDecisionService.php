<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ModelBoundProfileApprovalDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private const array DISPOSITIONS = ['APPROVED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];
    private string $dispositions;
    private string $reconciliations;
    private string $findings;
    private string $decisions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->dispositions = $senate.'/model-bound-profile-dispositions';
        $this->reconciliations = $senate.'/model-bound-profile-reconciliations';
        $this->findings = $senate.'/model-bound-profile-senator-findings';
        $this->decisions = $root.'/var/imperium/imperator/model-bound-profile-approval-decisions';
    }

    public function decide(string $sourceId, string $disposition, string $response, string $limitations): array
    {
        if (!preg_match('/^model-bound-profile-disposition-[a-f0-9]{20}$/', $sourceId)) {
            throw new \InvalidArgumentException('I221_MODEL_BOUND_PROFILE_DISPOSITION_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $response = trim($response);
        $limitations = trim($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $response || '' === $limitations) {
            throw new \InvalidArgumentException('I222_MODEL_BOUND_PROFILE_APPROVAL_DISPOSITION_INVALID');
        }

        $senate = $this->read($this->dispositions.'/'.$sourceId.'.json', 'I223_MODEL_BOUND_PROFILE_DISPOSITION_ABSENT');
        $this->validateSenateDisposition($senate, $sourceId);
        [$reconciliation, $findings] = $this->validateCompleteSenateRecord($senate);

        if ('APPROVED' === $disposition && ('APPROVED' !== ($senate['decision']['disposition'] ?? null) || true === $senate['mandatory_security_blocking_condition'])) {
            throw new \RuntimeException('I225_MODEL_BOUND_SENATE_DISPOSITION_NOT_APPROVED');
        }

        foreach (glob($this->decisions.'/model-bound-profile-approval-decision-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'I228_MODEL_BOUND_PROFILE_APPROVAL_DECISION_CONFLICT');
            if (($prior['source_senate_disposition']['id'] ?? null) !== $sourceId) {
                continue;
            }
            if (($prior['disposition'] ?? null) === $disposition && ($prior['response'] ?? null) === $response && ($prior['limitations'] ?? null) === $limitations) {
                return $prior;
            }
            throw new \RuntimeException('I228_MODEL_BOUND_PROFILE_APPROVAL_DECISION_CONFLICT');
        }

        $approved = 'APPROVED' === $disposition;
        $id = 'model-bound-profile-approval-decision-'.substr(hash('sha256', CanonicalJson::encode([$sourceId, $senate['record_digest'], self::IMPERATOR_ID, $disposition, $response, $limitations])), 0, 20);
        $qualificationAuthorityId = $approved ? 'operational-qualification-request-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $sourceId, $senate['record_digest']])), 0, 20) : null;

        return $this->save($id, [
            'schema' => 'imperium.imperator-model-bound-profile-approval-decision/v1',
            'decision_id' => $id,
            'instance_id' => $senate['instance_id'],
            'case_id' => $senate['case_id'],
            'case_digest' => $senate['case_digest'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_senate_disposition' => ['id' => $sourceId, 'digest' => $senate['record_digest'], 'decision' => $senate['decision']],
            'source_reconciliation' => ['id' => $senate['source_reconciliation']['id'], 'digest' => $reconciliation['record_digest']],
            'subject_profile' => $senate['subject_profile'],
            'admitted_findings' => $senate['admitted_findings'],
            'reconciliation' => $senate['reconciliation'],
            'mandatory_security_blocking_condition' => $senate['mandatory_security_blocking_condition'],
            'lord_speaker' => $senate['lord_speaker'],
            'senate_disposition_authority_consumed' => true,
            'verified_finding_digests' => array_column($findings, 'record_digest'),
            'disposition' => $disposition,
            'response' => $response,
            'limitations' => $limitations,
            'status' => $approved ? 'IMPERATOR_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION' : 'NON_APPROVING_IMPERATOR_PROFILE_DISPOSITION_RECORDED',
            'imperator_profile_approval_consumed' => true,
            'profile_approved' => $approved,
            'operational_qualification_request_authority' => $approved,
            'operational_qualification_request' => $approved ? ['authority_id' => $qualificationAuthorityId, 'authority_single_use' => true, 'destination' => 'conscription.recruiter', 'purpose' => 'REQUEST_ONE_EXACT_OPERATIONAL_PROFILE_QUALIFICATION', 'consumed' => false] : null,
            'operational_qualification_authority' => false,
            'profile_installation_authority' => false,
            'profile_activation_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'custody_transfer_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'provider_invocation_authority' => false,
            'external_action_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validateSenateDisposition(array $senate, string $sourceId): void
    {
        if (!$this->valid($senate)
            || 'imperium.senate-model-bound-profile-disposition/v1' !== ($senate['schema'] ?? null)
            || $sourceId !== ($senate['disposition_id'] ?? null)
            || 'PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL' !== ($senate['status'] ?? null)
            || true !== ($senate['senate_disposition_authority_consumed'] ?? null)
            || true !== ($senate['imperator_profile_approval_pending'] ?? null)
            || true === ($senate['profile_approval_authority'] ?? null)
            || true === ($senate['profile_installation_authority'] ?? null)
            || true === ($senate['profile_activation_authority'] ?? null)
            || true === ($senate['operational_qualification_authority'] ?? null)
            || true === ($senate['manifestation_assembly_authority'] ?? null)
            || true === ($senate['seat_binding_authority'] ?? null)
            || true === ($senate['custody_transfer_authority'] ?? null)
            || true === ($senate['tool_use_authority'] ?? null)
            || true === ($senate['credential_use_authority'] ?? null)
            || true === ($senate['provider_invocation_authority'] ?? null)
            || true === ($senate['external_action_authority'] ?? null)
            || true === ($senate['deployment_authority'] ?? null)
            || true === ($senate['execution_authority'] ?? null)
            || true !== ($senate['disposition_authority']['consumed'] ?? null)
            || false !== ($senate['disposition_authority']['continuing_authority'] ?? null)
            || true !== ($senate['sealed'] ?? null)
            || !is_array($senate['subject_profile'] ?? null)
            || !is_array($senate['admitted_findings'] ?? null)
            || 3 !== count($senate['admitted_findings'])
            || !is_array($senate['reconciliation'] ?? null)
            || !is_bool($senate['mandatory_security_blocking_condition'] ?? null)
            || !in_array($senate['decision']['disposition'] ?? null, ['APPROVED', 'RETURN_FOR_REVISION', 'REFUSED', 'UNRESOLVED'], true)) {
            throw new \RuntimeException('I224_MODEL_BOUND_PROFILE_APPROVAL_CHAIN_INVALID');
        }
    }

    private function validateCompleteSenateRecord(array $senate): array
    {
        $source = $senate['source_reconciliation'] ?? [];
        $reconciliation = $this->read($this->reconciliations.'/'.($source['id'] ?? '').'.json', 'I226_MODEL_BOUND_RECONCILIATION_ABSENT');
        if (!$this->valid($reconciliation)
            || 'imperium.senate-model-bound-profile-reconciliation/v1' !== ($reconciliation['schema'] ?? null)
            || ($source['id'] ?? null) !== ($reconciliation['reconciliation_id'] ?? null)
            || ($source['digest'] ?? null) !== $reconciliation['record_digest']
            || 'PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING' !== ($reconciliation['status'] ?? null)
            || true !== ($reconciliation['reconciliation_authority']['consumed'] ?? null)
            || false !== ($reconciliation['reconciliation_authority']['continuing_authority'] ?? null)
            || true !== ($reconciliation['sealed'] ?? null)
            || ($senate['subject_profile'] ?? null) !== ($reconciliation['subject_profile'] ?? null)
            || ($senate['admitted_findings'] ?? null) !== ($reconciliation['admitted_findings'] ?? null)
            || ($senate['reconciliation'] ?? null) !== ($reconciliation['reconciliation'] ?? null)
            || ($senate['mandatory_security_blocking_condition'] ?? null) !== ($reconciliation['mandatory_security_blocking_condition'] ?? null)) {
            throw new \RuntimeException('I224_MODEL_BOUND_PROFILE_APPROVAL_CHAIN_INVALID');
        }
        $findings = [];
        foreach ($senate['admitted_findings'] as $reference) {
            $finding = $this->read($this->findings.'/'.($reference['finding_id'] ?? '').'.json', 'I227_MODEL_BOUND_FINDING_ABSENT');
            if (!$this->valid($finding)
                || ($reference['finding_digest'] ?? null) !== $finding['record_digest']
                || ($reference['jurisdiction'] ?? null) !== ($finding['jurisdiction'] ?? null)
                || ($reference['decision'] ?? null) !== ($finding['decision'] ?? null)
                || ($reference['mandatory_security_blocking_condition'] ?? null) !== ($finding['mandatory_security_blocking_condition'] ?? null)) {
                throw new \RuntimeException('I224_MODEL_BOUND_PROFILE_APPROVAL_CHAIN_INVALID');
            }
            $findings[] = $finding;
        }
        if (['trust', 'security', 'usability'] !== array_column($findings, 'jurisdiction')) {
            throw new \RuntimeException('I224_MODEL_BOUND_PROFILE_APPROVAL_CHAIN_INVALID');
        }
        $findingReferences = array_map(static fn (array $finding): string => 'finding:'.$finding['jurisdiction'].':'.$finding['record_digest'], $findings);
        if ($findingReferences !== ($senate['decision']['finding_references'] ?? null)
            || $findingReferences !== ($senate['reconciliation']['finding_references'] ?? null)) {
            throw new \RuntimeException('I224_MODEL_BOUND_PROFILE_APPROVAL_CHAIN_INVALID');
        }
        return [$reconciliation, $findings];
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) {
            throw new \RuntimeException('I229_MODEL_BOUND_PROFILE_APPROVAL_DECISION_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->decisions.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'I228_MODEL_BOUND_PROFILE_APPROVAL_DECISION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('I228_MODEL_BOUND_PROFILE_APPROVAL_DECISION_CONFLICT');
            }
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('I229_MODEL_BOUND_PROFILE_APPROVAL_DECISION_FAILED');
        }
        return $record;
    }
}
