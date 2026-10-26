<?php

namespace App\Controller\Admin;

use App\Entity\Tournoi;
use App\Form\TournoiType;
use App\Repository\TournoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/tournoi')]
class TournoiController extends AbstractController
{
    #[Route('/', name: 'admin_tournoi_index', methods: ['GET'])]
    public function index(TournoiRepository $tournoiRepository): Response
    {
        $tournois = $tournoiRepository->findBy([], ['date_debut' => 'DESC']);

        $stats = [
            'total' => \count($tournois),
            'en_attente' => 0,
            'en_cours' => 0,
            'termine' => 0,
        ];

        foreach ($tournois as $t) {
            $slug = $this->normalizeStatut($t->getStatut());
            if ($slug === 'en_attente') {
                $stats['en_attente']++;
            } elseif ($slug === 'en_cours') {
                $stats['en_cours']++;
            } elseif ($slug === 'termine') {
                $stats['termine']++;
            }
        }

        return $this->render('tournoi/admin/index.html.twig', [
            'tournois' => $tournois,
            'stats' => $stats,
        ]);
    }

    private function normalizeStatut(?string $statut): string
    {
        if ($statut === null || $statut === '') {
            return '';
        }
        $s = mb_strtolower($statut, 'UTF-8');
        if (str_contains($s, 'termin')) {
            return 'termine';
        }
        if (str_contains($s, 'cours')) {
            return 'en_cours';
        }
        if (str_contains($s, 'attente')) {
            return 'en_attente';
        }
        return $s;
    }

    #[Route('/new', name: 'admin_tournoi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tournoi = (new Tournoi())->applyDefaultSchedule();
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tournoi);
            $entityManager->flush();

            return $this->redirectToRoute('admin_tournoi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournoi/admin/new.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_tournoi_show', methods: ['GET'])]
    public function show(Tournoi $tournoi, TournoiRepository $tournoiRepository): Response
    {
        $equipesInscrites = $tournoiRepository->getEquipesInscrites($tournoi);

        return $this->render('tournoi/admin/show.html.twig', [
            'tournoi' => $tournoi,
            'equipesInscrites' => $equipesInscrites,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_tournoi_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TournoiType::class, $tournoi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_tournoi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tournoi/admin/edit.html.twig', [
            'tournoi' => $tournoi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_tournoi_delete', methods: ['POST'])]
    public function delete(Request $request, Tournoi $tournoi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tournoi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tournoi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_tournoi_index', [], Response::HTTP_SEE_OTHER);
    }
}
