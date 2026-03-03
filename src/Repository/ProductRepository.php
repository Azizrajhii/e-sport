<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }
    /**
     * Search products by name
     *
     * @return Product[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Return all products ordered by creation date descending
     *
     * @return Product[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
// src/Repository/ProductRepository.php

public function findTopSales(int $limit = 3): array
{
    $query = $this->createQueryBuilder('p')
        ->select('p', 'SUM(oi.quantity) as HIDDEN totalSold')
        ->join('App\Entity\OrderItem', 'oi', 'WITH', 'oi.product = p')
        ->join('oi.order', 'o')
        ->where('o.status = :status')
        ->setParameter('status', 'paid')
        ->groupBy('p.id')
        ->orderBy('totalSold', 'DESC')
        ->setMaxResults($limit)
        ->getQuery();

    $paginator = new Paginator($query, fetchJoinCollection: true);

    return iterator_to_array($paginator);
}
public function searchh(string $term): array
{
    return $this->createQueryBuilder('p')
        ->where('p.name LIKE :term')
        ->setParameter('term', '%' . $term . '%')
        ->getQuery()
        ->getResult();
}

public function findAllOrderedd(): array
{
    return $this->createQueryBuilder('p')
        ->orderBy('p.id', 'DESC')
        ->getQuery()
        ->getResult();
}
    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
