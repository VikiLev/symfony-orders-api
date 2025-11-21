<?php

declare(strict_types=1);


namespace Tests\Api;

use Tests\Support\ApiTester;
use Symfony\Component\HttpClient\HttpClient;
use PHPUnit\Framework\Assert;

final class OrderCest
{
    private $client;

    public function _before(ApiTester $I): void
    {
        $this->client = HttpClient::create(['base_uri' => 'http://127.0.0.1:34319']);
    }

    public function createOrder(ApiTester $I): void
    {
        $data = [
            'customerName' => 'Viki',
            'customerEmail' => 'viki@example.com',
            'totalAmount' => 100,
            'items' => [
                ['productName' => 'Book', 'quantity' => 1, 'price' => 100]
            ]
        ];

        $response = $this->client->request('POST', '/api/orders', [
            'json' => $data
        ]);

        Assert::assertEquals(201, $response->getStatusCode());
        $responseData = $response->toArray();
        Assert::assertArrayHasKey('id', $responseData);
    }

    public function getOrderDetails(ApiTester $I): void
    {
        $data = [
            'customerName' => 'Viki',
            'customerEmail' => 'viki@example.com',
            'totalAmount' => 100,
            'items' => [
                ['productName' => 'Book', 'quantity' => 1, 'price' => 100]
            ]
        ];

        $createResponse = $this->client->request('POST', '/api/orders', [
            'json' => $data
        ]);

        $createData = $createResponse->toArray();
        $orderId = $createData['id'];

        $response = $this->client->request('GET', "/api/orders/{$orderId}");

        Assert::assertEquals(200, $response->getStatusCode());
        $responseData = $response->toArray();
        Assert::assertEquals('Viki', $responseData['customerName']);
        Assert::assertEquals('viki@example.com', $responseData['customerEmail']);
    }
}
