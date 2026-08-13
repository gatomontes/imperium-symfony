<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\SubordinateClarificationReturnService;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationRevisionCognitionGateway;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationRevisionService;
use PHPUnit\Framework\TestCase;

final class SubordinateSpecificationRevisionFlowTest extends TestCase
{
    public function testPreservesClarificationAndCreatesCompleteSupersessionLineage(): void
    {
        $root = sys_get_temp_dir().'/imperium-spec-revision-'.bin2hex(random_bytes(6));
        $caseId = 'subordinate-construction-case-'.str_repeat('a', 20);
        $case = ['schema'=>'imperium.foundry-subordinate-construction-case/v1','case_id'=>$caseId,'instance_id'=>'imperium-test','queue_position'=>1,'office'=>'hagiography','subordinate_staff_class'=>'Chronicler','source_resolution_id'=>'resolution','source_resolution_digest'=>'resolution-digest','artificer'=>['seat'=>'foundry.artificer'],'subordinate_requirements'=>['research an unfinished Persona'],'status'=>'OPEN_PENDING_PERSONA_SPECIFICATION','construction_authority'=>true];
        $this->write($root.'/var/imperium/offices/foundry/subordinate-construction-cases', $caseId, $case);
        $specId = 'subordinate-persona-specification-'.str_repeat('b', 20);
        $spec = ['schema'=>'imperium.foundry-subordinate-persona-specification/v1','specification_id'=>$specId,'specification_version'=>1,'instance_id'=>'imperium-test','case_id'=>$caseId,'case_digest'=>$case['record_digest'],'queue_position'=>1,'office'=>'hagiography','subordinate_staff_class'=>'Chronicler','source_resolution_id'=>'resolution','source_resolution_digest'=>'resolution-digest','artificer'=>['seat'=>'foundry.artificer'],'inherited_requirements'=>$case['subordinate_requirements'],'specification'=>['persona_name'=>'Chronicler','source_requirements'=>['Obtain Garrison personnel facts for the Persona under construction']],'status'=>'SEALED_PENDING_PERSONA_CONSTRUCTION','persona_specification_complete'=>true,'construction_authority'=>true,'sealed'=>true];
        $this->write($root.'/var/imperium/offices/foundry/subordinate-persona-specifications', $specId, $spec);
        $productId = 'hagiography-subordinate-product-'.str_repeat('c', 20);
        $clarification = 'A Persona still under construction has no Garrison personnel record; return this requirement to Foundry for correction.';
        $product = ['schema'=>'imperium.subordinate-persona-section-packet/v1','product_id'=>$productId,'instance_id'=>'imperium-test','office'=>'hagiography','acceptance_id'=>'acceptance','acceptance_digest'=>'acceptance-digest','commission_id'=>'commission','commission_digest'=>'commission-digest','persona_specification_id'=>$specId,'persona_specification_digest'=>$spec['record_digest'],'subordinate_construction_case_id'=>$caseId,'subordinate_construction_case_digest'=>$case['record_digest'],'source_resolution_id'=>'resolution','source_resolution_digest'=>'resolution-digest','authored_sections'=>['boundary'=>'Garrison holds admitted Personas only.'],'source_citations'=>[],'unresolved_questions'=>[$clarification],'status'=>'CLARIFICATION_REQUIRED','sealed'=>false,'authorship_complete'=>false,'persona_assembly_authority'=>false,'persona_approval_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'admission_authority'=>false,'execution_authority'=>false];
        $this->write($root.'/var/imperium/offices/hagiography/subordinate-products', $productId, $product);
        $gateway = new class implements SubordinatePersonaSpecificationRevisionCognitionGateway { public function revise(array $case,array $priorSpecification,array $clarificationReturn):array{return['disposition'=>'PERSONA_SPECIFICATION_COMPLETE','persona_name'=>'Chronicler','purpose'=>'Research evidence for a bounded Persona.','identity_constraints'=>['Chronicler only'],'competencies'=>['evidence research'],'behavioral_directives'=>['preserve provenance'],'evidence_obligations'=>['cite evidence'],'explicit_exclusions'=>['do not seek Garrison facts for an unfinished Persona'],'source_requirements'=>['authorized evidence sources only'],'return_contracts'=>['return to Foundry'],'stop_conditions'=>['insufficient evidence']];}};
        try {
            $returnService = new SubordinateClarificationReturnService($root);
            $returned = $returnService->returnToFoundry('hagiography', $productId);
            self::assertSame($returned, $returnService->returnToFoundry('hagiography', $productId));
            self::assertSame([$clarification], $returned['original_clarification']['unresolved_questions']);
            self::assertSame($product['record_digest'], $returned['clarification_product_digest']);
            $revisionService = new SubordinatePersonaSpecificationRevisionService($root, $gateway);
            $revised = $revisionService->revise($returned['return_id']);
            self::assertSame($revised, $revisionService->revise($returned['return_id']));
            self::assertSame(2, $revised['specification_version']);
            self::assertSame($specId, $revised['supersedes']['specification_id']);
            self::assertSame($spec['record_digest'], $revised['supersedes']['specification_digest']);
            self::assertSame([$clarification], $revised['revision_basis']['original_clarification']['unresolved_questions']);
            self::assertSame('SEALED_PENDING_PERSONA_CONSTRUCTION', $revised['status']);
            self::assertFalse($revised['admission_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    private function write(string $directory, string $id, array &$record): void
    {
        mkdir($directory, 0770, true);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($directory.'/'.$id.'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
