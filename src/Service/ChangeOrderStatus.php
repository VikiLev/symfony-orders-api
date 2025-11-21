<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Event\OrderStatusChangedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChangeOrderStatus
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository        $orderRepository,
        private EventDispatcherInterface $dispatcher
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

        $oldStatus = $order->getStatus();

        $order->setStatus($status);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->dispatcher->dispatch(
            new OrderStatusChangedEvent($order, $oldStatus, $status),
            OrderStatusChangedEvent::NAME
        );

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
