<?php

namespace App\Entity;

use App\Repository\PortfolioValuationSnapshotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortfolioValuationSnapshotRepository::class)]
#[ORM\Table(name: 'portfolio_valuation_snapshots')]
class PortfolioValuationSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'calculated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $calculatedAt;

    #[ORM\Column(name: 'amount_usdt', type: 'decimal', precision: 20, scale: 2)]
    private string $amountUsdt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalculatedAt(): \DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(\DateTimeImmutable $calculatedAt): self
    {
        $this->calculatedAt = $calculatedAt;

        return $this;
    }

    public function getAmountUsdt(): string
    {
        return $this->amountUsdt;
    }

    public function setAmountUsdt(string $amountUsdt): self
    {
        $this->amountUsdt = $amountUsdt;

        return $this;
    }
}
