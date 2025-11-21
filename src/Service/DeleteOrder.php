<?php

namespace App\Service;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeleteOrder
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository        $orderRepository
    )
    {}

    public function deleteById(int $orderId): void
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found');
        }

        $this->em->remove($order);
        $this->em->flush();
    }
}
