<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

/** Bounded admission JSON: closed objects/lists and strictly typed scalar values.
 * Invalid bytes/shapes throw InvalidArgumentException with a fixed, non-payload message.
 */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class StrictJson
{
    public const MAX_BYTES = 1048576;
    public const MAX_DEPTH = 32;
    private int $offset = 0;
    private static ?array $decoded=null;
    private static int $decodedBytes=0;
    private static array $recordIndex=[];
    private static int $encodedBytes=0;

    /** Bounded, synchronous call lifetime; only pure parsing and exact-value encoding are reused. */
    public static function within(callable $validation):mixed
    {
        if(self::$decoded!==null){return $validation();}
        self::$decoded=[];self::$decodedBytes=0;self::$recordIndex=[];self::$encodedBytes=0;
        try{return $validation();}finally{self::$decoded=null;self::$decodedBytes=0;self::$recordIndex=[];self::$encodedBytes=0;}
    }

    private function __construct(private readonly string $bytes) {}

    public static function decode(string $bytes): mixed
    {
        if(strlen($bytes)>self::MAX_BYTES){throw new \InvalidArgumentException('RESPONSE_BYTE_LIMIT');}
        if(self::$decoded===null){return self::parse($bytes);}
        $key=hash('sha256',$bytes);
        // Index by digest, but compare every original byte before reusing a parse.
        if(isset(self::$decoded[$key]) && self::$decoded[$key]['raw']===$bytes){return self::$decoded[$key]['value'];}
        $value=self::parse($bytes);$length=strlen($bytes);
        if(count(self::$decoded)<128 && self::$decodedBytes+$length<=2097152 && !isset(self::$decoded[$key])){
            self::$decoded[$key]=['raw'=>$bytes,'value'=>$value];self::$decodedBytes+=$length;
            if(is_array($value) && is_string($value['schema']??null) && is_string($value['id']??null) && is_string($value['record_digest']??null)){
                self::$recordIndex[$value['schema']."\0".$value['id']]??=$key;
            }
        }
        return $value;
    }

    /** Reuse only encoding of an exact, privately retained parsed record value.
     * Identity fields select a candidate; full strict value equality decides reuse.
     * No record validity, signature or currentness result is retained.
     */
    public static function canonical(mixed $value):string
    {
        if(self::$decoded!==null && is_array($value) && is_string($value['schema']??null) && is_string($value['id']??null)){
            $key=self::$recordIndex[$value['schema']."\0".$value['id']]??null;
            if($key!==null){
                $original=self::$decoded[$key]['value'];
                $form=array_key_exists('record_digest',$value)?'complete':'unsigned';
                if($form==='unsigned'){unset($original['record_digest']);}
                // Parsed values contain no objects/references. This private snapshot cannot
                // be changed by a caller mutating a returned value or reusing an ID/digest.
                if($value===$original){
                    if(isset(self::$decoded[$key][$form])){return self::$decoded[$key][$form];}
                    $encoded=\App\Bootstrap\CanonicalJson::encode($value);
                    if(self::$encodedBytes+strlen($encoded)<=2097152){self::$decoded[$key][$form]=$encoded;self::$encodedBytes+=strlen($encoded);}
                    return $encoded;
                }
            }
        }
        return \App\Bootstrap\CanonicalJson::encode($value);
    }

    private static function parse(string $bytes): mixed
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
        if ($token !== '{' && $token !== '[') {
            foreach (['true' => true, 'false' => false, 'null' => null] as $literal => $value) {
                if (substr($this->bytes, $this->offset, strlen($literal)) === $literal) { $this->offset += strlen($literal); return $value; }
            }
            if (preg_match('/\G(?:0|[1-9][0-9]*)/A', $this->bytes, $match, 0, $this->offset) === 1) {
                $digits = $match[0]; $max = (string) PHP_INT_MAX;
                if (strlen($digits) > strlen($max) || (strlen($digits) === strlen($max) && strcmp($digits, $max) > 0)) { throw new \InvalidArgumentException('INTEGER_RANGE'); }
                $this->offset += strlen($digits); return (int) $digits;
            }
            throw new \InvalidArgumentException('INVALID_JSON_VALUE');
        }
        // Count containers only: the root object/array is depth 1; strings add no depth.
        if (++$depth > self::MAX_DEPTH) { throw new \InvalidArgumentException('RESPONSE_DEPTH_LIMIT'); }
        ++$this->offset;
        $object = $token === '{';
        $close = $object ? '}' : ']';
        $values = []; $seen = [];
        $this->whitespace();
        if (($this->bytes[$this->offset] ?? '') === $close) {
            ++$this->offset;
            if ($object) { throw new \InvalidArgumentException('EMPTY_OBJECT'); } return [];
        }
        while (true) {
            if ($object) {
                $this->whitespace();
                $key = $this->string();
                if (is_numeric($key) || $key === '') { throw new \InvalidArgumentException('AMBIGUOUS_OBJECT_KEY'); }
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
                return $values;
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
