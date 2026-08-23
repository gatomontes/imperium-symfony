<?php
declare(strict_types=1);namespace App\Imperium\Runtime\Authorship;
interface SubordinatePersonaSectionAuthorshipGateway{public function author(string $office,array $acceptance,array $commission,array $specification,array $case):array;}
