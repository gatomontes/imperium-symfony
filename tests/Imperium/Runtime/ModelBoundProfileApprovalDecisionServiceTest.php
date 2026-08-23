<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ModelBoundProfileApprovalDecisionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModelBoundProfileApprovalDecisionServiceTest extends TestCase
{
    public function testExactApprovalOpensOnlyBoundedQualificationRequest(): void
    {
        $root = sys_get_temp_dir().'/imperium-model-profile-approval-'.bin2hex(random_bytes(6));
        try {
            $source = $this->source($root, 'APPROVED', false);
            $service = new ModelBoundProfileApprovalDecisionService($root);
            $decision = $service->decide($source['disposition_id'], 'APPROVED', 'Approve the exact examined Profile.', 'Qualification request only.');

            self::assertSame('IMPERATOR_PROFILE_APPROVED_PENDING_CONSCRIPTION_OPERATIONAL_QUALIFICATION', $decision['status']);
            self::assertTrue($decision['profile_approved']);
            self::assertTrue($decision['operational_qualification_request_authority']);
            self::assertSame('conscription.recruiter', $decision['operational_qualification_request']['destination']);
            self::assertTrue($decision['operational_qualification_request']['authority_single_use']);
            self::assertFalse($decision['operational_qualification_request']['consumed']);
            self::assertSame($source['subject_profile'], $decision['subject_profile']);
            self::assertSame($source['admitted_findings'], $decision['admitted_findings']);
            self::assertSame($source['reconciliation'], $decision['reconciliation']);
            self::assertSame($source['record_digest'], $decision['source_senate_disposition']['digest']);
            foreach (['operational_qualification_authority', 'profile_installation_authority', 'profile_activation_authority', 'manifestation_assembly_authority', 'seat_binding_authority', 'custody_transfer_authority', 'tool_use_authority', 'credential_use_authority', 'provider_invocation_authority', 'external_action_authority', 'deployment_authority', 'execution_authority'] as $field) {
                self::assertFalse($decision[$field]);
            }
            self::assertSame($decision, $service->decide($source['disposition_id'], 'APPROVED', 'Approve the exact examined Profile.', 'Qualification request only.'));
            $this->expectExceptionMessage('I228_MODEL_BOUND_PROFILE_APPROVAL_DECISION_CONFLICT');
            $service->decide($source['disposition_id'], 'DEFERRED', 'Defer.', 'No authority.');
        } finally {
            $this->remove($root);
        }
    }

    #[DataProvider('nonApprovingDispositions')]
    public function testEveryNonApprovingBranchIsSealedAndNonAuthorizing(string $disposition): void
    {
        $root = sys_get_temp_dir().'/imperium-model-profile-nonapproval-'.bin2hex(random_bytes(6));
        try {
            $source = $this->source($root, 'APPROVED', false);
            $decision = (new ModelBoundProfileApprovalDecisionService($root))->decide($source['disposition_id'], $disposition, 'Record non-approval.', 'No downstream authority.');
            self::assertSame('NON_APPROVING_IMPERATOR_PROFILE_DISPOSITION_RECORDED', $decision['status']);
            self::assertSame($disposition, $decision['disposition']);
            self::assertTrue($decision['imperator_profile_approval_consumed']);
            self::assertFalse($decision['profile_approved']);
            self::assertFalse($decision['operational_qualification_request_authority']);
            self::assertNull($decision['operational_qualification_request']);
            self::assertTrue($decision['sealed']);
        } finally {
            $this->remove($root);
        }
    }

    public static function nonApprovingDispositions(): array
    {
        return [['REFUSED'], ['RETURNED_FOR_REVISION'], ['ALTERNATIVE_PROPOSED'], ['CLARIFICATION_REQUIRED'], ['DEFERRED']];
    }

    public function testImperatorCannotApproveANonApprovedSenateDisposition(): void
    {
        $root = sys_get_temp_dir().'/imperium-model-profile-senate-refusal-'.bin2hex(random_bytes(6));
        try {
            $source = $this->source($root, 'RETURN_FOR_REVISION', false);
            $this->expectExceptionMessage('I225_MODEL_BOUND_SENATE_DISPOSITION_NOT_APPROVED');
            (new ModelBoundProfileApprovalDecisionService($root))->decide($source['disposition_id'], 'APPROVED', 'Attempt approval.', 'Qualification only.');
        } finally {
            $this->remove($root);
        }
    }

    public function testMandatorySecurityBlockMechanicallyPreventsImperatorApproval(): void
    {
        $root = sys_get_temp_dir().'/imperium-model-profile-security-block-'.bin2hex(random_bytes(6));
        try {
            $source = $this->source($root, 'APPROVED', true);
            $this->expectExceptionMessage('I225_MODEL_BOUND_SENATE_DISPOSITION_NOT_APPROVED');
            (new ModelBoundProfileApprovalDecisionService($root))->decide($source['disposition_id'], 'APPROVED', 'Attempt approval.', 'Qualification only.');
        } finally {
            $this->remove($root);
        }
    }

    public function testChangedOnDiskFindingInvalidatesCompleteSenateRecord(): void
    {
        $root = sys_get_temp_dir().'/imperium-model-profile-tamper-'.bin2hex(random_bytes(6));
        try {
            $source = $this->source($root, 'APPROVED', false);
            $reference = $source['admitted_findings'][0];
            $path = $root.'/var/imperium/offices/senate/model-bound-profile-senator-findings/'.$reference['finding_id'].'.json';
            $finding = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $finding['decision']['severity'] = 'HIGH';
            $finding['record_digest'] = $this->digest($finding);
            file_put_contents($path, json_encode($finding, JSON_THROW_ON_ERROR));
            $this->expectExceptionMessage('I224_MODEL_BOUND_PROFILE_APPROVAL_CHAIN_INVALID');
            (new ModelBoundProfileApprovalDecisionService($root))->decide($source['disposition_id'], 'APPROVED', 'Attempt approval.', 'Qualification only.');
        } finally {
            $this->remove($root);
        }
    }

    private function source(string $root, string $senateDisposition, bool $securityBlock): array
    {
        $references = [];
        foreach (['trust', 'security', 'usability'] as $jurisdiction) {
            $findingId = 'model-bound-profile-senator-finding-'.substr(hash('sha256', $jurisdiction), 0, 20);
            $blocked = $securityBlock && 'security' === $jurisdiction;
            $finding = $this->record([
                'schema' => 'imperium.senate-model-bound-profile-senator-finding/v1',
                'finding_id' => $findingId,
                'jurisdiction' => $jurisdiction,
                'decision' => ['disposition' => $blocked ? 'FAIL' : 'PASS', 'severity' => $blocked ? 'CRITICAL' : 'NONE'],
                'mandatory_security_blocking_condition' => $blocked,
                'sealed' => true,
            ]);
            $this->write($root.'/var/imperium/offices/senate/model-bound-profile-senator-findings/'.$findingId.'.json', $finding);
            $references[] = ['jurisdiction' => $jurisdiction, 'finding_id' => $findingId, 'finding_digest' => $finding['record_digest'], 'decision' => $finding['decision'], 'mandatory_security_blocking_condition' => $blocked];
        }
        $subject = ['profile_id' => 'profile-foundry-artificer', 'profile_version' => '1.1.0', 'content_digest' => 'sha256:'.str_repeat('a', 64), 'target' => ['kind' => 'seat', 'id' => 'foundry.artificer']];
        $reconciliationId = 'model-bound-profile-reconciliation-'.str_repeat('b', 20);
        $findingReferences = array_map(static fn (array $reference): string => 'finding:'.$reference['jurisdiction'].':'.$reference['finding_digest'], $references);
        $reconciliationBody = ['finding_references' => $findingReferences, 'rationale' => 'All findings preserved unchanged.'];
        $reconciliation = $this->record(['schema' => 'imperium.senate-model-bound-profile-reconciliation/v1', 'reconciliation_id' => $reconciliationId, 'instance_id' => 'imperium-test', 'case_id' => 'profile-case', 'case_digest' => str_repeat('c', 64), 'subject_profile' => $subject, 'admitted_findings' => $references, 'mandatory_security_blocking_condition' => $securityBlock, 'reconciliation' => $reconciliationBody, 'reconciliation_authority' => ['id' => 'reconciliation-authority', 'consumed' => true, 'continuing_authority' => false], 'status' => 'PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING', 'sealed' => true]);
        $this->write($root.'/var/imperium/offices/senate/model-bound-profile-reconciliations/'.$reconciliationId.'.json', $reconciliation);
        $sourceId = 'model-bound-profile-disposition-'.str_repeat('d', 20);
        $source = $this->record([
            'schema' => 'imperium.senate-model-bound-profile-disposition/v1',
            'disposition_id' => $sourceId,
            'instance_id' => 'imperium-test',
            'case_id' => 'profile-case',
            'case_digest' => str_repeat('c', 64),
            'source_reconciliation' => ['id' => $reconciliationId, 'digest' => $reconciliation['record_digest']],
            'subject_profile' => $subject,
            'admitted_findings' => $references,
            'reconciliation' => $reconciliationBody,
            'mandatory_security_blocking_condition' => $securityBlock,
            'lord_speaker' => ['seat' => 'senate.lord-speaker', 'binding_id' => 'binding-lord-speaker'],
            'disposition_authority' => ['id' => 'authority', 'consumed' => true, 'continuing_authority' => false],
            'decision' => ['disposition' => $senateDisposition, 'finding_references' => $findingReferences, 'rationale' => 'Senate disposition.'],
            'status' => 'PROFILE_EXAMINATION_DISPOSITION_SEALED_PENDING_IMPERATOR_PROFILE_APPROVAL',
            'senate_disposition_authority_consumed' => true,
            'imperator_profile_approval_pending' => true,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'profile_activation_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/offices/senate/model-bound-profile-dispositions/'.$sourceId.'.json', $source);
        return $source;
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function digest(array $record): string
    {
        unset($record['record_digest']);
        return hash('sha256', CanonicalJson::encode($record));
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
