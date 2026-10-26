<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Form\TournoiType;
use App\Repository\TournoiRepository;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tournoi')]
final class TournoiController extends AbstractController
{
    #[Route(name: 'app_tournoi_index', methods: ['GET'])]
    public function index(TournoiRepository $tournoiRepository): Response
    {
        $tournois = $tournoiRepository->findAllWithEquipes();
        $equipesParTournoi = [];
        foreach ($tournois as $t) {
            $equipesParTournoi[$t->getId()] = $tournoiRepository->getEquipesInscrites($t);
        }

        return $this->render('tournoi/index.html.twig', [
            'tournois' => $tournois,
            'equipesParTournoi' => $equipesParTournoi,
        ]);
    }

    #[Route('/new', name: 'app_tournoi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tournoi = (new Tournoi())->applyDefaultSchedule();
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tournoi);
            $entityManager->flush();

            return $this->redirectToRoute('app_tournoi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournoi/new.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tournoi_show', methods: ['GET'])]
    public function show(int $id, Request $request, TournoiRepository $tournoiRepository, EquipeRepository $equipeRepository): Response
    {
        $tournoi = $tournoiRepository->findOneWithJeu($id);
        
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournament not found');
        }

        $equipesInscrites = $tournoiRepository->getEquipesInscrites($tournoi);
        $equipes = [];
        $inscrire = false;

        if ($request->query->getInt('inscrire', 0) === 1 && $this->getUser()) {
            $equipes = $equipeRepository->findEligibleForUser($this->getUser());
            $inscrire = !empty($equipes);
        }

        return $this->render('tournoi/show.html.twig', [
            'tournoi' => $tournoi,
            'equipesInscrites' => $equipesInscrites,
            'equipes' => $equipes,
            'inscrire' => $inscrire,
        ]);
    }

    #[Route('/{id}/paiement', name: 'app_tournoi_paiement', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function paiement(int $id, Request $request, TournoiRepository $tournoiRepository, EquipeRepository $equipeRepository): Response
    {
        $tournoi = $tournoiRepository->find($id);
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi introuvable.');
        }
        $equipeId = $request->query->getInt('equipe_id', 0);
        $equipe = $equipeRepository->find($equipeId);
        $user = $this->getUser();
        if (!$equipe || ($equipe->getOwner() !== $user && !$equipe->getMembers()->contains($user))) {
            $this->addFlash('error', 'Équipe non valide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }
        return $this->render('tournoi/paiement.html.twig', [
            'tournoi' => $tournoi,
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id}/paiement/process', name: 'app_tournoi_paiement_process', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function paiementProcess(int $id, Request $request, TournoiRepository $tournoiRepository, EquipeRepository $equipeRepository, EntityManagerInterface $entityManager): Response
    {
        $tournoi = $tournoiRepository->find($id);
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi introuvable.');
        }
        if (!$this->isCsrfTokenValid('paiement_tournoi', $request->request->get('token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }
        $equipeId = $request->request->getInt('equipe_id', 0);
        $equipe = $equipeRepository->find($equipeId);
        $user = $this->getUser();
        if (!$equipe || ($equipe->getOwner() !== $user && !$equipe->getMembers()->contains($user))) {
            $this->addFlash('error', 'Équipe non valide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }
        if ($tournoi->getEquipes()->contains($equipe)) {
            $this->addFlash('warning', 'Votre équipe est déjà inscrite à ce tournoi.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }
        // TODO: Intégrer Stripe/PayPal - pour l'instant on valide le paiement et on inscrit
        $tournoi->addEquipe($equipe);
        $entityManager->flush();
        try {
            $conn = $entityManager->getConnection();
            $conn->insert('inscription_tournoi', [
                'tournoi_id' => $tournoi->getId(),
                'equipe_id' => $equipe->getId(),
                'date_inscription' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('warning', 'Inscription enregistrée (problème secondaire : ' . $e->getMessage() . ')');
        }
        $this->addFlash('success', 'Paiement effectué ! Inscription au tournoi réussie.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/register', name: 'app_tournoi_register', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function register(int $id, TournoiRepository $tournoiRepository, EquipeRepository $equipeRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $tournoi = $tournoiRepository->find($id);
        
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        // Normaliser le statut pour décision côté serveur
        $rawStatut = (string) $tournoi->getStatut();
        $slug = strtolower($rawStatut);
        // translit accents to ascii
        $slug = @iconv('UTF-8', 'ASCII//TRANSLIT', $slug) ?: $slug;
        $slug = preg_replace('/\s+/', '_', $slug);
        $slug = preg_replace('/[^a-z0-9_]/', '', $slug);

        // Interdire l'inscription si le tournoi est en cours ou terminé
        if (in_array($slug, ['en_cours', 'encours', 'termine', 'terminé', 'termene', 'termine'])) {
            $this->addFlash('warning', 'Les inscriptions sont fermées pour ce tournoi (statut : ' . $tournoi->getStatut() . ').');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $user = $this->getUser();
        $equipes = $equipeRepository->findEligibleForUser($user);

        if ($request->isMethod('POST')) {
            $equipeId = $request->get('equipe_id');
            $token = $request->get('token');

            // Validate CSRF token
            if (!$this->isCsrfTokenValid('register_tournament', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            if (!$equipeId) {
                $this->addFlash('error', 'Veuillez sélectionner une équipe.');
                return $this->redirectToRoute('app_tournoi_register', ['id' => $id]);
            }

            $equipe = $equipeRepository->find($equipeId);

            $canRegister = $equipe && ($equipe->getOwner() === $user || $equipe->getMembers()->contains($user));
            if (!$canRegister) {
                $this->addFlash('error', 'Équipe non valide.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            if ($tournoi->getEquipes()->contains($equipe)) {
                $this->addFlash('warning', 'Votre équipe est déjà inscrite à ce tournoi.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            $fraisInscription = $tournoi->getFraisInscription();
            if ($fraisInscription === null || (float) $fraisInscription <= 0) {
                // Gratuit : inscription automatique sans passer par le paiement
                $tournoi->addEquipe($equipe);
                $entityManager->flush();
                try {
                    $conn = $entityManager->getConnection();
                    $conn->insert('inscription_tournoi', [
                        'tournoi_id' => $tournoi->getId(),
                        'equipe_id' => $equipe->getId(),
                        'date_inscription' => (new \DateTime())->format('Y-m-d H:i:s'),
                    ]);
                } catch (\Throwable $e) {
                    $this->addFlash('warning', 'Inscription enregistrée (problème secondaire : ' . $e->getMessage() . ')');
                }
                $this->addFlash('success', 'Inscription au tournoi effectuée avec succès !');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
            }

            // Frais > 0 : redirection vers la page de paiement
            return $this->redirectToRoute('app_tournoi_paiement', [
                'id' => $id,
                'equipe_id' => $equipe->getId(),
            ]);
        }

        $equipesRejoignables = $tournoiRepository->getEquipesRejoignables($tournoi, $user);

        return $this->render('tournoi/register.html.twig', [
            'tournoi' => $tournoi,
            'equipes' => $equipes,
            'equipesRejoignables' => $equipesRejoignables,
        ]);
    }

    #[Route('/{id}/rejoindre-equipe', name: 'app_tournoi_rejoindre_equipe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function rejoindreEquipe(int $id, Request $request, TournoiRepository $tournoiRepository, EquipeRepository $equipeRepository, EntityManagerInterface $entityManager): Response
    {
        $tournoi = $tournoiRepository->find($id);
        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi introuvable.');
        }
        if (!$this->isCsrfTokenValid('rejoindre_equipe_tournoi', $request->request->get('token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_tournoi_register', ['id' => $id]);
        }
        $equipeId = $request->request->getInt('equipe_id', 0);
        $equipe = $equipeRepository->find($equipeId);
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est authentifié et du bon type
        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('error', 'Vous devez être authentifié pour rejoindre une équipe.');
            return $this->redirectToRoute('app_login');
        }
        
        if (!$equipe) {
            $this->addFlash('error', 'Équipe introuvable.');
            return $this->redirectToRoute('app_tournoi_register', ['id' => $id]);
        }
        if ($equipe->getOwner() === $user || $equipe->getMembers()->contains($user)) {
            $this->addFlash('info', 'Vous faites déjà partie de cette équipe.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }
        if (!$tournoi->getEquipes()->contains($equipe)) {
            $this->addFlash('error', 'Cette équipe n\'est pas inscrite à ce tournoi.');
            return $this->redirectToRoute('app_tournoi_register', ['id' => $id]);
        }
        if ($equipe->getMembers()->count() >= $equipe->getMaxMembers()) {
            $this->addFlash('error', 'Cette équipe est complète.');
            return $this->redirectToRoute('app_tournoi_register', ['id' => $id]);
        }
        try {
            $equipe->addMember($user);
            $entityManager->flush();
            $this->addFlash('success', 'Vous avez rejoint l\'équipe ' . $equipe->getNom() . ' ! Vous participez maintenant au tournoi.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/{id}/edit', name: 'app_tournoi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tournoi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournoi/edit.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tournoi_delete', methods: ['POST'])]
    public function delete(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournoi->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tournoi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tournoi_index', [], Response::HTTP_SEE_OTHER);
    }
}
