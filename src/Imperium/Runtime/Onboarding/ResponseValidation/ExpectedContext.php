<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

use App\Imperium\Runtime\Onboarding\Selection\{RecordRef, Shape};

/** Immutable caller-supplied expectations. Construction supplies no provenance or authority. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class ExpectedContext
{
    private function __construct(
        public string $groupId, public string $inputDigest, public RecordRef $holderRef,
        public RecordRef $profileRef, public array $candidateRefs, public array $evidenceRefs,
    ) {}

    /** References use closed PHP arrays; candidate/evidence collections must be lists. */
    public static function fromInputs(string $groupId, string $inputDigest, array $holderRef, array $profileRef, array $candidateRefs, array $evidenceRefs): self
    {
        if (!in_array($groupId, ['W1', 'W2', 'W3'], true)) { throw new \InvalidArgumentException('INVALID_GROUP'); }
        $objects = static function (array $refs): array {
            return array_map(static function (mixed $ref): JsonObject {
                if (!is_array($ref)) { throw new \InvalidArgumentException('INVALID_REFERENCE'); }
                return new JsonObject($ref);
            }, Shape::list($refs));
        };
        return new self($groupId, Shape::digest($inputDigest), ResponseShape::ref(new JsonObject($holderRef)),
            ResponseShape::ref(new JsonObject($profileRef)), ResponseShape::refs($objects($candidateRefs)),
            ResponseShape::refs($objects($evidenceRefs)));
    }
}
