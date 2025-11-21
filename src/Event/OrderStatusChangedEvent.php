<?php

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderStatusChangedEvent
{
    public const NAME = 'order.status_changed';

    public function __construct(private Order $order, private string $oldStatus, private string $newStatus)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }
}
