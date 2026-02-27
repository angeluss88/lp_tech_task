<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class PortfolioValuationService
{
    /**
     * @param array<string, float|int> $portfolioHoldings
     */
    public function __construct(
        private readonly BinancePriceService $binancePriceService,
        private readonly LoggerInterface $logger,
        private readonly array $portfolioHoldings,
    ) {
    }

    public function calculateTotalUsdt(): float
    {
        $btcAmount = (float) ($this->portfolioHoldings['BTC'] ?? 0.0);
        $ethAmount = (float) ($this->portfolioHoldings['ETH'] ?? 0.0);
        $solAmount = (float) ($this->portfolioHoldings['SOL'] ?? 0.0);
        $usdtAmount = (float) ($this->portfolioHoldings['USDT'] ?? 0.0);

        $btcPrice = $this->binancePriceService->getAveragePriceUsdt('BTC');
        $ethPrice = $this->binancePriceService->getAveragePriceUsdt('ETH');
        $solPrice = $this->binancePriceService->getAveragePriceUsdt('SOL');

        $total = ($btcAmount * $btcPrice) + ($ethAmount * $ethPrice) + ($solAmount * $solPrice) + $usdtAmount;

        $this->logger->info('Portfolio valuation calculated.', [
            'holdings' => $this->portfolioHoldings,
            'prices' => [
                'BTCUSDT' => $btcPrice,
                'ETHUSDT' => $ethPrice,
                'SOLUSDT' => $solPrice,
            ],
            'total_usdt' => $total,
        ]);

        return round($total, 2);
    }
}
