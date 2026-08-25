<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Garrison;
interface TerminalTransitionFaultInjector{public function after(string $checkpoint):void;}
