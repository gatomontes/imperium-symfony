<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Sortie\SortieCognitionGateway;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SortieServiceWiringTest extends KernelTestCase
{
    public function testSortieCognitionGatewayCompilesWithToolExecutor(): void
    {
        self::bootKernel(['environment' => 'sortie', 'debug' => false]);

        $container = self::getContainer();
        self::assertTrue($container->has(SortieCognitionGateway::class));
        self::assertInstanceOf(SortieCognitionGateway::class, $container->get(SortieCognitionGateway::class));
    }
}
