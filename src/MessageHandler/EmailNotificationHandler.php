<?php

namespace App\MessageHandler;

use App\Message\EmailNotificationMessage;
use App\Repository\OrderRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailNotificationHandler
{
    private const FROM_EMAIL = 'shop@example.com';

    public function __construct(
        private MailerInterface $mailer,
        private OrderRepository $orderRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(EmailNotificationMessage $message)
    {
        $order = $this->orderRepository->find($message->orderId);
        if (!$order) {
            $this->logger->error("Order not found: {$message->orderId}");
            return;
        }

        $subject = '';
        $body = '';

        switch ($message->type) {
            case 'created':
                $subject = 'Welcome to our shop!';
                $body = "Hello {$order->getCustomerName()}, your order #{$order->getId()} has been successfully created.";
                break;
            case 'shipped':
                $subject = 'Your order has been shipped';
                $body = "Hello {$order->getCustomerName()}, your order #{$order->getId()} is on the way!";
                break;
            case 'delivered':
                $subject = 'Thank you for your order';
                $body = "Hello {$order->getCustomerName()}, thank you for receiving your order #{$order->getId()}!";
                break;
            default:
                return;
        }

        $email = (new Email())
            ->from(self::FROM_EMAIL)
            ->to($order->getCustomerEmail())
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
        $this->logger->info("Email ({$message->type}) sent for Order #{$order->getId()}");
    }
}

