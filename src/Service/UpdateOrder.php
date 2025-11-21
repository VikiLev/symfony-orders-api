<?php

namespace App\Service;

use App\DTO\Order as OrderDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\OrderRepository;

class UpdateOrder
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository        $orderRepository
    )
    {}

    public function update(int $orderId, OrderDTO $dto): Order
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        $order->setCustomerName($dto->customerName);
        $order->setCustomerEmail($dto->customerEmail);
        $order->setTotalAmount($dto->totalAmount);
        $order->setUpdatedAt(new \DateTimeImmutable());

        $order->getItems()->clear();
        foreach ($dto->items as $itemDto) {
            $item = new OrderItem();
            $item->setProductName($itemDto->productName);
            $item->setQuantity($itemDto->quantity);
            $item->setPrice($itemDto->price);
            $item->setOrder($order);
            $order->addItem($item);
        }

        $this->em->flush();

        return $order;
    }
}
