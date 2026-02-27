<?php

namespace App\Command;

use App\Entity\PortfolioValuationSnapshot;
use App\Service\PortfolioValuationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:portfolio:snapshot',
    description: 'Calculates and stores hourly portfolio valuation in USDT.',
)]
class PortfolioSnapshotCommand extends Command
{
    public function __construct(
        private readonly PortfolioValuationService $valuationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $calculatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            $totalUsdt = $this->valuationService->calculateTotalUsdt();

            $snapshot = (new PortfolioValuationSnapshot())->setCalculatedAt($calculatedAt);
            $snapshot->setAmountUsdt(number_format($totalUsdt, 2, '.', ''));
            $this->entityManager->persist($snapshot);
            $this->entityManager->flush();

            $this->logger->info('Portfolio snapshot stored.', [
                'calculated_at' => $calculatedAt->format(\DateTimeInterface::ATOM),
                'amount_usdt' => $snapshot->getAmountUsdt(),
            ]);

            $io->success(sprintf(
                'Snapshot saved for %s. Amount: %s USDT',
                $calculatedAt->format(\DateTimeInterface::ATOM),
                $snapshot->getAmountUsdt()
            ));

            return Command::SUCCESS;
        } catch (\Throwable $throwable) {
            $this->logger->error('Portfolio snapshot command failed.', [
                'message' => $throwable->getMessage(),
            ]);

            $io->error(sprintf('Snapshot failed: %s', $throwable->getMessage()));

            return Command::FAILURE;
        }
    }
}
