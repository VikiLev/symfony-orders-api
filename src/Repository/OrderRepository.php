<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function getFilteredOrders(int $page, int $limit, array $filters): array
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.id', 'DESC');

        if (!empty($filters['status'])) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $dateFrom = \DateTime::createFromFormat('Y-m-d', $filters['date_from']);
            if ($dateFrom) {
                $dateFrom->setTime(0, 0, 0);
                $qb->andWhere('o.created_at >= :date_from')
                    ->setParameter('date_from', $dateFrom);
            }
        }

        if (!empty($filters['date_to'])) {
            $dateTo = \DateTime::createFromFormat('Y-m-d', $filters['date_to']);
            if ($dateTo) {
                $dateTo->setTime(23, 59, 59);
                $qb->andWhere('o.created_at <= :date_to')
                    ->setParameter('date_to', $dateTo);
            }
        }

        if (!empty($filters['email'])) {
            $qb->andWhere('o.customer_email LIKE :email')
                ->setParameter('email', '%' . $filters['email'] . '%');
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();

        $countQb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)');

        if (!empty($filters['status'])) {
            $countQb->andWhere('o.status = :status')
                ->setParameter('status', $filters['status']);
        }
        if (!empty($filters['date_from']) && $dateFrom) {
            $countQb->andWhere('o.created_at >= :date_from')
                ->setParameter('date_from', $dateFrom);
        }
        if (!empty($filters['date_to']) && $dateTo) {
            $countQb->andWhere('o.created_at <= :date_to')
                ->setParameter('date_to', $dateTo);
        }
        if (!empty($filters['email'])) {
            $countQb->andWhere('o.customer_email LIKE :email')
                ->setParameter('email', '%' . $filters['email'] . '%');
        }

        try {
            $total = (int)$countQb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $total = 0;
        }

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => $items,
        ];
    }
}
