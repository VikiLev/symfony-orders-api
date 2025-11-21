<?php

namespace App\Service;

use App\DTO\Order as OrderDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use App\Event\OrderCreatedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CreateOrder
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher
    ) {}

    public function createOrder(OrderDTO $dto): Order
    {
        $order = new Order();
        $order->setCustomerName($dto->customerName);
        $order->setCustomerEmail($dto->customerEmail);
        $order->setTotalAmount($dto->totalAmount);
        $order->setStatus(Order::STATUS_PENDING);

        foreach ($dto->items as $itemDto) {
            $item = new OrderItem();
            $item->setProductName($itemDto->productName);
            $item->setQuantity($itemDto->quantity);
            $item->setPrice($itemDto->price);
            $item->setOrder($order);

            $order->addItem($item);
        }

        $this->em->persist($order);
        $this->em->flush();

        $this->dispatcher->dispatch(new OrderCreatedEvent($order), OrderCreatedEvent::NAME);

        return $order;
    }
}
