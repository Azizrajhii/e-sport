<?php

namespace App\Controller;

use App\Entity\MatchGame;
use App\Form\MatchGame1Type;
use App\Repository\MatchGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/match/game')]
final class MatchGameController extends AbstractController
{
    #[Route('/tournoi/{tournoiId}', name: 'app_match_game_by_tournoi', methods: ['GET'])]
    public function byTournoi(int $tournoiId, MatchGameRepository $matchGameRepository, \App\Repository\TournoiRepository $tournoiRepository): Response
    {
        $tournoi = $tournoiRepository->findOneWithJeu($tournoiId);
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi introuvable.');
        }
        $matchGames = $matchGameRepository->findByTournoi($tournoi);
        $equipesInscrites = $tournoiRepository->getEquipesInscrites($tournoi);
        $classement = $this->computeClassement($matchGames, $equipesInscrites);
        return $this->render('match_game/index.html.twig', [
            'match_games' => $matchGames,
            'tournoi' => $tournoi,
            'classement' => $classement,
        ]);
    }

    /**
     * Calcule le classement : toutes les équipes inscrites au tournoi, avec les stats des matchs terminés (ou 0).
     * @param array<MatchGame> $matchGames
     * @param array<\App\Entity\Equipe> $equipesInscrites
     * @return array<int, array{equipe: \App\Entity\Equipe, rang: int, mj: int, v: int, n: int, p: int, bp: int, bc: int, diff: int, pts: int}>
     */
    private function computeClassement(array $matchGames, array $equipesInscrites): array
    {
        /** @var array<int|null, array{equipe: \App\Entity\Equipe, mj: int, v: int, n: int, p: int, bp: int, bc: int, pts: int, diff: int}> $stats */
        $stats = [];
        foreach ($equipesInscrites as $equipe) {
            $id = $equipe->getId();
            $stats[$id] = ['equipe' => $equipe, 'mj' => 0, 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0, 'diff' => 0];
        }
        foreach ($matchGames as $m) {
            if (strtolower((string) $m->getStatut()) !== 'finished') {
                continue;
            }
            $eq1 = $m->getEquipe1();
            $eq2 = $m->getEquipe2();
            if (!$eq1 || !$eq2) {
                continue;
            }
            $id1 = $eq1->getId();
            $id2 = $eq2->getId();
            if (!isset($stats[$id1])) {
                $stats[$id1] = ['equipe' => $eq1, 'mj' => 0, 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0, 'diff' => 0];
            }
            if (!isset($stats[$id2])) {
                $stats[$id2] = ['equipe' => $eq2, 'mj' => 0, 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0, 'diff' => 0];
            }
            $s1 = $m->getScoreTeam1() ?? 0;
            $s2 = $m->getScoreTeam2() ?? 0;
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
        }
        unset($s);
        uasort($stats, static function ($a, $b) {
            $cmp = $b['pts'] <=> $a['pts'];
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = $b['diff'] <=> $a['diff'];
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcasecmp($a['equipe']->getNom(), $b['equipe']->getNom());
        });
        $result = [];
        $rang = 1;
        foreach ($stats as $s) {
            $s['rang'] = $rang++;
            $result[] = $s;
        }
        return $result;
    }

    #[Route("/getall" ,name: 'app_match_game_index', methods: ['GET'])]
    public function index(MatchGameRepository $matchGameRepository): Response
    {
        return $this->render('match_game/index.html.twig', [
            'match_games' => $matchGameRepository->findBy([], ['id' => 'DESC'], 200),
            'tournoi' => null,
            'classement' => [],
        ]);
    }

        #[Route("/dashboard" ,name: 'app_match_game_index2', methods: ['GET'])]
    public function index2(MatchGameRepository $matchGameRepository): Response
    {
        return $this->render('match_game/MatchDashbored.html.twig', [
            'match_games' => $matchGameRepository->findBy([], ['id' => 'DESC'], 200),
        ]);
    }

    #[Route('/new', name: 'app_match_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $matchGame = new MatchGame();
        $form = $this->createForm(MatchGame1Type::class, $matchGame);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$matchGame->getEquipe1() || !$matchGame->getEquipe2()) {
            } elseif (!$matchGame->getTournoi()) {
            } else {
                $entityManager->persist($matchGame);
                $entityManager->flush();
                return $this->redirectToRoute('app_match_game_index');
            }
        }

        return $this->render('match_game/new.html.twig', [
            'match_game' => $matchGame,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_match_game_show', methods: ['GET'])]
    public function show(MatchGame $matchGame): Response
    {
        return $this->render('match_game/show.html.twig', [
            'match_game' => $matchGame,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_match_game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MatchGame $matchGame, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MatchGame1Type::class, $matchGame);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$matchGame->getEquipe1() || !$matchGame->getEquipe2()) {
            } elseif (!$matchGame->getTournoi()) {
            } else {
                $entityManager->flush();
                return $this->redirectToRoute('app_match_game_index');
            }
        }

        return $this->render('match_game/edit.html.twig', [
            'match_game' => $matchGame,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_match_game_delete', methods: ['POST'])]
    public function delete(Request $request, MatchGame $matchGame, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$matchGame->getId(), $request->request->get('_token'))) {
            $entityManager->remove($matchGame);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_match_game_index');
    }
}
