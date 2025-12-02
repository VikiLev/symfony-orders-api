<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class OrderItem
{
    #[Assert\NotBlank(message: 'Product name is required')]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: 'Product name must be at least {{ limit }} characters',
        maxMessage: 'Product name cannot be longer than {{ limit }} characters'
    )]
    public string $productName;

    #[Assert\Positive(message: 'Quantity must be positive')]
    #[Assert\LessThanOrEqual(
        value: 10000,
        message: 'Quantity cannot exceed {{ compared_value }}'
    )]
    public int $quantity;

    #[Assert\Positive(message: 'Price must be positive')]
    #[Assert\LessThanOrEqual(
        value: 999999.99,
        message: 'Price cannot exceed {{ compared_value }}'
    )]
    public float $price;

    public function __construct(string $productName, int $quantity, float $price)
    {
        $this->productName = $productName;
        $this->quantity = $quantity;
        $this->price = $price;
    }
}
