<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

interface ProfileExaminationReconciliationCognitionGateway
{
    public function reconcile(array $authority, array $findings): array;
}
