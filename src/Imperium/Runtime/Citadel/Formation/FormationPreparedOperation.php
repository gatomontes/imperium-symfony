<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;
use App\Bootstrap\CanonicalJson;

/** Pure public serialization/binding contract. It grants no custody authority. */
final class FormationPreparedOperation
{
    public static function authorization(mixed $value): void
    {
        if (!is_array($value) || !FormationJournal::keys($value, ['schema','adapter','credential_reference','operation'])
            || $value['schema'] !== 'imperium.formation-transport-authorization/v1') { throw new \RuntimeException('FC002_EXACT_TRANSPORT_AUTHORIZATION_REQUIRED'); }
        foreach (['adapter','credential_reference','operation'] as $key) {
            if (!is_string($value[$key]) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:@-]{0,179}$/D', $value[$key])) {
                throw new \RuntimeException('FC002_EXACT_TRANSPORT_AUTHORIZATION_REQUIRED');
            }
        }
    }

    public static function build(array $request, array $terms, string $wire, array $maximum): array
    {
        self::authorization($terms['transport'] ?? null);
        if(array_key_exists('model_settings',$terms)){\App\Imperium\Runtime\Onboarding\Assignment\AssignmentRule::settingShape($terms['model_settings']);}
        return ['schema'=>'imperium.formation-prepared-operation/v1', 'authorization'=>$terms['transport'],
            'request_bytes_base64'=>base64_encode(CanonicalJson::encode($request)), 'request_digest'=>FormationJournal::digest($request),
            'wire_bytes_base64'=>base64_encode($wire), 'wire_sha256'=>hash('sha256',$wire),
            'provider'=>$terms['provider'], 'model'=>$terms['model'], 'destination'=>$terms['destination'],
            'authority_source_digest'=>FormationJournal::digest($terms['source']), 'pricing_digest'=>FormationJournal::digest($terms['pricing']),
            'maximum'=>$maximum, 'per_call'=>$terms['per_call'], 'total'=>$terms['total'], 'expires_at'=>$terms['expires_at'],
            ...(array_key_exists('model_settings',$terms)?['model_settings'=>$terms['model_settings']]:[])];
    }

    public static function validate(array $operation, array $request, array $terms, array $maximum): void
    {
        self::authorization($terms['transport'] ?? null);
        SessionExposure::validate($maximum); SessionExposure::validate($terms['per_call']); SessionExposure::validate($terms['total']);
        $wire = base64_decode($operation['wire_bytes_base64'] ?? '', true);
        if (!is_string($wire) || $wire === '' || strlen($wire) > 8388608 || $maximum['calls'] !== 1
            || FormationJournal::digest($operation) !== FormationJournal::digest(self::build($request,$terms,$wire,$maximum))) {
            throw new \RuntimeException('FC003_PREPARED_OPERATION_MISMATCH');
        }
        foreach ($maximum as $field=>$value) {
            if ($value > $terms['per_call'][$field] || $value > $terms['total'][$field]) { throw new \RuntimeException('FC003_PREPARED_OPERATION_MISMATCH'); }
        }
    }
}
