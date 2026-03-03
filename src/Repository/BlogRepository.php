<?php

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blog>
 */
class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    public function searchByTitleAndDate(?string $title, ?string $date)
    {
        $qb = $this->createQueryBuilder('b');

        if ($title) {
            $qb->andWhere('b.title LIKE :title')
               ->setParameter('title', '%' . $title . '%');
        }

        if ($date) {
            $start = new \DateTimeImmutable($date . ' 00:00:00');
            $end = new \DateTimeImmutable($date . ' 23:59:59');

            $qb->andWhere('b.createdAt BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        $qb->orderBy('b.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find blogs with their ratings and average
     * 
     * @return Blog[] Returns an array of Blog objects with rating info
     */
    public function findAllWithRatings(): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.ratings', 'r')
            ->addSelect('r')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find top rated blogs
     * 
     * @param int $limit Maximum number of blogs to return
     * @return Blog[] Returns an array of Blog objects ordered by average rating
     */
    public function findTopRated(int $limit = 10): array
    {
        $query = $this->createQueryBuilder('b')
            ->leftJoin('b.ratings', 'r')
            ->addSelect('AVG(r.value) as HIDDEN avg_rating')
            ->addSelect('COUNT(r.id) as HIDDEN rating_count')
            ->groupBy('b.id')
            ->having('rating_count > 0')
            ->orderBy('avg_rating', 'DESC')
            ->addOrderBy('rating_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query, fetchJoinCollection: true);

        return iterator_to_array($paginator);
    }

    /**
     * Find blogs by category with their ratings
     * 
     * @param string $category The category to filter by
     * @return Blog[] Returns an array of Blog objects
     */
    public function findByCategoryWithRatings(string $category): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.ratings', 'r')
            ->addSelect('r')
            ->andWhere('b.category = :category')
            ->setParameter('category', $category)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search by title and date with ratings included
     * 
     * @param string|null $title
     * @param string|null $date
     * @return Blog[] Returns an array of Blog objects
     */
    public function searchByTitleAndDateWithRatings(?string $title, ?string $date): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.ratings', 'r')
            ->addSelect('r');

        if ($title) {
            $qb->andWhere('b.title LIKE :title')
               ->setParameter('title', '%' . $title . '%');
        }

        if ($date) {
            $start = new \DateTimeImmutable($date . ' 00:00:00');
            $end = new \DateTimeImmutable($date . ' 23:59:59');

            $qb->andWhere('b.createdAt BETWEEN :start AND :end')
               ->setParameter('start', $start)
               ->setParameter('end', $end);
        }

        $qb->orderBy('b.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get blogs with highest number of ratings
     * 
     * @param int $limit Maximum number of blogs to return
     * @return Blog[] Returns an array of Blog objects
     */
    public function findMostRated(int $limit = 10): array
    {
        $query = $this->createQueryBuilder('b')
            ->leftJoin('b.ratings', 'r')
            ->addSelect('COUNT(r.id) as HIDDEN rating_count')
            ->groupBy('b.id')
            ->orderBy('rating_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        $paginator = new Paginator($query, fetchJoinCollection: true);

        return iterator_to_array($paginator);
    }

    /**
     * Get blogs with average rating above a threshold
     * 
     * @param float $threshold Minimum average rating (1-5)
     * @return Blog[] Returns an array of Blog objects
     */
    public function findByMinimumRating(float $threshold): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.ratings', 'r')
            ->addSelect('AVG(r.value) as HIDDEN avg_rating')
            ->groupBy('b.id')
            ->having('avg_rating >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('avg_rating', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function search(string $term)
{
    return $this->createQueryBuilder('b')
                ->where('b.title LIKE :term')
                ->setParameter('term', '%'.$term.'%')
                ->getQuery()
                ->getResult();
}

    //    /**
    //     * @return Blog[] Returns an array of Blog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Blog
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}