<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class Order
{
    #[Assert\NotBlank]
    public string $customerName;

    #[Assert\Email]
    public string $customerEmail;

    #[Assert\Positive]
    public float $totalAmount;

    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    /** @var OrderItem[] */
    public array $items = [];

    public function __construct(string $customerName, string $customerEmail, float $totalAmount, array $items)
    {
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->totalAmount = $totalAmount;
        $this->items = $items;
    }
}
