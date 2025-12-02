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
        // Validate total amount matches sum of items
        $calculatedTotal = $this->calculateTotalFromItems($dto->items);
        if (abs($calculatedTotal - $dto->totalAmount) > 0.01) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Total amount (%.2f) does not match sum of items (%.2f)',
                    $dto->totalAmount,
                    $calculatedTotal
                )
            );
        }

        $this->em->beginTransaction();
        try {
            $order = new Order();
            $order->setCustomerName($this->sanitizeString($dto->customerName));
            $order->setCustomerEmail($this->sanitizeEmail($dto->customerEmail));
            $order->setTotalAmount((string)$dto->totalAmount);
            $order->setStatus(Order::STATUS_PENDING);

            foreach ($dto->items as $itemDto) {
                $item = new OrderItem();
                $item->setProductName($this->sanitizeString($itemDto->productName));
                $item->setQuantity($itemDto->quantity);
                $item->setPrice((string)$itemDto->price);
                $item->setOrder($order);

                $order->addItem($item);
            }

            $this->em->persist($order);
            $this->em->flush();
            $this->em->commit();

            $this->dispatcher->dispatch(new OrderCreatedEvent($order), OrderCreatedEvent::NAME);

            return $order;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function calculateTotalFromItems(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item->quantity * $item->price;
        }
        return $total;
    }

    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function sanitizeEmail(string $email): string
    {
        return trim(strtolower(filter_var($email, FILTER_SANITIZE_EMAIL)));
    }
}
