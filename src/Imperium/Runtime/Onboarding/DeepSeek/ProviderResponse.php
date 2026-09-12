<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R, StrictJson};

/** Closed provider projection v1. Extensions refuse; original bytes never normalized. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ProviderResponse
{
    public static function listing(string $bytes, int $elapsed): array
    {
        $v = StrictJson::decode($bytes); R::object($v,['object','data']);
        R::require($v['object'] === 'list' && is_array($v['data']) && array_is_list($v['data'])
            && count($v['data']) <= 256, 'LIST_RESPONSE'); $seen=[];
        foreach ($v['data'] as $row) {
            R::object($row,['id','object','created','owned_by']);
            R::text($row['id']); R::text($row['owned_by']); R::integer($row['created']);
            R::require($row['object'] === 'model' && !isset($seen[$row['id']]), 'LIST_MODEL');
            $seen[$row['id']] = true;
        }
        R::require($elapsed >= 0 && $elapsed <= 10000, 'ELAPSED');
        return ['identity'=>'local:deepseek-model-list:sha256:'.hash('sha256',$bytes),
            'usage'=>['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>$elapsed]];
    }

    public static function cognition(string $bytes, string $model, Tariff $tariff, int $elapsed,
        ?string $expectedFingerprint = null): array
    {
        $v=StrictJson::decode($bytes);
        R::object($v,['id','object','created','model','system_fingerprint','choices','usage']);
        R::text($v['id']); R::integer($v['created']); R::text($v['system_fingerprint']);
        R::require(in_array($model,Wire::MODELS,true) && $v['model'] === $model && $tariff->model === $model
            && $v['object'] === 'chat.completion' && ($expectedFingerprint === null || $v['system_fingerprint'] === $expectedFingerprint), 'RESPONSE_MODEL');
        R::require(is_array($v['choices']) && array_is_list($v['choices']) && count($v['choices']) === 1, 'CHOICES');
        $choice=$v['choices'][0]; R::object($choice,['index','message','finish_reason']);
        R::require($choice['index'] === 0 && $choice['finish_reason'] === 'stop', 'FINISH_REASON');
        R::object($choice['message'],['role','content']);
        R::require($choice['message']['role'] === 'assistant', 'MESSAGE_ROLE'); R::text($choice['message']['content']);
        $u=$v['usage']; R::object($u,['prompt_tokens','prompt_cache_hit_tokens','prompt_cache_miss_tokens',
            'completion_tokens','total_tokens','completion_tokens_details']);
        foreach (['prompt_tokens','prompt_cache_hit_tokens','prompt_cache_miss_tokens','completion_tokens','total_tokens'] as $f) { R::integer($u[$f]); }
        R::object($u['completion_tokens_details'],['reasoning_tokens']); R::integer($u['completion_tokens_details']['reasoning_tokens']);
        R::require($u['prompt_tokens'] <= 16384 && $u['completion_tokens'] <= 4096
            && $u['prompt_cache_hit_tokens'] <= $u['prompt_tokens']
            && $u['prompt_cache_miss_tokens'] === $u['prompt_tokens'] - $u['prompt_cache_hit_tokens']
            && $u['total_tokens'] === $u['prompt_tokens'] + $u['completion_tokens']
            && $u['completion_tokens_details']['reasoning_tokens'] <= $u['completion_tokens'], 'USAGE_METERS');
        R::require($elapsed >= 0 && $elapsed <= 60000, 'ELAPSED');
        $cost=$tariff->cost($u['prompt_cache_hit_tokens'],$u['prompt_cache_miss_tokens'],$u['completion_tokens']);
        R::require($cost <= 100000,'COST_BOUND');
        return ['identity'=>'provider:deepseek:'.$v['id'],'model'=>$v['model'],'fingerprint'=>$v['system_fingerprint'],
            'content'=>$choice['message']['content'],'finish_reason'=>$choice['finish_reason'],
            'usage'=>['calls'=>1,'input_tokens'=>$u['prompt_tokens'],'output_tokens'=>$u['completion_tokens'],
                'cost_microusd'=>$cost,'milliseconds'=>$elapsed]];
    }
}
