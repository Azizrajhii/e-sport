<?php

namespace App\Repository;

use App\Entity\Equipe;
use App\Entity\MatchGame;
use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MatchGame>
 */
class MatchGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatchGame::class);
    }

    /**
     * @return MatchGame[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.equipe1', 'e1')
            ->leftJoin('m.equipe2', 'e2')
            ->where('e1.nom LIKE :q')
            ->orWhere('e2.nom LIKE :q')
            ->orWhere('m.statut LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('m.dateMatch', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return MatchGame[]
     */
    public function findByTournoi(Tournoi $tournoi): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.tournoi = :tournoi')
            ->setParameter('tournoi', $tournoi)
            ->orderBy('m.dateMatch', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Matchs où l'équipe est equipe1 ou equipe2 (pour suppression en cascade manuelle).
     * @return MatchGame[]
     */
    public function findByEquipe(Equipe $equipe): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.equipe1 = :equipe OR m.equipe2 = :equipe')
            ->setParameter('equipe', $equipe)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return MatchGame[] Returns an array of MatchGame objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MatchGame
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
