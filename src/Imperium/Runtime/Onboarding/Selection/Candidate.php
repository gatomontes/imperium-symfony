<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** Internal projection of an exact binding. Fitness is supplied, not established here. */
final readonly class Candidate
{
    private function __construct(public RecordRef $ref, public array $identity) {}

    public static function decode(mixed $value): self
    {
        $v = Shape::object($value, ['ref', 'identity']);
        $raw = Shape::object($v['identity'], SelectionRule::IDENTITY_FIELDS);
        $identity = [];
        foreach (SelectionRule::IDENTITY_FIELDS as $field) {
            $identity[$field] = str_ends_with($field, '_digest') ? Shape::digest($raw[$field]) : Shape::text($raw[$field]);
        }
        $ref = RecordRef::decode($v['ref']);
        if ($ref->digest !== $identity['binding_digest']) { throw new \InvalidArgumentException('BINDING_DIGEST_MISMATCH'); }
        return new self($ref, $identity);
    }

    public function compare(self $other): int
    {
        foreach (SelectionRule::IDENTITY_FIELDS as $field) {
            $cmp = strcmp($this->identity[$field], $other->identity[$field]);
            if ($cmp !== 0) { return $cmp; }
        }
        return 0;
    }
}
