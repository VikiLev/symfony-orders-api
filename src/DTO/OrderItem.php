<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class OrderItem
{
    #[Assert\NotBlank]
    public string $productName;

    #[Assert\Positive]
    public int $quantity;

    #[Assert\Positive]
    public float $price;

    public function __construct(string $productName, int $quantity, float $price)
    {
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->price = $price;
    }
}
