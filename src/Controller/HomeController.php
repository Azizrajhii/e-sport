<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Equipe;
use App\Entity\Jeu;
use App\Entity\MatchGame;
use App\Entity\Tournoi;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProductRepository;

final class HomeController extends AbstractController
{
   #[Route('/', name: 'app_home')]
public function index(EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
{
    $ranking = $this->computeGlobalRanking($entityManager);
    $topSales = $productRepository->findTopSales(3);

    // Featured tournaments: running ones first, then the next to start
    $tournois = $entityManager->getRepository(Tournoi::class)->createQueryBuilder('t')
        ->where('t.statut != :done')
        ->setParameter('done', 'Terminé')
        ->orderBy('t.date_debut', 'ASC')
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();

    $jeux = $entityManager->getRepository(Jeu::class)->findBy(['statut' => 'ACTIVE'], ['id' => 'ASC'], 6);

    $recentMatches = $entityManager->getRepository(MatchGame::class)->createQueryBuilder('m')
        ->where('LOWER(m.statut) = :finished')
        ->setParameter('finished', 'finished')
        ->orderBy('m.dateMatch', 'DESC')
        ->setMaxResults(4)
        ->getQuery()
        ->getResult();

    $blogs = $entityManager->getRepository(Blog::class)->findBy([], ['createdAt' => 'DESC'], 3);

    $counters = [
        'tournois' => $entityManager->getRepository(Tournoi::class)->count([]),
        'equipes' => $entityManager->getRepository(Equipe::class)->count([]),
        'joueurs' => $entityManager->getRepository(User::class)->count([]),
        'cagnotte' => (float) $entityManager->getRepository(Tournoi::class)->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.cagnotte), 0)')
            ->getQuery()
            ->getSingleScalarResult(),
    ];

    return $this->render('home/index.html.twig', [
        'controller_name' => 'HomeController',
        'ranking' => $ranking,
        'topSales' => $topSales,
        'tournois' => $tournois,
        'jeux' => $jeux,
        'recentMatches' => $recentMatches,
        'blogs' => $blogs,
        'counters' => $counters,
    ]);
}

    private function computeGlobalRanking(EntityManagerInterface $entityManager): array
    {
        $allTeams = $entityManager->getRepository(Equipe::class)->findAll();
        $finishedMatches = $entityManager->getRepository(MatchGame::class)->createQueryBuilder('m')
            ->where('m.statut = :status')
            ->setParameter('status', 'Finished')
            ->getQuery()
            ->getResult();

        /** @var array<int|null, array{equipe: Equipe, mj: int, v: int, n: int, p: int, bp: int, bc: int, pts?: int, diff?: int, ppm?: float, badge?: string}> $stats */
        $stats = [];
        foreach ($allTeams as $team) {
            $id = $team->getId();
            $stats[$id] = [
                'equipe' => $team,
                'mj' => 0, 'v' => 0, 'n' => 0, 'p' => 0,
                'bp' => 0, 'bc' => 0,
            ];
        }

        foreach ($finishedMatches as $m) {
            $eq1 = $m->getEquipe1();
            $eq2 = $m->getEquipe2();
            if (!$eq1 || !$eq2) continue;

            $id1 = $eq1->getId();
            $id2 = $eq2->getId();
            $s1 = $m->getScoreTeam1() ?? 0;
            $s2 = $m->getScoreTeam2() ?? 0;

            if (!isset($stats[$id1])) {
                $stats[$id1] = ['equipe' => $eq1, 'mj' => 0, 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0, 'diff' => 0, 'ppm' => 0.0, 'badge' => 'Beginner'];
            }
            if (!isset($stats[$id2])) {
                $stats[$id2] = ['equipe' => $eq2, 'mj' => 0, 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0, 'diff' => 0, 'ppm' => 0.0, 'badge' => 'Beginner'];
            }

            $stats[$id1]['mj']++;
            $stats[$id2]['mj']++;
            $stats[$id1]['bp'] += $s1;
            $stats[$id1]['bc'] += $s2;
            $stats[$id2]['bp'] += $s2;
            $stats[$id2]['bc'] += $s1;

            if ($s1 > $s2) {
                $stats[$id1]['v']++;
                $stats[$id2]['p']++;
            } elseif ($s1 < $s2) {
                $stats[$id2]['v']++;
                $stats[$id1]['p']++;
            } else {
                $stats[$id1]['n']++;
                $stats[$id2]['n']++;
            }
        }

        foreach ($stats as &$s) {
            $s['pts'] = $s['v'] * 3 + $s['n'];
            $s['diff'] = $s['bp'] - $s['bc'];
            $s['ppm'] = $s['mj'] > 0 ? round($s['pts'] / $s['mj'], 2) : 0;

            if ($s['ppm'] >= 2.5) {
                $s['badge'] = 'Elite';
            } elseif ($s['ppm'] >= 1.5) {
                $s['badge'] = 'Competitive';
            } else {
                $s['badge'] = 'Beginner';
            }
        }
        unset($s);

        uasort($stats, static function ($a, $b) {
            $cmp = $b['ppm'] <=> $a['ppm'];
            if ($cmp !== 0) return $cmp;
            $cmp = $b['diff'] <=> $a['diff'];
            if ($cmp !== 0) return $cmp;
            $cmp = $b['bp'] <=> $a['bp'];
            if ($cmp !== 0) return $cmp;
            return $b['v'] <=> $a['v'];
        });

        $result = [];
        $rang = 1;
        foreach ($stats as $s) {
            $s['rang'] = $rang++;
            $result[] = $s;
        }

        return $result;
    }
}
