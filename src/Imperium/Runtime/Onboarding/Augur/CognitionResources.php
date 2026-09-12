<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\DeepSeek\{TokenEvidence,Tariff};
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class CognitionResources
{
    public function __construct(public TokenEvidence $tokens,public Tariff $tariff){}
}
