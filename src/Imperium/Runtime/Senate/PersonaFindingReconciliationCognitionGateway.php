<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface PersonaFindingReconciliationCognitionGateway
{
    public function reconcile(array $authority, array $findings): array;
}
