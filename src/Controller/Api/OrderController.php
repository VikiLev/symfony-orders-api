<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\CreateOrder;
use App\Service\UpdateOrder;
use App\Service\DeleteOrder;
use App\Service\ChangeOrderStatus;
use App\DTO\Order as OrderDTO;
use App\DTO\OrderItem as OrderItemDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private OrderRepository    $orderRepository,
        private CreateOrder        $createOrder,
        private UpdateOrder        $updateOrder,
        private DeleteOrder        $deleteOrder,
        private ChangeOrderStatus  $changeOrderStatus
    )
    {
    }

    #[Route('/api/orders', name: 'get_orders', methods: ['GET'])]
    public function getOrders(Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = min(100, max(1, (int)$request->query->get('limit', 10)));

        $filters = [
            'status' => $request->query->get('status'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'email' => $request->query->get('email'),
        ];

        $result = $this->orderRepository->getFilteredOrders($page, $limit, $filters);

        return $this->json($result);
    }

    #[Route('/api/orders/{id}', name: 'get_order_by_id', methods: ['GET'])]
    public function getOrderById(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(
                ['error' => 'Order not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($order, Response::HTTP_OK);
    }

    #[Route('/api/orders', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            $data = $request->toArray();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $itemsDto = [];
        foreach ($data['items'] ?? [] as $item) {
            $itemsDto[] = new OrderItemDTO(
                $item['productName'] ?? '',
                (int)($item['quantity'] ?? 0),
                (float)($item['price'] ?? 0)
            );
        }

        $orderDto = new OrderDTO(
            $data['customerName'] ?? '',
            $data['customerEmail'] ?? '',
            (float)($data['totalAmount'] ?? 0),
            $itemsDto
        );

        $errors = $this->validator->validate($orderDto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var Order $order */
            $order = $this->createOrder->createOrder($orderDto);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to create order', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json(['id' => $order->getId()], Response::HTTP_CREATED);
    }

    #[Route('/api/orders/{id}', name: 'update_order', methods: ['PUT'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        try {
            $data = $request->toArray();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $itemsDto = [];
        foreach ($data['items'] ?? [] as $item) {
            $itemsDto[] = new OrderItemDTO(
                $item['productName'] ?? '',
                (int)($item['quantity'] ?? 0),
                (float)($item['price'] ?? 0)
            );
        }

        $dto = new OrderDTO(
            $data['customerName'] ?? '',
            $data['customerEmail'] ?? '',
            (float)($data['totalAmount'] ?? 0),
            $itemsDto
        );

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $order = $this->updateOrder->update($id, $dto);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json($order);
    }

    #[Route('/api/orders/{id}', name: 'delete_order', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $this->deleteOrder->deleteById($id);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to delete order', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/orders/{id}/status', name: 'update_order_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): Response
    {
        try {
            $data = $request->toArray();
            $status = $data['status'] ?? '';
            $order = $this->changeOrderStatus->changeStatus($id, $status);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update status'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'updatedAt' => $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

}
