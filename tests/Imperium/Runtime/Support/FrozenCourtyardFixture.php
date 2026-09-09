<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

/** Restores only fixed pre-CY1 public synthetic bytes to a new disposable root. */
final class FrozenCourtyardFixture
{
    public string $root;
    public array $data;
    public SyntheticFormationClock $clock;
    public function __construct(string $case)
    {
        $hash=match($case) {
            'child-publication'=>'3dcc0c29249eae2952985fa23ec8b80060131ecb790a50a4aa969b5f706bd71a',
            'custody'=>'b41a73d5b397879084025371a1b5d4a069aa92ab523bb77f67ab873e9537da82',
            default=>throw new \RuntimeException('Unknown frozen fixture'),
        };
        require_once __DIR__.'/CitadelFormationFixture.php';
        $path=dirname(__DIR__,3).'/fixtures/courtyard-baseline/'.$case.'.json.gz';
        if (hash_file('sha256',$path) !== $hash) { throw new \RuntimeException('Frozen fixture changed'); }
        $this->data=json_decode(gzdecode(file_get_contents($path)),true,512,JSON_THROW_ON_ERROR);
        $this->root=sys_get_temp_dir().'/imperium-citadel-proof-'.bin2hex(random_bytes(12)); mkdir($this->root,0700);
        foreach ($this->data['files'] as $relative=>$file) {
            if (!str_starts_with($relative,'var/imperium/') || str_contains($relative,'..') || str_contains($relative,'\\')) { throw new \RuntimeException('Frozen path invalid'); }
            $bytes=base64_decode($file['bytes_base64'],true);
            if (hash('sha256',$bytes) !== $file['sha256']) { throw new \RuntimeException('Frozen bytes mismatch'); }
            $target=$this->root.'/'.$relative;
            if (!is_dir(dirname($target))) { mkdir(dirname($target),0700,true); }
            file_put_contents($target,$bytes);
        }
        $this->clock=new SyntheticFormationClock(); $this->clock->at=$this->data['clock'];
    }
    public function assertOriginalBytes(): void
    {
        foreach ($this->data['files'] as $path=>$file) {
            if (hash_file('sha256',$this->root.'/'.$path) !== $file['sha256']) { throw new \RuntimeException('Original public evidence modified: '.$path); }
        }
    }
    public function close(): void
    {
        FormationCustodyFixture::assertRoot($this->root);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            $f->isDir() && !$f->isLink() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($this->root);
    }
}
