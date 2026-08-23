<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinateClarificationReturnService
{
    private string $officeRoot;
    private string $foundryInbox;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->officeRoot = $projectDir.'/var/imperium/offices';
        $this->foundryInbox = $this->officeRoot.'/foundry/subordinate-clarification-returns';
    }

    public function returnToFoundry(string $office, string $productId): array
    {
        if (!in_array($office, ['hagiography', 'studium'], true)) {
            throw new \InvalidArgumentException('A113_AUTHORSHIP_OFFICE_INVALID');
        }
        if (!preg_match('/^'.$office.'-subordinate-product-[a-f0-9]{20}$/', $productId)) {
            throw new \InvalidArgumentException('A114_SUBORDINATE_PRODUCT_ID_INVALID');
        }

        $product = $this->read(
            $this->officeRoot.'/'.$office.'/subordinate-products/'.$productId.'.json',
            'A115_SUBORDINATE_PRODUCT_ABSENT',
        );
        if (!$this->digestMatches($product)
            || 'imperium.subordinate-persona-section-packet/v1' !== ($product['schema'] ?? null)
            || $office !== ($product['office'] ?? null)
            || 'CLARIFICATION_REQUIRED' !== ($product['status'] ?? null)
            || true === ($product['sealed'] ?? null)
            || true === ($product['authorship_complete'] ?? null)
            || !is_array($product['unresolved_questions'] ?? null)
            || [] === $product['unresolved_questions']
            || !is_string($product['originating_guildhall_commission_id'] ?? null)
            || !preg_match('/^guildhall-subordinate-construction-commission-[a-f0-9]{20}$/', $product['originating_guildhall_commission_id'])
            || !is_string($product['originating_guildhall_commission_digest'] ?? null)
            || true === ($product['persona_assembly_authority'] ?? null)
            || true === ($product['persona_approval_authority'] ?? null)
            || true === ($product['profile_approval_authority'] ?? null)
            || true === ($product['spawning_authority'] ?? null)
            || true === ($product['admission_authority'] ?? null)
            || true === ($product['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('A116_SUBORDINATE_CLARIFICATION_INVALID');
        }

        foreach (glob($this->foundryInbox.'/subordinate-clarification-return-*.json') ?: [] as $path) {
            $old = $this->read($path, 'A118_CLARIFICATION_RETURN_REPLAY_CONFLICT');
            if ($productId === ($old['clarification_product_id'] ?? null) && $this->digestMatches($old)) {
                return $old;
            }
        }

        $id = 'subordinate-clarification-return-'.substr(hash('sha256', CanonicalJson::encode([
            $productId,
            $product['record_digest'],
            'foundry',
        ])), 0, 20);

        return $this->persist($id, [
            'schema' => 'imperium.subordinate-specification-clarification-return/v1',
            'return_id' => $id,
            'instance_id' => $product['instance_id'],
            'from_office' => $office,
            'to_office' => 'foundry',
            'clarification_product_id' => $productId,
            'clarification_product_digest' => $product['record_digest'],
            'acceptance_id' => $product['acceptance_id'],
            'acceptance_digest' => $product['acceptance_digest'],
            'commission_id' => $product['commission_id'],
            'commission_digest' => $product['commission_digest'],
            'persona_specification_id' => $product['persona_specification_id'],
            'persona_specification_digest' => $product['persona_specification_digest'],
            'subordinate_construction_case_id' => $product['subordinate_construction_case_id'],
            'subordinate_construction_case_digest' => $product['subordinate_construction_case_digest'],
            'originating_guildhall_commission_id' => $product['originating_guildhall_commission_id'],
            'originating_guildhall_commission_digest' => $product['originating_guildhall_commission_digest'],
            'source_resolution_id' => $product['source_resolution_id'],
            'source_resolution_digest' => $product['source_resolution_digest'],
            'original_clarification' => [
                'authored_sections' => $product['authored_sections'],
                'source_citations' => $product['source_citations'],
                'unresolved_questions' => $product['unresolved_questions'],
            ],
            'disposition' => 'RETURNED_TO_FOUNDRY_FOR_SPECIFICATION_REVISION',
            'status' => 'PENDING_FOUNDRY_SPECIFICATION_REVISION',
            'specification_revision_authority' => true,
            'persona_assembly_authority' => false,
            'persona_approval_authority' => false,
            'profile_approval_authority' => false,
            'spawning_authority' => false,
            'admission_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->foundryInbox) && !mkdir($this->foundryInbox, 0770, true) && !is_dir($this->foundryInbox)) {
            throw new \RuntimeException('Foundry clarification-return inbox cannot be created.');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->foundryInbox.'/'.$id.'.json';
        if (is_file($path)) {
            $old = $this->read($path, 'A118_CLARIFICATION_RETURN_REPLAY_CONFLICT');
            if (CanonicalJson::encode($old) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('A118_CLARIFICATION_RETURN_REPLAY_CONFLICT');
            }

            return $old;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)
            || !rename($temporary, $path)
        ) {
            @unlink($temporary);
            throw new \RuntimeException('Clarification return cannot be committed atomically.');
        }

        return $record;
    }
}
