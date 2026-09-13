<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;
use App\Bootstrap\CanonicalJson;
/** Exact encoding reuse only, confined to one native-decoded journal traversal. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class JournalCanonicalHash
{
    // These measured large fields select encoding candidates, never trusted facts.
    private const FIELDS=['evidence','acts','policies','augur_migration','assignment_migration'];
    private array $entries=[];
    private int $nodes=0;
    private int $rawBytes=0;
    private int $encodedBytes=0;
    private int $fragmentBytes=0;
    public function read(string $raw):array
    {
        try{
            $frame=$this->decode($raw);
            $digest=$frame['record_digest']??null;unset($frame['record_digest']);
            return [$frame,$digest,is_string($digest)?$this->digest($frame):null];
        }catch(\Throwable $error){$this->entries=[];$this->nodes=0;$this->rawBytes=0;$this->encodedBytes=0;$this->fragmentBytes=0;throw $error;}
    }
    /** Only exact native-encoded, shallow, complete array fragments may reuse parsing. */
    private function decode(string $raw):mixed
    {
        if($this->entries===[] || strlen($raw)>16777216 || str_contains($raw,'"\u0000"')){
            return json_decode($raw,true,512,JSON_THROW_ON_ERROR);
        }
        $matches=[];
        foreach($this->entries as $entry){
            $offset=0;
            while(($at=strpos($raw,$entry['fragment'],$offset))!==false){
                $matches[]=[$at,$entry];$offset=$at+strlen($entry['fragment']);
                if(count($matches)>64){return json_decode($raw,true,512,JSON_THROW_ON_ERROR);}
            }
        }
        if($matches===[]){return json_decode($raw,true,512,JSON_THROW_ON_ERROR);}
        usort($matches,static fn($a,$b)=>$a[0]<=>$b[0] ?: $b[1]['fragmentSize']<=>$a[1]['fragmentSize']);
        $cursor=0;$depth=0;$tokens=0;$copied=0;$chunks=[];$values=[];
        foreach($matches as [$at,$entry]){
            if($at<$cursor){continue;}
            $start=$at+strlen($entry['prefix']);
            do{
                $found=preg_match('/"(?:[^"\\\\]++|\\\\.)*+"(*SKIP)(*F)|[{}\[\]]/s',$raw,$token,PREG_OFFSET_CAPTURE,$cursor);
                if($found!==1 || ++$tokens>65536){return json_decode($raw,true,512,JSON_THROW_ON_ERROR);}
                [$char,$position]=$token[0];
                if($position>=$start){break;}
                $depth+=($char==='{' || $char==='[')?1:-1;
                if($depth<0 || $depth>128){return json_decode($raw,true,512,JSON_THROW_ON_ERROR);}
                $cursor=$position+1;
            }while(true);
            if($position!==$start || ($char!=='{' && $char!=='[')){
                return json_decode($raw,true,512,JSON_THROW_ON_ERROR);
            }
            // The cached value has depth <=64; this cannot hide native depth-512 errors,
            // including those in a duplicate member later overwritten by native decoding.
            $chunks[]=substr($raw,$copied,$start-$copied);
            $chunks[]='{"\u0000":'.$entry['id'].'}';$values[$entry['id']]=$entry['value'];
            $cursor=$copied=$at+$entry['fragmentSize'];
        }
        if($chunks===[]){return json_decode($raw,true,512,JSON_THROW_ON_ERROR);}
        $chunks[]=substr($raw,$copied);
        try{
            $short=json_decode(implode('',$chunks),true,512,JSON_THROW_ON_ERROR);
            return self::restore($short,$values,0);
        }catch(\Throwable){return json_decode($raw,true,512,JSON_THROW_ON_ERROR);}
    }
    private static function restore(mixed $value,array $values,int $depth):mixed
    {
        if(!is_array($value)){return $value;}
        if($depth>128){throw new \RuntimeException('Use native original-depth parsing');}
        if(count($value)===1 && array_key_exists("\0",$value)){
            $id=$value["\0"];
            if(!is_int($id) || !array_key_exists($id,$values)){throw new \RuntimeException('Use native original marker parsing');}
            return $values[$id];
        }
        foreach($value as $key=>$item){if(is_array($item)){$value[$key]=self::restore($item,$values,$depth+1);}}
        return $value;
    }
    private function digest(mixed $frame):string
    {
        if(!is_array($frame['state']['onboarding']??null)){return hash('sha256',CanonicalJson::encode($frame));}
        try{
            $short=$frame;$replacements=[];
            foreach(self::FIELDS as $id=>$field){
                $value=$frame['state']['onboarding'][$field]??null;
                if(!is_array($value)){continue;}
                $entry=$this->entries[$field]??null;
                if($entry===null || $entry['value']!==$value){
                    $nodes=0;$rawBytes=0;
                    if(!self::bounded($value,0,$nodes,$rawBytes)){continue;}
                    $encoded=CanonicalJson::encode($value);$size=strlen($encoded);
                    $prefix='"'.$field.'":';
                    $body=json_encode($value,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
                    $fragment=$prefix.$body;$fragmentSize=strlen($fragment);
                    if(json_decode($body,true,512,JSON_THROW_ON_ERROR)!==$value){continue;}
                    if($this->fragmentBytes-($entry['fragmentSize']??0)+$fragmentSize>8388608 || $size<4096 || $this->encodedBytes-($entry['size']??0)+$size>8388608
                        || $this->nodes-($entry['nodes']??0)+$nodes>65536
                        || $this->rawBytes-($entry['rawBytes']??0)+$rawBytes>8388608){continue;}
                    $this->nodes+=$nodes-($entry['nodes']??0);$this->rawBytes+=$rawBytes-($entry['rawBytes']??0);$this->encodedBytes+=$size-($entry['size']??0);
                    $this->fragmentBytes+=$fragmentSize-($entry['fragmentSize']??0);
                    $entry=['fragment'=>$fragment,'fragmentSize'=>$fragmentSize,'prefix'=>$prefix,'id'=>$id,'value'=>$value,'encoded'=>$encoded,'size'=>$size,'nodes'=>$nodes,'rawBytes'=>$rawBytes];$this->entries[$field]=$entry;
                }
                $short['state']['onboarding'][$field]=["\0"=>$id];
                $replacements['{"\\u0000":'.$id.'}']=$entry['encoded'];
            }
            if($replacements===[]){return hash('sha256',CanonicalJson::encode($frame));}
            $bytes=CanonicalJson::encode($short);
            foreach($replacements as $token=>$encoded){if(substr_count($bytes,$token)!==1){return hash('sha256',CanonicalJson::encode($frame));}}
            return hash('sha256',strtr($bytes,$replacements));
        }catch(\JsonException){return hash('sha256',CanonicalJson::encode($frame));}
    }
    /** Floats remain on the original path: PHP equality cannot distinguish signed zero. */
    private static function bounded(array $value,int $depth,int &$nodes,int &$bytes):bool
    {
        if(++$nodes>65536 || $depth>64){return false;}
        foreach($value as $key=>$item){
            if(is_string($key)){$bytes+=strlen($key);}
            if(is_array($item)){if(!self::bounded($item,$depth+1,$nodes,$bytes)){return false;}}
            else{if(is_float($item) || ++$nodes>65536){return false;}if(is_string($item)){$bytes+=strlen($item);}}
            if($bytes>8388608){return false;}
        }
        return true;
    }
}
