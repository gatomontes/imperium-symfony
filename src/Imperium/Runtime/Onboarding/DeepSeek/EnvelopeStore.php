<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,StrictJson};
use App\Imperium\Runtime\Citadel\Formation\SharedExposure;

/** Infrastructure-only immutable bytes; journal metadata is required separately by O2. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class EnvelopeStore
{
    private string $root;
    public function __construct(string $directory, private ?\Closure $publicationObserver = null)
    {
        R::require(preg_match('~\A(?:[A-Za-z]:[/\\\\]|/)~',$directory) === 1,'ENVELOPE_ABSOLUTE_ROOT');
        self::safe($directory); $root=realpath($directory);
        R::require(is_string($root) && is_dir($root), 'ENVELOPE_ROOT'); $this->root=$root;
    }
    private static function safe(string $path): void
    {
        R::require(preg_match('~(?:^|/)\.\.?(?:/|$)~',str_replace('\\','/',$path)) !== 1 && !str_contains($path,"\0"), 'ENVELOPE_PATH');
        for ($p=$path; ; $p=dirname($p)) {
            // PHP on Windows can report is_link=false for directory junctions; readlink still identifies them.
            clearstatcache(true,$p); $target=@readlink($p);
            $normalize=static fn(string $v): string=>strtolower(rtrim(str_replace('\\','/',$v),'/'));
            R::require(!is_link($p) && ($target === false || (PHP_OS_FAMILY === 'Windows' && $normalize($target) === $normalize($p))), 'ENVELOPE_SYMLINK');
            if (dirname($p) === $p || $p === '.') { break; }
        }
    }
    private function path(array $claim): string
    {
        R::ref($claim); R::require($claim['schema'] === 'imperium.bootstrap-cognition-claim/v1','ENVELOPE_CLAIM');
        self::safe($this->root); R::require(realpath($this->root) === $this->root,'ENVELOPE_ROOT_CHANGED');
        $path=$this->root.'/'.substr(R::hash($claim),7).'.json'; self::safe($path); return $path;
    }
    private static function validate(array $e): void
    {
        R::object($e,['claim_ref','operation_digest','response','metadata']); R::ref($e['claim_ref']); R::digest($e['operation_digest']);
        R::require(is_string($e['response']) && strlen($e['response']) <= 1048576 && preg_match('//u',$e['response']) === 1,'ENVELOPE_RESPONSE');
        $m=$e['metadata']; R::object($m,['provider_response_id','operation_digest','response_digest','usage','provenance']);
        R::text($m['provider_response_id']); SharedExposure::meters($m['usage']);
        R::require($m['operation_digest'] === $e['operation_digest'] && $m['response_digest'] === R::hash($e['response'])
            && $m['provenance'] === Wire::ADAPTER,'ENVELOPE_ATTRIBUTION');
    }
    public function retain(array $envelope): void
    {
        self::validate($envelope); $path=$this->path($envelope['claim_ref']);
        $lockPath=$this->root.'/retention.lock'; self::safe($lockPath);
        $lock=fopen($lockPath,'c+b'); R::require($lock !== false,'ENVELOPE_LOCK');
        try {
            R::require(flock($lock,LOCK_EX),'ENVELOPE_LOCK'); $this->path($envelope['claim_ref']);
            if (file_exists($path)) { R::require(R::same($this->read($envelope['claim_ref']),$envelope),'ENVELOPE_CONFLICT'); return; }
            $temporary=$path.'.pending.'.bin2hex(random_bytes(12)); $file=fopen($temporary,'x+b');
            R::require($file !== false,'ENVELOPE_CREATE');
            try {
                $bytes=CanonicalJson::encode($envelope); $offset=0;
                while ($offset < strlen($bytes)) { $n=fwrite($file,substr($bytes,$offset)); R::require(is_int($n) && $n > 0,'ENVELOPE_WRITE'); $offset+=$n; }
                R::require(fflush($file),'ENVELOPE_FLUSH');
            } finally { fclose($file); }
            try {
                if ($this->publicationObserver !== null) { ($this->publicationObserver)('before-publish'); }
                $this->path($envelope['claim_ref']); R::require(!file_exists($path) && rename($temporary,$path),'ENVELOPE_PUBLISH');
                if ($this->publicationObserver !== null) { ($this->publicationObserver)('after-publish'); }
            }
            finally { if (is_file($temporary)) { unlink($temporary); } }
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
    public function read(array $claim): array
    {
        $path=$this->path($claim); R::require(is_file($path),'ENVELOPE_MISSING');
        // JSON escaping can expand raw response bytes sixfold. Bound the physical read too.
        $file=fopen($path,'rb'); R::require($file !== false,'ENVELOPE_READ');
        try { $raw=stream_get_contents($file,6400001); } finally { fclose($file); }
        R::require(is_string($raw) && strlen($raw) <= 6400000,'ENVELOPE_READ_LIMIT');
        // Envelope is our canonical encoding; duplicate/foreign representations are refused by re-encoding.
        $e=json_decode($raw,true,32,JSON_THROW_ON_ERROR); R::require(is_array($e) && CanonicalJson::encode($e) === $raw,'ENVELOPE_ENCODING');
        self::validate($e); R::require(R::same($e['claim_ref'],$claim),'ENVELOPE_CLAIM'); return $e;
    }
}
