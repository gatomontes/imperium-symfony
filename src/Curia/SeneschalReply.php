<?php

namespace App\Curia;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SeneschalReply
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 12000)]
        public string $message,
        public bool $readyToDraft,
    ) {
    }
}
