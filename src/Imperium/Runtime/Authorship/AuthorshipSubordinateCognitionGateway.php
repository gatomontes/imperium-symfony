<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Authorship;
interface AuthorshipSubordinateCognitionGateway { public function resolve(string $office, array $acceptance, array $commission, array $occupancy): array; }
