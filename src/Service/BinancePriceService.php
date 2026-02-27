<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BinancePriceService
{
    private const AVG_PRICE_ENDPOINT = 'https://api.binance.com/api/v3/avgPrice';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getAveragePriceUsdt(string $asset): float
    {
        $symbol = strtoupper($asset).'USDT';

        try {
            $response = $this->httpClient->request('GET', self::AVG_PRICE_ENDPOINT, [
                'query' => ['symbol' => $symbol],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200 || !isset($data['price']) || !is_string($data['price'])) {
                $this->logger->error('Invalid Binance avgPrice response.', [
                    'asset' => $asset,
                    'symbol' => $symbol,
                    'status_code' => $statusCode,
                    'response' => $data,
                ]);

                throw new \RuntimeException(sprintf('Failed to fetch avg price for %s.', $symbol));
            }

            return (float) $data['price'];
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Binance request failed.', [
                'asset' => $asset,
                'symbol' => $symbol,
                'message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException(sprintf('Unable to fetch price for %s.', $symbol), previous: $exception);
        }
    }
}
