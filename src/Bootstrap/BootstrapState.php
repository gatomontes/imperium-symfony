<?php

declare(strict_types=1);

namespace App\Bootstrap;

enum BootstrapState: string
{
    case Uninitialized = 'UNINITIALIZED';
    case ManifestBound = 'MANIFEST_BOUND';
    case ConscriptionActive = 'CONSCRIPTION_ACTIVE';
    case ProvisionalRecruiterBound = 'PROVISIONAL_RECRUITER_BOUND';
    case OrdinaryRecruiterBound = 'ORDINARY_RECRUITER_BOUND';
}
