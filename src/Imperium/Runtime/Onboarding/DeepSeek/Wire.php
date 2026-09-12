<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,StrictJson};

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Wire
{
    public const ADAPTER = 'imperium.deepseek-o3-b0/v1';
    public const MODELS = ['deepseek-v4-flash','deepseek-v4-pro'];
    public const CONFIGURATION = ['max_tokens'=>4096,'stream'=>false,'temperature'=>0,
        'thinking'=>['type'=>'disabled'],'response_format'=>['type'=>'json_object'],
        'message_roles'=>['system','user']];

    public static function cognition(string $model, array $configuration, string $system, string $user): string
    {
        R::require(in_array($model, self::MODELS, true) && R::same($configuration,self::CONFIGURATION)
            && $configuration['temperature'] === 0 && $configuration['max_tokens'] === 4096, 'CONFIGURATION');
        foreach ([$system,$user] as $text) {
            R::require(trim($text) !== '' && preg_match('//u',$text) === 1, 'PUBLIC_PROMPT');
        }
        $wire = CanonicalJson::encode(['model'=>$model,'messages'=>[
            ['role'=>'system','content'=>$system],['role'=>'user','content'=>$user]],
            'max_tokens'=>4096,'stream'=>false,'thinking'=>['type'=>'disabled'],
            'temperature'=>0,'response_format'=>['type'=>'json_object']]);
        R::require(strlen($wire) <= 1048576, 'WIRE_LIMIT');
        return $wire;
    }

    public static function preflight(string $wire, string $model, ?TokenEvidence $tokens,
        ?Tariff $tariff, int $now, int $expiry): void
    {
        R::require($tokens !== null && $tariff !== null, 'TOKEN_TARIFF_EVIDENCE_MISSING');
        $tokens->check($wire); $tariff->current($now,$expiry,$model);
        R::require($tariff->cost(0,$tokens->inputMaximum,4096) <= 100000, 'COST_BOUND');
    }

    /** Fixed options only. The caller must restrict the concrete HTTP client, too. */
    public static function options(array $operation, #[\SensitiveParameter] string $key, int $remainingMs): array
    {
        R::object($operation,['schema','wire','destination','method','provider','model','configuration_ref','credential_operation','adapter','maximum','expires_at','authority_source']);
        R::require($operation['schema'] === 'imperium.bootstrap-prepared-operation/v1','OPERATION_SCHEMA');
        R::ref($operation['configuration_ref']);
        $access = $operation['method'] === 'GET';
        R::require($operation['adapter'] === self::ADAPTER && $operation['provider'] === 'deepseek'
            && $operation['destination'] === ($access ? 'https://api.deepseek.com:443/models' : 'https://api.deepseek.com:443/chat/completions')
            && ($access || $operation['method'] === 'POST') && $remainingMs > 0
            && $remainingMs <= ($access ? 10000 : 60000), 'TRANSPORT_SCOPE');
        R::require(preg_match('/\A[\x21-\x7e]+\z/',$key) === 1, 'KEY_FORMAT');
        R::require(!$access || $operation['wire'] === '', 'ACCESS_WIRE');
        if (!$access) {
            $body=StrictJson::decode($operation['wire']);
            R::object($body,['model','messages','max_tokens','stream','thinking','temperature','response_format']);
            R::require(is_array($body['messages']) && array_is_list($body['messages']) && count($body['messages']) === 2,'WIRE_MESSAGES');
            foreach ($body['messages'] as $message) { R::object($message,['role','content']); }
            R::require($body['messages'][0]['role'] === 'system' && $body['messages'][1]['role'] === 'user'
                && $body['model'] === $operation['model'] && $operation['wire'] === self::cognition($operation['model'],self::CONFIGURATION,
                    $body['messages'][0]['content'],$body['messages'][1]['content']), 'EXACT_COGNITION_WIRE');
        }
        return ['headers'=>['Authorization'=>'Bearer '.$key,'Accept'=>'application/json','Content-Type'=>'application/json'],
            'body'=>$operation['wire'],'max_redirects'=>0,'timeout'=>$remainingMs/1000,
            // Locked NativeHttpClient rejects proxy="" and null inherits environment proxies.
            // Its wildcard no_proxy branch unconditionally removes this inert proxy before connecting.
            'max_duration'=>$remainingMs/1000,'buffer'=>false,'proxy'=>'http://127.0.0.1:9','no_proxy'=>'*',
            'auth_basic'=>null,'auth_bearer'=>null,'query'=>[], 'verify_peer'=>true,'verify_host'=>true];
    }
}
