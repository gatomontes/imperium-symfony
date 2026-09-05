<?php
declare(strict_types=1);
namespace App\ProtectedMission;

use App\Bootstrap\CanonicalJson;

/** Exact execution identity; no mission-ID-only evidence can be promoted into it. */
final class Generation
{
    public const SCHEMA='imperium.protected-state/v2';

    public static function binding(array $chain):array
    {
        $p=$chain['payload'];$a=$chain['authorization'];$d=$chain['dossier'];
        $binding=['authorization_id'=>$a['authorization_id'],'authorization_digest'=>$a['record_digest'],
            'mission_id'=>$p['mission_id'],'dossier_id'=>$d['dossier_id'],'dossier_version'=>$d['dossier_version'],
            'dossier_digest'=>$d['record_digest'],'target'=>$p['target'],'paths'=>$p['paths'],'budget'=>$p['budget']];
        return json_decode(CanonicalJson::encode(['generation_id'=>hash('sha256',CanonicalJson::encode($binding)),...$binding]),true,512,JSON_THROW_ON_ERROR);
    }

    public static function requireBinding(array $record,array $binding):void
    {
        if (CanonicalJson::encode($record['binding'] ?? null)!==CanonicalJson::encode($binding)) throw new \RuntimeException('PMA_GENERATION_BINDING_INVALID');
    }
}
