<?php

namespace App\Tests\Service;

use App\Service\BinancePriceService;
use App\Service\PortfolioValuationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PortfolioValuationServiceTest extends TestCase
{
    public function testCalculateTotalUsdt(): void
    {
        $binancePriceService = $this->createMock(BinancePriceService::class);
        $binancePriceService
            ->expects(self::exactly(3))
            ->method('getAveragePriceUsdt')
            ->willReturnMap([
                ['BTC', 50000.0],
                ['ETH', 3000.0],
                ['SOL', 100.0],
            ]);

        $service = new PortfolioValuationService(
            $binancePriceService,
            new NullLogger(),
            [
                'BTC' => 1,
                'ETH' => 10,
                'SOL' => 50,
                'USDT' => 5000,
            ]
        );

        $total = $service->calculateTotalUsdt();

        self::assertSame(90000.0, $total);
    }
}
