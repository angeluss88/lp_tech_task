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
        $total = 0.0;
        $prices = [];

        foreach ($this->portfolioHoldings as $asset => $amountRaw) {
            $asset = strtoupper((string) $asset);
            $amount = (float) $amountRaw;

            // USDT is already the quote currency, so no external price lookup is needed.
            if ($asset === 'USDT') {
                $total += $amount;

                continue;
            }

            $price = $this->binancePriceService->getAveragePriceUsdt($asset);
            $prices[$asset.'USDT'] = $price;
            $total += $amount * $price;
        }

        $this->logger->info('Portfolio valuation calculated.', [
            'holdings' => $this->portfolioHoldings,
            'prices' => $prices,
            'total_usdt' => $total,
        ]);

        return round($total, 2);
    }
}
