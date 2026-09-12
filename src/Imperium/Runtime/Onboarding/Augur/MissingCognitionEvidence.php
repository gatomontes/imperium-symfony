<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\ResponseValidation\ParsedResponse;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class MissingCognitionEvidence implements CognitionEvidence
{
    public function resources(array $commission,array $originals,string $wire,string $model,int $now):CognitionResources{throw new \RuntimeException('O3_AUTHENTIC_COGNITION_EVIDENCE_MISSING');}
    public function response(ParsedResponse $response,array $commission,array $originals):void{throw new \RuntimeException('O3_AUTHENTIC_COGNITION_EVIDENCE_MISSING');}
}
