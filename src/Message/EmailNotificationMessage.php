<?php

namespace App\Message;

class EmailNotificationMessage
{
    public function __construct(
        public int $orderId,
        public string $type
    ) {}
}
