<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Runtime-owned verification custody. Callers cannot provide or replace this key. */
final readonly class MissionCapabilityKeyStore
{
    private string $path;

    public function __construct(string $root)
    {
        $this->path = $root.'/var/imperium/runtime/canonical-mission/capability-issuer.key';
    }

    public function existing(): string
    {
        if (!is_file($this->path)) { throw new \RuntimeException('MIS411_TRUSTED_CAPABILITY_ISSUER_UNAVAILABLE'); }
        $key = file_get_contents($this->path);
        if (false === $key || 32 !== strlen($key)) { throw new \RuntimeException('MIS411_TRUSTED_CAPABILITY_ISSUER_UNAVAILABLE'); }
        return $key;
    }

    public function initialize(): string
    {
        if (is_file($this->path)) { return $this->existing(); }
        if (!is_dir(dirname($this->path)) && !mkdir(dirname($this->path), 0770, true) && !is_dir(dirname($this->path))) {
            throw new \RuntimeException('MIS411_TRUSTED_CAPABILITY_ISSUER_UNAVAILABLE');
        }
        $key = random_bytes(32);
        $temporary = $this->path.'.tmp.'.bin2hex(random_bytes(6));
        if (32 !== file_put_contents($temporary, $key, LOCK_EX) || !rename($temporary, $this->path)) {
            @unlink($temporary);
            throw new \RuntimeException('MIS411_TRUSTED_CAPABILITY_ISSUER_UNAVAILABLE');
        }
        @chmod($this->path, 0600);
        return $key;
    }
}
