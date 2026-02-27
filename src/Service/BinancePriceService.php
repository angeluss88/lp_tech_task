<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BinancePriceService
{
    private const AVG_PRICE_ENDPOINT = 'https://api.binance.com/api/v3/avgPrice';
    private const MAX_ATTEMPTS = 2;
    private const RETRY_DELAY_MICROSECONDS = 250000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getAveragePriceUsdt(string $asset): float
    {
        $symbol = strtoupper($asset).'USDT';

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            try {
                $response = $this->httpClient->request('GET', self::AVG_PRICE_ENDPOINT, [
                    'query' => ['symbol' => $symbol],
                    'timeout' => 10,
                ]);

                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);

                if ($statusCode === 200 && isset($data['price']) && is_string($data['price'])) {
                    return (float) $data['price'];
                }

                $this->logger->warning('Invalid Binance avgPrice response.', [
                    'asset' => $asset,
                    'symbol' => $symbol,
                    'attempt' => $attempt,
                    'status_code' => $statusCode,
                    'response' => $data,
                ]);
            } catch (ExceptionInterface $exception) {
                $this->logger->warning('Binance request failed.', [
                    'asset' => $asset,
                    'symbol' => $symbol,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                ]);
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                usleep(self::RETRY_DELAY_MICROSECONDS);
            }
        }

        throw new \RuntimeException(sprintf('Unable to fetch price for %s after %d attempts.', $symbol, self::MAX_ATTEMPTS));
    }
}
