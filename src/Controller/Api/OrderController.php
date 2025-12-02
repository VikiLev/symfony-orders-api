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
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class OrderController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private OrderRepository    $orderRepository,
        private CreateOrder        $createOrder,
        private UpdateOrder        $updateOrder,
        private DeleteOrder        $deleteOrder,
        private ChangeOrderStatus  $changeOrderStatus,
        private LoggerInterface    $logger,
        private KernelInterface    $kernel
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
            return $this->createErrorResponse('Order not found', Response::HTTP_NOT_FOUND);
        }

        return $this->json($order, Response::HTTP_OK);
    }

    #[Route('/api/orders', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $orderDto = $this->createOrderDtoFromRequest($request);
        if ($orderDto instanceof Response) {
            return $orderDto; // Error response
        }

        $validationError = $this->validateOrderDto($orderDto);
        if ($validationError !== null) {
            return $validationError;
        }

        try {
            /** @var Order $order */
            $order = $this->createOrder->createOrder($orderDto);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Validation error while creating order', [
                'exception' => $e->getMessage(),
                'dto' => [
                    'customerName' => $orderDto->customerName,
                    'customerEmail' => $orderDto->customerEmail,
                ]
            ]);
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create order', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'dto' => [
                    'customerName' => $orderDto->customerName,
                    'customerEmail' => $orderDto->customerEmail,
                ]
            ]);
            
            $errorMessage = $this->getErrorMessage($e);
            return $this->createErrorResponse('Failed to create order', Response::HTTP_INTERNAL_SERVER_ERROR, $errorMessage);
        }

        return $this->json(['id' => $order->getId()], Response::HTTP_CREATED);
    }

    #[Route('/api/orders/{id}', name: 'update_order', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $orderDto = $this->createOrderDtoFromRequest($request);
        if ($orderDto instanceof Response) {
            return $orderDto; // Error response
        }

        $validationError = $this->validateOrderDto($orderDto);
        if ($validationError !== null) {
            return $validationError;
        }

        try {
            $order = $this->updateOrder->update($id, $orderDto);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Validation error while updating order', [
                'orderId' => $id,
                'exception' => $e->getMessage()
            ]);
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Failed to update order', [
                'orderId' => $id,
                'exception' => $e->getMessage()
            ]);
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while updating order', [
                'orderId' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $errorMessage = $this->getErrorMessage($e);
            return $this->createErrorResponse('Failed to update order', Response::HTTP_INTERNAL_SERVER_ERROR, $errorMessage);
        }

        return $this->json($order);
    }

    #[Route('/api/orders/{id}', name: 'delete_order', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        try {
            $this->deleteOrder->deleteById($id);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Failed to delete order', [
                'orderId' => $id,
                'exception' => $e->getMessage()
            ]);
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while deleting order', [
                'orderId' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $errorMessage = $this->getErrorMessage($e);
            return $this->createErrorResponse('Failed to delete order', Response::HTTP_INTERNAL_SERVER_ERROR, $errorMessage);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/orders/{id}/status', name: 'update_order_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): Response
    {
        try {
            $data = $request->toArray();
            $status = $this->sanitizeString($data['status'] ?? '');
            $order = $this->changeOrderStatus->changeStatus($id, $status);
        } catch (\RuntimeException $e) {
            $this->logger->warning('Failed to update order status', [
                'orderId' => $id,
                'exception' => $e->getMessage()
            ]);
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid status provided', [
                'orderId' => $id,
                'status' => $data['status'] ?? null,
                'exception' => $e->getMessage()
            ]);
            return $this->createErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error while updating order status', [
                'orderId' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $errorMessage = $this->getErrorMessage($e);
            return $this->createErrorResponse('Failed to update status', Response::HTTP_INTERNAL_SERVER_ERROR, $errorMessage);
        }

        return $this->json([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'updatedAt' => $order->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Creates OrderDTO from Request, returns Response if error occurred
     */
    private function createOrderDtoFromRequest(Request $request): OrderDTO|Response
    {
        try {
            $data = $request->toArray();
        } catch (\Exception $e) {
            $this->logger->warning('Invalid JSON in request', [
                'exception' => $e->getMessage()
            ]);
            return $this->createErrorResponse('Invalid JSON format', Response::HTTP_BAD_REQUEST);
        }

        // Sanitize input data
        $itemsDto = [];
        foreach ($data['items'] ?? [] as $item) {
            $itemsDto[] = new OrderItemDTO(
                $this->sanitizeString($item['productName'] ?? ''),
                max(1, (int)($item['quantity'] ?? 0)),
                max(0, (float)($item['price'] ?? 0))
            );
        }

        return new OrderDTO(
            $this->sanitizeString($data['customerName'] ?? ''),
            $this->sanitizeEmail($data['customerEmail'] ?? ''),
            max(0, (float)($data['totalAmount'] ?? 0)),
            $itemsDto
        );
    }

    /**
     * Sanitizes string input
     */
    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Sanitizes email input
     */
    private function sanitizeEmail(string $email): string
    {
        return trim(strtolower(filter_var($email, FILTER_SANITIZE_EMAIL)));
    }

    /**
     * Creates consistent error response
     */
    private function createErrorResponse(string $message, int $statusCode, ?string $details = null): JsonResponse
    {
        $response = ['error' => $message];
        if ($details !== null && $this->kernel->getEnvironment() !== 'prod') {
            $response['details'] = $details;
        }
        return $this->json($response, $statusCode);
    }

    /**
     * Validates OrderDTO, returns Response with errors if validation failed, null otherwise
     */
    private function validateOrderDto(OrderDTO $orderDto): ?Response
    {
        $errors = $this->validator->validate($orderDto);
        if (count($errors) > 0) {
            $messages = $this->formatValidationErrors($errors);
            return $this->json(['errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    /**
     * Formats validation errors into array of messages
     */
    private function formatValidationErrors(ConstraintViolationListInterface $errors): array
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
        }
        return $messages;
    }

    /**
     * Returns error message based on environment (hides details in production)
     */
    private function getErrorMessage(\Exception $e): string
    {
        // In production, don't expose internal error details
        if ($this->kernel->getEnvironment() === 'prod') {
            return 'An internal error occurred. Please try again later.';
        }
        
        return $e->getMessage();
    }

}
