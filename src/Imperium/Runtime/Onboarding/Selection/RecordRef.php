<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\Selection;

/** A parsed D2 reference, not proof that a genuine record exists. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class RecordRef
{
    private function __construct(public string $schema, public string $id, public string $digest) {}

    public static function decode(mixed $value): self
    {
        $v = Shape::object($value, ['schema', 'id', 'digest']);
        return new self(Shape::text($v['schema']), Shape::text($v['id']), Shape::digest($v['digest']));
    }

    public function key(): string
    {
        return json_encode([$this->schema, $this->id, $this->digest], JSON_THROW_ON_ERROR);
    }
}
