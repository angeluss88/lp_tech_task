<?php

namespace App\Controller;

use App\Repository\PortfolioValuationSnapshotRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PortfolioHistoryController extends AbstractController
{
    #[OA\Get(
        path: '/api/portfolio/history',
        summary: 'Get portfolio valuation history',
        description: 'Returns historical portfolio valuation snapshots in chronological order.',
        parameters: [
            new OA\Parameter(
                name: 'hours',
                description: 'Lookback window in hours (cannot be combined with from/to)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'from',
                description: 'Start datetime in ISO-8601 format',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'to',
                description: 'End datetime in ISO-8601 format',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'History response',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'time', type: 'string', format: 'date-time', example: '2026-02-26T10:00:00Z'),
                            new OA\Property(property: 'amount_usdt', type: 'number', format: 'float', example: 123456.78),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'The "hours" parameter must be a positive number.'),
                    ]
                )
            ),
        ]
    )]
    #[Route('/api/portfolio/history', name: 'api_portfolio_history', methods: ['GET'])]
    public function __invoke(
        Request $request,
        PortfolioValuationSnapshotRepository $repository,
        ValidatorInterface $validator,
    ): JsonResponse {
        $hoursRaw = $request->query->get('hours');
        $fromRaw = $request->query->get('from');
        $toRaw = $request->query->get('to');

        if (($fromRaw !== null && !is_string($fromRaw)) || ($toRaw !== null && !is_string($toRaw))) {
            return $this->json(
                ['error' => 'The "from" and "to" parameters must be strings in ISO-8601 format.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $hasRange = is_string($fromRaw) || is_string($toRaw);
        if ($hoursRaw !== null && $hasRange) {
            return $this->json(
                ['error' => 'Use either "hours" or "from/to" parameters, not both.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            if ($hoursRaw !== null) {
                $violations = $validator->validate($hoursRaw, [
                    new Assert\Type('numeric'),
                    new Assert\Positive(),
                ]);

                if (count($violations) > 0) {
                    return $this->json(
                        ['error' => 'The "hours" parameter must be a positive number.'],
                        Response::HTTP_BAD_REQUEST
                    );
                }

                $hours = (int) $hoursRaw;
                $to = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
                $from = $to->sub(new \DateInterval(sprintf('PT%dH', $hours)));
            } else {
                $from = is_string($fromRaw) ? new \DateTimeImmutable($fromRaw) : null;
                $to = is_string($toRaw) ? new \DateTimeImmutable($toRaw) : null;
            }
        } catch (\Exception) {
            return $this->json(
                ['error' => 'Invalid date format. Use ISO-8601 format for "from" and "to".'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($from !== null && $to !== null && $from > $to) {
            return $this->json(
                ['error' => '"from" must be earlier than or equal to "to".'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // @todo Add pagination/limit support when snapshot volume grows to keep this endpoint bounded.
        $history = $repository->findHistory($from, $to);

        $payload = array_map(
            static fn ($item): array => [
                'time' => $item->getCalculatedAt()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
                'amount_usdt' => (float) $item->getAmountUsdt(),
            ],
            $history
        );

        return $this->json($payload);
    }
}
