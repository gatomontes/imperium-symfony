<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\SectionAuthorshipGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SectionAuthorshipGovernanceCognitionBoundaryTest extends TestCase
{
    #[DataProvider('offices')]
    public function testExactSectionLineageCreatesOneOfficeSpecificAuthority(string $office, string $type, string $seat, string $class): void
    {
        $root = sys_get_temp_dir().'/imperium-section-governance-'.bin2hex(random_bytes(5));
        try {
            $acceptanceId = $office.'-subordinate-acceptance-'.str_repeat('a', 20);
            $commissionId = 'subordinate-authorship-'.$office.'-'.str_repeat('b', 20);
            $specificationId = 'subordinate-persona-specification-'.str_repeat('c', 20);
            $caseId = 'subordinate-construction-case-'.str_repeat('d', 20);
            $case = $this->seal(['case_id' => $caseId, 'instance_id' => 'imperium-test', 'status' => 'OPEN_PENDING_PERSONA_SPECIFICATION', 'construction_authority' => true]);
            $specification = $this->seal(['specification_id' => $specificationId, 'case_id' => $caseId, 'case_digest' => $case['record_digest'], 'status' => 'SEALED_PENDING_PERSONA_CONSTRUCTION', 'sealed' => true]);
            $commission = $this->seal(['commission_id' => $commissionId, 'office' => $office, 'authorship_class' => $class, 'authorship_authority' => true, 'execution_authority' => false]);
            $acceptance = $this->seal([
                'acceptance_id' => $acceptanceId, 'instance_id' => 'imperium-test', 'office' => $office,
                'commission_id' => $commissionId, 'commission_digest' => $commission['record_digest'],
                'persona_specification_id' => $specificationId, 'persona_specification_digest' => $specification['record_digest'],
                'subordinate_construction_case_id' => $caseId, 'subordinate_construction_case_digest' => $case['record_digest'],
                'actor' => ['seat' => $seat], 'authorship_class' => $class,
                'disposition' => 'ACCEPTED_FOR_EXACT_SUBORDINATE_AUTHORSHIP', 'recipient_acceptance' => true,
                'authorship_authority_exercisable' => true, 'execution_authority' => false,
            ]);
            $this->write($root, $office.'/inbox', $commissionId, $commission);
            $this->write($root, 'foundry/subordinate-persona-specifications', $specificationId, $specification);
            $this->write($root, 'foundry/subordinate-construction-cases', $caseId, $case);
            $this->write($root, $office.'/subordinate-acceptances', $acceptanceId, $acceptance);

            $resolver = new SectionAuthorshipGovernanceCognitionAuthorityResolver($root);
            $authority = $resolver->resolve('section-authorship', $type, $acceptanceId);
            self::assertSame($seat, $authority['seat']);
            self::assertSame(hash('sha256', CanonicalJson::encode([$acceptance, $commission, $specification, $case])), $authority['input_digest']);
            self::assertFalse($authority['consumed']);
            self::assertFalse($resolver->supports('section-authorship', 'other-office-section-authorship'));

            $product = $this->seal(['product_id' => $office.'-subordinate-product-'.str_repeat('e', 20), 'acceptance_id' => $acceptanceId]);
            $this->write($root, $office.'/subordinate-products', $product['product_id'], $product);
            self::assertTrue($resolver->resolve('section-authorship', $type, $acceptanceId)['consumed']);
        } finally { $this->remove($root); }
    }

    public function testSectionGatewayContainsNoDirectAgentOrCredentialPath(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Authorship/SymfonyAiSubordinatePersonaSectionAuthorshipGateway.php');
        foreach (['AgentInterface', 'Autowire(service:', 'DEEPSEEK_API_KEY', '$sanctographer', '$chancellor'] as $forbidden) { self::assertStringNotContainsString($forbidden, $source); }
        self::assertStringContainsString('GovernanceCognitionInvoker', $source);
    }

    public static function offices(): iterable
    {
        yield ['hagiography', 'hagiography-section-authorship', 'hagiography.sanctographer', 'EVIDENCE_DERIVED_PERSONA_SECTIONS'];
        yield ['studium', 'studium-section-authorship', 'studium.chancellor', 'PERSONA_GOVERNANCE_DOCTRINE_SECTIONS'];
    }

    private function seal(array $record): array { $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
    private function write(string $root, string $directory, string $id, array $record): void { $path = $root.'/var/imperium/offices/'.$directory; if (!is_dir($path)) { mkdir($path, 0770, true); } file_put_contents($path.'/'.$id.'.json', json_encode($record, JSON_THROW_ON_ERROR)); }
    private function remove(string $path): void { if (!is_dir($path)) { return; } foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->remove($child) : unlink($child); } rmdir($path); }
}
