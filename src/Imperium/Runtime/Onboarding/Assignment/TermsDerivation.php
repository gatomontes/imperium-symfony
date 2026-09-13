<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class TermsDerivation
{
    public static function derive(array $s,array $policy,array $slot,callable $load):array
    {
        return match($slot['terms_rule']['kind']){
            'exact'=>['terms_ref'=>$slot['terms_rule']['object_ref'],'derivation_input_refs'=>[]],
            'eligible_binding'=>\App\Imperium\Runtime\Onboarding\Augur\FoundingRule::derive($s,$policy,$slot,$load),
            'assessed_assignment_set'=>AssignmentRule::derive($s,$policy,$slot,$load),
            default=>throw new \RuntimeException('O4_UNSUPPORTED_TERMS_RULE'),
        };
    }
}
