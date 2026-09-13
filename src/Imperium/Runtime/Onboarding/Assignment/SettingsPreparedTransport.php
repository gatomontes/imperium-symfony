<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Citadel\Formation\PreparedFormationTransport;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class SettingsPreparedTransport extends SettingsBoundTransport implements PreparedFormationTransport
{
    public function __construct(PersistentSettings $settings,PreparedFormationTransport $transport,string $role){parent::__construct($settings,$transport,$role);}
    public function prepareOperation(array $request,array $terms):array{$this->check($terms);return $this->transport->prepareOperation($request,$terms);}
}
