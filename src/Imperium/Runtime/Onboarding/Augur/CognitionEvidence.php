<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\ResponseValidation\ParsedResponse;
use App\Imperium\Runtime\Onboarding\DeepSeek\{TokenEvidence,Tariff};
interface CognitionEvidence
{
    /** Authenticates originals and derives bounds for these exact bytes, model and account. */
    public function resources(array $commission,array $originals,string $wire,string $model,int $now):CognitionResources;
    /** Checks substantive claims against the exact original evidence, not merely JSON validity. */
    public function response(ParsedResponse $response,array $commission,array $originals):void;
}
