<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

/** Bounded response JSON subset: objects, arrays and strings only; no numeric conversion.
 * Invalid bytes/shapes throw InvalidArgumentException with a fixed, non-payload message.
 */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class RawJsonDecoder
{
    public const MAX_BYTES = 1048576;
    public const MAX_DEPTH = 32;
    private int $offset = 0;

    private function __construct(private readonly string $bytes) {}

    public static function decode(string $bytes): mixed
    {
        if (strlen($bytes) > self::MAX_BYTES) { throw new \InvalidArgumentException('RESPONSE_BYTE_LIMIT'); }
        if (str_starts_with($bytes, "\xEF\xBB\xBF") || preg_match('//u', $bytes) !== 1) {
            throw new \InvalidArgumentException('INVALID_RESPONSE_ENCODING');
        }
        $parser = new self($bytes);
        $value = $parser->value(0);
        $parser->whitespace();
        if ($parser->offset !== strlen($bytes)) { throw new \InvalidArgumentException('TRAILING_JSON_DATA'); }
        return $value;
    }

    private function value(int $depth): mixed
    {
        $this->whitespace();
        $token = $this->bytes[$this->offset] ?? '';
        if ($token === '"') { return $this->string(); }
        if ($token !== '{' && $token !== '[') { throw new \InvalidArgumentException('INVALID_JSON_VALUE'); }
        // Count containers only: the root object/array is depth 1; strings add no depth.
        if (++$depth > self::MAX_DEPTH) { throw new \InvalidArgumentException('RESPONSE_DEPTH_LIMIT'); }
        ++$this->offset;
        $object = $token === '{';
        $close = $object ? '}' : ']';
        $values = []; $seen = [];
        $this->whitespace();
        if (($this->bytes[$this->offset] ?? '') === $close) {
            ++$this->offset;
            return $object ? new JsonObject([]) : [];
        }
        while (true) {
            if ($object) {
                $this->whitespace();
                $key = $this->string();
                // Prefix prevents PHP's numeric-string key conversion affecting duplicate detection.
                if (isset($seen['key:'.$key])) { throw new \InvalidArgumentException('DUPLICATE_JSON_MEMBER'); }
                $seen['key:'.$key] = true;
                $this->whitespace();
                $this->consume(':');
                $values[$key] = $this->value($depth);
            } else {
                $values[] = $this->value($depth);
            }
            $this->whitespace();
            if (($this->bytes[$this->offset] ?? '') === $close) {
                ++$this->offset;
                return $object ? new JsonObject($values) : $values;
            }
            $this->consume(',');
        }
    }

    private function string(): string
    {
        $start = $this->offset;
        $this->consume('"');
        $length = strlen($this->bytes);
        while ($this->offset < $length) {
            $char = $this->bytes[$this->offset++];
            if ($char === '\\') { ++$this->offset; continue; }
            if ($char === '"') {
                try {
                    return json_decode(substr($this->bytes, $start, $this->offset - $start), false, 2, JSON_THROW_ON_ERROR);
                } catch (\JsonException $error) {
                    throw new \InvalidArgumentException('INVALID_JSON_STRING', 0, $error);
                }
            }
        }
        throw new \InvalidArgumentException('UNTERMINATED_JSON_STRING');
    }

    private function whitespace(): void
    {
        $this->offset += strspn($this->bytes, " \t\r\n", $this->offset);
    }

    private function consume(string $token): void
    {
        if (($this->bytes[$this->offset] ?? '') !== $token) { throw new \InvalidArgumentException('INVALID_JSON_SYNTAX'); }
        ++$this->offset;
    }
}
