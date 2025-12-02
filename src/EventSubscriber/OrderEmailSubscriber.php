<?php

namespace App\EventSubscriber;

use App\Event\OrderCreatedEvent;
use App\Event\OrderStatusChangedEvent;
use App\Message\EmailNotificationMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class OrderEmailSubscriber implements EventSubscriberInterface
{
    public function __construct(private MessageBusInterface $bus) {}

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreatedEvent::NAME => 'onOrderCreated',
            OrderStatusChangedEvent::NAME => 'onOrderStatusChanged',
        ];
    }

    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->bus->dispatch(new EmailNotificationMessage(
            $event->getOrder()->getId(),
            'created'
        ));
    }

    public function onOrderStatusChanged(OrderStatusChangedEvent $event): void
    {
        $status = $event->getNewStatus();
        if (!in_array($status, ['shipped', 'delivered'])) {
            return;
        }

        $this->bus->dispatch(new EmailNotificationMessage(
            $event->getOrder()->getId(),
            $status
        ));
    }
}
