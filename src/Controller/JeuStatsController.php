<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use App\Repository\TournoiRepository;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/jeu-stats')]
class JeuStatsController extends AbstractController
{
    #[Route('/dropdown', name: 'app_jeu_stats_dropdown', methods: ['GET'])]
    public function dropdown(
        JeuRepository $jeuRepository,
        TournoiRepository $tournoiRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $jeux = $jeuRepository->findAll();
        $statsParJeu = [];

        foreach ($jeux as $jeu) {
            $tournois = $jeu->getTournois();
            $nbTournois = $tournois->count();
            
            // Compter les équipes uniques
            $equipesIds = [];
            $cagnotteTotal = 0;
            $nbMatchs = 0;
            
            foreach ($tournois as $tournoi) {
                // Équipes
                foreach ($tournoi->getEquipes() as $equipe) {
                    $equipesIds[$equipe->getId()] = true;
                }
                
                // Cagnotte
                if ($tournoi->getCagnotte()) {
                    $cagnotteTotal += (float) $tournoi->getCagnotte();
                }
                
                // Matchs
                $matchs = $entityManager->getRepository(\App\Entity\MatchGame::class)
                    ->createQueryBuilder('m')
                    ->where('m.tournoi = :tournoi')
                    ->setParameter('tournoi', $tournoi)
                    ->getQuery()
                    ->getResult();
                $nbMatchs += count($matchs);
            }
            
            $nbEquipes = count($equipesIds);
            
            // Calculer le taux de participation (moyenne du taux de remplissage des tournois)
            $tauxParticipation = 0;
            $tournoiAvecMax = 0;
            foreach ($tournois as $tournoi) {
                if ($tournoi->getMaxParticipants() && $tournoi->getMaxParticipants() > 0) {
                    $tauxParticipation += ($tournoi->getEquipes()->count() / $tournoi->getMaxParticipants()) * 100;
                    $tournoiAvecMax++;
                }
            }
            if ($tournoiAvecMax > 0) {
                $tauxParticipation = round($tauxParticipation / $tournoiAvecMax);
            }
            
            $statsParJeu[] = [
                'jeu' => $jeu,
                'nbTournois' => $nbTournois,
                'nbEquipes' => $nbEquipes,
                'nbMatchs' => $nbMatchs,
                'cagnotteTotal' => $cagnotteTotal,
                'tauxParticipation' => $tauxParticipation,
            ];
        }

        return $this->render('jeu_stats/dropdown.html.twig', [
            'statsParJeu' => $statsParJeu,
        ]);
    }

    #[Route('/page', name: 'app_jeu_stats_page', methods: ['GET'])]
    public function statsPage(
        JeuRepository $jeuRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $jeux = $jeuRepository->findAll();
        $statsParJeu = [];

        foreach ($jeux as $jeu) {
            $tournois = $jeu->getTournois();
            $nbTournois = $tournois->count();
            
            // Compter les équipes uniques
            $equipesIds = [];
            $cagnotteTotal = 0;
            $nbMatchs = 0;
            
            foreach ($tournois as $tournoi) {
                // Équipes
                foreach ($tournoi->getEquipes() as $equipe) {
                    $equipesIds[$equipe->getId()] = true;
                }
                
                // Cagnotte
                if ($tournoi->getCagnotte()) {
                    $cagnotteTotal += (float) $tournoi->getCagnotte();
                }
                
                // Matchs
                $matchs = $entityManager->getRepository(\App\Entity\MatchGame::class)
                    ->createQueryBuilder('m')
                    ->where('m.tournoi = :tournoi')
                    ->setParameter('tournoi', $tournoi)
                    ->getQuery()
                    ->getResult();
                $nbMatchs += count($matchs);
            }
            
            $nbEquipes = count($equipesIds);
            
            // Calculer le taux de participation
            $tauxParticipation = 0;
            $tournoiAvecMax = 0;
            foreach ($tournois as $tournoi) {
                if ($tournoi->getMaxParticipants() && $tournoi->getMaxParticipants() > 0) {
                    $tauxParticipation += ($tournoi->getEquipes()->count() / $tournoi->getMaxParticipants()) * 100;
                    $tournoiAvecMax++;
                }
            }
            if ($tournoiAvecMax > 0) {
                $tauxParticipation = round($tauxParticipation / $tournoiAvecMax);
            }
            
            // Calculer la note moyenne (simulation - à adapter selon votre système de notation)
            $noteMoyenne = 4.5; // Valeur par défaut
            
            $statsParJeu[] = [
                'jeu' => $jeu,
                'nbTournois' => $nbTournois,
                'nbEquipes' => $nbEquipes,
                'nbMatchs' => $nbMatchs,
                'cagnotteTotal' => $cagnotteTotal,
                'tauxParticipation' => $tauxParticipation,
                'noteMoyenne' => $noteMoyenne,
            ];
        }

        return $this->render('jeu_stats/page.html.twig', [
            'statsParJeu' => $statsParJeu,
        ]);
    }
}
