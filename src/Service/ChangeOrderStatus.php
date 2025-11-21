<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class ChangeOrderStatus
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository        $orderRepository
    )
    {}

    public function changeStatus(int $orderId, string $status): Order
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        if (!$this->isValidStatus($status)) {
            throw new \InvalidArgumentException('Invalid status value');
        }

        $order->setStatus($status);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $order;
    }

    private function isValidStatus(string $status): bool
    {
        return in_array($status, [
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ], true);
    }
}
