<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Citadel\Formation\BoundedFormationTransport;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** Opt-in model-settings consumer. Existing FormationCognition remains the
 * personnel/session/lease authority owner; this decorator grants none of it. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
readonly class SettingsBoundTransport implements BoundedFormationTransport
{
    public function __construct(private PersistentSettings $settings,protected BoundedFormationTransport $transport,private string $role)
    {R::require(get_class($this)!==self::class || !$transport instanceof \App\Imperium\Runtime\Citadel\Formation\PreparedFormationTransport,'SETTINGS_PREPARED_WRAPPER_REQUIRED');}
    protected function check(array $terms):void
    {
        $tuple=$this->settings->resolve($this->role);
        R::require(($terms['provider']??null)===$tuple['provider'] && ($terms['model']??null)===$tuple['model_id'],'SETTINGS_TRANSPORT_IDENTITY');
        $binding=$terms['model_settings']??null;
        R::require(is_array($binding) && R::same($binding,$tuple),'SETTINGS_TRANSPORT_GENERATION');
    }
    public function inspect(array $request,array $terms):array{$this->check($terms);return $this->transport->inspect($request,$terms);}
    public function invoke(array $claim,array $request,array $terms):array{$this->check($terms);return $this->transport->invoke($claim,$request,$terms);}
}
