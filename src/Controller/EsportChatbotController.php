<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Repository\MatchGameRepository;
use App\Repository\TournoiRepository;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Chatbot IA contextuel pour les pages Équipe et Tournoi.
 * Fichier séparé de ChatbotController (boutique produits).
 */
class EsportChatbotController extends AbstractController
{
    // =========================================================
    //  Chatbot — Équipe
    //  POST /chatbot/equipe/{id}
    // =========================================================

    #[Route('/chatbot/equipe/{id}', name: 'chatbot_equipe', methods: ['POST'])]
    public function chatEquipe(
        int $id,
        Request $request,
        GeminiService $gemini,
        SessionInterface $session,
        EntityManagerInterface $em,
        MatchGameRepository $matchGameRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $equipe = $em->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->json(['error' => 'Équipe introuvable'], 404);
        }

        // Membres
        $membres = array_map(
            fn($m) => $m->getNom(),
            $equipe->getMembers()->toArray()
        );

        // 10 derniers matchs
        $matches = $matchGameRepository->createQueryBuilder('m')
            ->where('m.equipe1 = :eq OR m.equipe2 = :eq')
            ->setParameter('eq', $equipe)
            ->orderBy('m.dateMatch', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $wins = 0;
        $losses = 0;
        $draws = 0;
        $matchLines = '';

        foreach ($matches as $m) {
            $s1 = $m->getScoreTeam1() ?? 0;
            $s2 = $m->getScoreTeam2() ?? 0;
            $isTeam1 = $m->getEquipe1()?->getId() === $equipe->getId();
            $myScore  = $isTeam1 ? $s1 : $s2;
            $oppScore = $isTeam1 ? $s2 : $s1;
            $opp      = ($isTeam1 ? $m->getEquipe2() : $m->getEquipe1())?->getNom() ?? '?';
            $statut   = strtolower((string) $m->getStatut());

            $result = match (true) {
                $statut === 'finished' && $myScore > $oppScore => 'Victoire',
                $statut === 'finished' && $myScore < $oppScore => 'Défaite',
                $statut === 'finished'                         => 'Nul',
                default                                        => ucfirst($statut),
            };

            if ($statut === 'finished') {
                if ($myScore > $oppScore) {
                    $wins++;
                } elseif ($myScore < $oppScore) {
                    $losses++;
                } else {
                    $draws++;
                }
            }

            $date = $m->getDateMatch()?->format('d/m/Y') ?? '?';
            $matchLines .= "- {$date} vs {$opp} : {$myScore}-{$oppScore} ({$result})\n";
        }

        $totalFinished = $wins + $losses + $draws;
        $winRate = $totalFinished > 0 ? round($wins / $totalFinished * 100) : 0;

        // Prompt système injecté comme contexte
        $systemPrompt =
            "Tu es un assistant IA expert en e-sport intégré dans la plateforme de gestion d'équipe.\n"
            . "Tu analyses uniquement les données de l'équipe suivante et tu réponds EN FRANÇAIS, de façon concise.\n\n"
            . "=== ÉQUIPE : {$equipe->getNom()} ===\n"
            . "Owner : " . ($equipe->getOwner()?->getNom() ?? $equipe->getOwner()?->getEmail() ?? '?') . "\n"
            . "Membres (" . count($membres) . '/' . $equipe->getMaxMembers() . ') : ' . implode(', ', $membres) . "\n"
            . "Statistiques : {$wins}V / {$draws}N / {$losses}D | Win rate : {$winRate}%\n\n"
            . "Derniers matchs :\n" . ($matchLines ?: "Aucun match joué.\n") . "\n"
            . "Réponds toujours en 2-3 lignes maximum. Si on pose une question hors contexte équipe, redirige vers les stats.";

        $sessionKey = 'chat_equipe_' . $id;
        $history    = $session->get($sessionKey, []);
        $response   = $gemini->chat($userMessage, $history, '', $systemPrompt);

        $history[] = ['role' => 'user',  'text' => $userMessage];
        $history[] = ['role' => 'model', 'text' => $response];
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        $session->set($sessionKey, $history);

        return $this->json(['response' => $response]);
    }

    // =========================================================
    //  Chatbot — Tournoi
    //  POST /chatbot/tournoi/{tournoiId}
    // =========================================================

    #[Route('/chatbot/tournoi/{tournoiId}', name: 'chatbot_tournoi', methods: ['POST'])]
    public function chatTournoi(
        int $tournoiId,
        Request $request,
        GeminiService $gemini,
        SessionInterface $session,
        MatchGameRepository $matchGameRepository,
        TournoiRepository $tournoiRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $tournoi = $tournoiRepository->find($tournoiId);
        if (!$tournoi) {
            return $this->json(['error' => 'Tournoi introuvable'], 404);
        }

        $matches          = $matchGameRepository->findByTournoi($tournoi);
        $equipesInscrites = $tournoiRepository->getEquipesInscrites($tournoi);

        // Init stats
        $stats = [];
        foreach ($equipesInscrites as $eq) {
            $stats[$eq->getId()] = [
                'nom' => $eq->getNom(),
                'v'   => 0, 'n' => 0, 'p' => 0,
                'bp'  => 0, 'bc' => 0, 'pts' => 0,
            ];
        }

        $matchLines = '';
        foreach ($matches as $m) {
            $s1     = $m->getScoreTeam1() ?? 0;
            $s2     = $m->getScoreTeam2() ?? 0;
            $eq1    = $m->getEquipe1();
            $eq2    = $m->getEquipe2();
            $date   = $m->getDateMatch()?->format('d/m/Y H:i') ?? '?';
            $statut = ucfirst(strtolower((string) $m->getStatut()));
            $matchLines .= "- {$date} | {$eq1?->getNom()} {$s1}-{$s2} {$eq2?->getNom()} ({$statut})\n";

            if (strtolower((string) $m->getStatut()) === 'finished' && $eq1 && $eq2) {
                $id1 = $eq1->getId();
                $id2 = $eq2->getId();
                if (!isset($stats[$id1])) {
                    $stats[$id1] = ['nom' => $eq1->getNom(), 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0];
                }
                if (!isset($stats[$id2])) {
                    $stats[$id2] = ['nom' => $eq2->getNom(), 'v' => 0, 'n' => 0, 'p' => 0, 'bp' => 0, 'bc' => 0, 'pts' => 0];
                }
                $stats[$id1]['bp'] += $s1;
                $stats[$id1]['bc'] += $s2;
                $stats[$id2]['bp'] += $s2;
                $stats[$id2]['bc'] += $s1;
                if ($s1 > $s2) {
                    $stats[$id1]['v']++;
                    $stats[$id2]['p']++;
                } elseif ($s2 > $s1) {
                    $stats[$id2]['v']++;
                    $stats[$id1]['p']++;
                } else {
                    $stats[$id1]['n']++;
                    $stats[$id2]['n']++;
                }
            }
        }

        foreach ($stats as &$s) {
            $s['pts'] = $s['v'] * 3 + $s['n'];
        }
        unset($s);
        usort($stats, fn($a, $b) => $b['pts'] <=> $a['pts']);

        $classementLines = '';
        $rang = 1;
        foreach ($stats as $s) {
            $diff = $s['bp'] - $s['bc'];
            $classementLines .= "{$rang}. {$s['nom']} — {$s['pts']}pts"
                . " ({$s['v']}V/{$s['n']}N/{$s['p']}D | BP:{$s['bp']} BC:{$s['bc']} diff:{$diff})\n";
            $rang++;
        }

        $jeu = $tournoi->getJeu()?->getNom() ?? 'inconnu';

        $systemPrompt =
            "Tu es un assistant IA expert en e-sport intégré dans la page du tournoi.\n"
            . "Réponds EN FRANÇAIS, de façon concise (2-3 lignes max).\n\n"
            . "=== TOURNOI : {$tournoi->getNom()} ===\n"
            . "Jeu : {$jeu}\n"
            . "Équipes inscrites : " . count($equipesInscrites) . "\n\n"
            . "Classement actuel :\n" . ($classementLines ?: "Aucun match terminé.\n") . "\n"
            . "Matchs :\n" . ($matchLines ?: "Aucun match planifié.\n") . "\n"
            . "Propose des analyses, pronostics ou explique le classement. Réponds uniquement sur ce tournoi.";

        $sessionKey = 'chat_tournoi_' . $tournoiId;
        $history    = $session->get($sessionKey, []);
        $response   = $gemini->chat($userMessage, $history, '', $systemPrompt);

        $history[] = ['role' => 'user',  'text' => $userMessage];
        $history[] = ['role' => 'model', 'text' => $response];
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }
        $session->set($sessionKey, $history);

        return $this->json(['response' => $response]);
    }
}
