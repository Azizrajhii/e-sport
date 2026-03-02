<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Entity\User;
use App\Entity\Tournoi;
use App\Entity\MatchGame;
use App\Entity\InscriptionTournoi;
use App\Entity\JoinRequest;
use App\Form\Equipe1Type;
use App\Repository\MatchGameRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Notification\JoinRequestNotification;

#[Route('/equipe')]
#[IsGranted('ROLE_USER')]
final class EquipeController extends AbstractController
{
    #[Route(name: 'app_equipe_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $ranking = $this->computeGlobalRanking($entityManager);

        return $this->render('equipe/index.html.twig', [
            'ranking' => $ranking,
        ]);
    }

      #[Route('/equipes', name: 'app_equipe_list', methods: ['GET'])]
    public function get(EntityManagerInterface $entityManager): Response
    {
        $equipes = $entityManager->getRepository(Equipe::class)->findAll();

        $ranking = $this->computeGlobalRanking($entityManager);
        $rankingByTeamId = [];
        foreach ($ranking as $r) {
            $rankingByTeamId[$r['equipe']->getId()] = $r;
        }

        return $this->render('equipe/afficher.html.twig', [
            'equipes' => $equipes,
            'rankingByTeamId' => $rankingByTeamId,
        ]);
    }


    #[Route('/new', name: 'app_equipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $equipe = new Equipe();
        $user = $this->getUser();
        if ($user instanceof User) {
            $equipe->setOwner($user);
            $equipe->addMember($user);
        }

        $form = $this->createForm(Equipe1Type::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                try {
                    $logoFile->move(
                        $this->getParameter('teams_directory'), // Ensure this parameter exists in services.yaml or similar
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $equipe->setLogo($newFilename);
            }

            $entityManager->persist($equipe);
            $entityManager->flush();

            $returnToTournoi = $request->query->getInt('return_to_tournoi', 0) ?: $request->request->getInt('return_to_tournoi', 0);
            if ($returnToTournoi > 0) {
                return $this->redirectToRoute('app_tournoi_register', ['id' => $returnToTournoi]);
            }

            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }

        $returnToTournoi = $request->query->getInt('return_to_tournoi', 0);

        return $this->render('equipe/new.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
            'return_to_tournoi' => $returnToTournoi ?: null,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_equipe_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        return $this->render('equipe/show.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_equipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        $form = $this->createForm(Equipe1Type::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             /** @var UploadedFile|null $logoFile */
             $logoFile = $form->get('logo')->getData();

             if ($logoFile) {
                 $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                 $safeFilename = $slugger->slug($originalFilename);
                 $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();
 
                 try {
                     $logoFile->move(
                         $this->getParameter('teams_directory'),
                         $newFilename
                     );
                 } catch (FileException $e) {
                     // ... handle exception
                 }
                 $equipe->setLogo($newFilename);
             }

            $entityManager->flush();

            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }

        return $this->render('equipe/edit.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_equipe_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, MatchGameRepository $matchGameRepository): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $request->request->get('_token'))) {
            foreach ($matchGameRepository->findByEquipe($equipe) as $matchGame) {
                $entityManager->remove($matchGame);
            }
            $inscriptions = $entityManager->getRepository(InscriptionTournoi::class)->findBy(['equipe' => $equipe]);
            foreach ($inscriptions as $inscription) {
                $entityManager->remove($inscription);
            }
            $equipe->getTournois()->clear();
            $entityManager->flush();
            $entityManager->remove($equipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_equipe_index');
    }

    #[Route('/{id<\d+>}/membres', name: 'app_equipe_membres', methods: ['GET','POST'])]
    public function invite(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
    
        // Tous les users sauf admins et le owner de cette équipe
        $allUsers = $entityManager->getRepository(User::class)->findAll();
        $users = array_filter($allUsers, function($u) use ($equipe) {
            $isAdmin = in_array('ROLE_ADMIN', $u->getRoles());
            $isOwner = ($u === $equipe->getOwner());
            return !$isAdmin && !$isOwner;
        });
    
        // IDs des membres déjà dans l'équipe
        $teamUsers = [];
        foreach ($equipe->getMembers() as $member) {
            $teamUsers[] = $member->getId();
        }
    
        // Ajout d'un joueur
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('user_id');
    
            if ($userId) {
                $user = $entityManager->getRepository(User::class)->find($userId);
    
                if ($user && !in_array($user->getId(), $teamUsers)) {
                    if ($equipe->getMembers()->count() < $equipe->getMaxMembers()) {
                        $equipe->addMember($user);
                        $entityManager->flush();
                    } 
                }
            }
    
            return $this->redirectToRoute('app_equipe_membres', ['id' => $id]);
        }
    
        // Pending join requests
        $pendingRequests = $entityManager->getRepository(JoinRequest::class)->findBy([
            'equipe' => $equipe,
            'status' => 'pending',
        ], ['createdAt' => 'DESC']);

        return $this->render('equipe/invite.html.twig', [
            'equipe'    => $equipe,
            'users'     => $users,
            'teamUsers' => $teamUsers,
            'isOwner'   => $this->getUser() === $equipe->getOwner(),
            'pendingRequests' => $pendingRequests,
        ]);
    }

    #[Route('/{id<\d+>}/remove-member', name: 'app_equipe_remove_member', methods: ['POST'])]
    public function removeMember(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        // Only owner can remove members
        if ($this->getUser() !== $equipe->getOwner()) {
            throw $this->createAccessDeniedException('You are not the owner of this team.');
        }

        $userId = $request->request->get('user_id');
        if ($userId) {
            $userToRemove = $entityManager->getRepository(User::class)->find($userId);
            if ($userToRemove && $equipe->getMembers()->contains($userToRemove)) {
                // Cannot remove owner
                if ($userToRemove === $equipe->getOwner()) {
                } else {
                    $equipe->removeMember($userToRemove);
                    $entityManager->flush();
                }
            }
        }

        return $this->redirectToRoute('app_equipe_membres', ['id' => $id]);
    }

  #[Route('/my-teams', name: 'app_my_teams', methods: ['GET'])]
    public function myTeams(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $ownedTeams = $entityManager->getRepository(Equipe::class)->findBy(['owner' => $user]);

        $qb = $entityManager->createQueryBuilder();
        $qb->select('e')
            ->from(Equipe::class, 'e')
            ->join('e.members', 'm')
            ->where('m = :user')
            ->andWhere('e.owner != :user')
            ->setParameter('user', $user);
        $joinedTeams = $qb->getQuery()->getResult();

        // Calculate Global Win Rate
        $allTeams = array_unique(array_merge($ownedTeams, $joinedTeams), SORT_REGULAR);
        $teamIds = array_map(fn($t) => $t->getId(), $allTeams);

        $totalWins = 0;
        $totalEndedMatches = 0;

        if (!empty($teamIds)) {
            $matches = $entityManager->getRepository(MatchGame::class)->createQueryBuilder('m')
                ->where('m.equipe1 IN (:teamIds) OR m.equipe2 IN (:teamIds)')
                ->andWhere('m.statut = :status')
                ->setParameter('teamIds', $teamIds)
                ->setParameter('status', 'Finished')
                ->getQuery()
                ->getResult();

            foreach ($matches as $match) {
                $totalEndedMatches++;
                $e1 = $match->getEquipe1();
                $e2 = $match->getEquipe2();
                $s1 = $match->getScoreTeam1();
                $s2 = $match->getScoreTeam2();

                // Check if one of user's teams won
                if (in_array($e1->getId(), $teamIds) && $s1 > $s2) {
                    $totalWins++;
                } elseif (in_array($e2->getId(), $teamIds) && $s2 > $s1) {
                    $totalWins++;
                }
            }
        }

        $globalWinRate = $totalEndedMatches > 0 ? round(($totalWins / $totalEndedMatches) * 100) : 0;
        $total = count($ownedTeams) + count($joinedTeams);

        return $this->render('equipe/my-teams.html.twig', [
            'ownedTeams' => $ownedTeams,
            'joinedTeams' => $joinedTeams,
            'totalTeams' => $total,
            'globalWinRate' => $globalWinRate,
        ]);
    }
    
    #[Route('/{id<\d+>}/dashboard', name: 'app_equipe_dashboard', methods: ['GET'])]
    public function dashboard(int $id, EntityManagerInterface $entityManager, ChartBuilderInterface $chartBuilder): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);

        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        $user = $this->getUser();
        
        // Membership Logic for Template
        $membership = ['role' => 'VISITOR'];
        if ($user) {
            if ($equipe->getOwner() === $user) {
                $membership = ['role' => 'LEADER'];
            } elseif ($equipe->getMembers()->contains($user)) {
                $membership = ['role' => 'MEMBER'];
            }
        }
        
        // --- DYNAMIC STATS ---
        
        // 1. Matches Played (only 'TERMINE')
        // Actually, dashboard might want ALL matches involving this team, or just ended ones?
        // Usually, dashboard shows total matches played (ended).
        
        $matches = $entityManager->getRepository(MatchGame::class)->createQueryBuilder('m')
            ->where('m.equipe1 = :team OR m.equipe2 = :team')
            ->andWhere('m.statut = :status') // Count only finished matches for stats
            ->setParameter('team', $equipe)
            ->setParameter('status', 'Finished')
            ->getQuery()
            ->getResult();
            
        $totalMatches = count($matches);
        $wins = 0;
        $goalsFor = 0;
        $goalsAgainst = 0;
        
        foreach ($matches as $match) {
            $s1 = $match->getScoreTeam1() ?? 0;
            $s2 = $match->getScoreTeam2() ?? 0;

            if ($match->getEquipe1() === $equipe) {
                $goalsFor += $s1;
                $goalsAgainst += $s2;
                if ($s1 > $s2) {
                    $wins++;
                }
            } elseif ($match->getEquipe2() === $equipe) {
                $goalsFor += $s2;
                $goalsAgainst += $s1;
                if ($s2 > $s1) {
                    $wins++;
                }
            }
        }
        
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100) : 0;
        $goalDifference = $goalsFor - $goalsAgainst;
        $goalAverage = $totalMatches > 0 ? round($goalsFor / $totalMatches, 1) : 0;

        // Get team rank from global ranking
        $ranking = $this->computeGlobalRanking($entityManager);
        $teamRank = null;
        foreach ($ranking as $r) {
            if ($r['equipe']->getId() === $equipe->getId()) {
                $teamRank = (object)$r;
                break;
            }
        }

        // --- CHARTS ---
        $losses = 0;
        $draws = 0;
        foreach ($matches as $match) {
            $s1 = $match->getScoreTeam1() ?? 0;
            $s2 = $match->getScoreTeam2() ?? 0;
            if ($match->getEquipe1() === $equipe) {
                if ($s1 < $s2) { $losses++; } elseif ($s1 === $s2) { $draws++; }
            } elseif ($match->getEquipe2() === $equipe) {
                if ($s2 < $s1) { $losses++; } elseif ($s1 === $s2) { $draws++; }
            }
        }

        // Results Chart (Doughnut: Wins / Losses / Draws)
        $resultsChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $resultsChart->setData([
            'labels' => ['Wins', 'Losses', 'Draws'],
            'datasets' => [[
                'data' => [$wins, $losses, $draws],
                'backgroundColor' => ['#22c55e', '#ef4444', '#6b7280'],
            ]],
        ]);
        $resultsChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['labels' => ['color' => '#fff']]],
        ]);

        // Goals Chart (Bar: Goals For vs Goals Against)
        $goalsChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $goalsChart->setData([
            'labels' => ['Goals For', 'Goals Against'],
            'datasets' => [[
                'label' => 'Goals',
                'data' => [$goalsFor, $goalsAgainst],
                'backgroundColor' => ['#3b82f6', '#ef4444'],
            ]],
        ]);
        $goalsChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => ['y' => ['ticks' => ['color' => '#fff'], 'grid' => ['color' => 'rgba(255,255,255,0.1)']], 'x' => ['ticks' => ['color' => '#fff'], 'grid' => ['color' => 'rgba(255,255,255,0.1)']]],
            'plugins' => ['legend' => ['labels' => ['color' => '#fff']]],
        ]);

        // Pending join requests (for owner)
        $pendingRequests = [];
        if ($membership['role'] === 'LEADER') {
            $pendingRequests = $entityManager->getRepository(JoinRequest::class)->findBy([
                'equipe' => $equipe,
                'status' => 'pending',
            ], ['createdAt' => 'DESC']);
        }

        return $this->render('equipe/dashboard.html.twig', [
            'team' => $equipe,
            'membership' => (object)$membership,
            'totalMatches' => $totalMatches,
            'wins' => $wins,
            'winRate' => $winRate,
            'goalsFor' => $goalsFor,
            'goalsAgainst' => $goalsAgainst,
            'goalAverage' => $goalAverage,
            'goalDifference' => $goalDifference,
            'teamRank' => $teamRank,
            'pendingRequests' => $pendingRequests,
            'resultsChart' => $resultsChart,
            'goalsChart' => $goalsChart,
        ]);
    }

    #[Route('/{id<\d+>}/join', name: 'app_equipe_join', methods: ['GET', 'POST'])]
    public function join(int $id, EntityManagerInterface $entityManager, NotifierInterface $notifier): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Already a member
        if ($equipe->getMembers()->contains($user)) {
            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $equipe->getId()]);
        }

        // Team is full
        if ($equipe->getMembers()->count() >= $equipe->getMaxMembers()) {
            return $this->redirectToRoute('app_equipe_discover');
        }

        // Check for existing pending request
        $existingRequest = $entityManager->getRepository(JoinRequest::class)->findOneBy([
            'equipe' => $equipe,
            'user' => $user,
            'status' => 'pending',
        ]);

        if ($existingRequest) {
            return $this->redirectToRoute('app_equipe_discover');
        }

        // Create pending join request
        $joinRequest = new JoinRequest();
        $joinRequest->setEquipe($equipe);
        $joinRequest->setUser($user);
        $joinRequest->setStatus('pending');
        $joinRequest->setCreatedBy($user);

        $entityManager->persist($joinRequest);
        $entityManager->flush();

        // Envoyer la notification email au owner de l'équipe
        $owner = $equipe->getOwner();
        if ($owner && $owner->getEmail()) {
            try {
                $notification = new JoinRequestNotification(
                    $user->getNom() ?: $user->getEmail(),
                    $equipe->getNom(),
                    $this->getParameter('kernel.project_dir')
                );
                $notifier->send($notification, new Recipient($owner->getEmail()));
            } catch (\Exception $e) {
                // Ne pas bloquer si l'email échoue
            }
        }

        return $this->redirectToRoute('app_equipe_discover');
    }

    #[Route('/{id<\d+>}/join-request/{requestId<\d+>}/{action}', name: 'app_equipe_handle_join_request', methods: ['POST'])]
    public function handleJoinRequest(int $id, int $requestId, string $action, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }

        // Only the owner can handle join requests
        if ($this->getUser() !== $equipe->getOwner()) {
            throw $this->createAccessDeniedException('Seul le propriétaire peut gérer les demandes.');
        }

        $joinRequest = $entityManager->getRepository(JoinRequest::class)->find($requestId);
        if (!$joinRequest || $joinRequest->getEquipe() !== $equipe || $joinRequest->getStatus() !== 'pending') {
            return $this->redirectToRoute('app_equipe_dashboard', ['id' => $id]);
        }

        if ($action === 'accept') {
            if ($equipe->getMembers()->count() >= $equipe->getMaxMembers()) {
            } else {
                $equipe->addMember($joinRequest->getUser());
                $joinRequest->setStatus('accepted');
                $joinRequest->setUpdatedBy($this->getUser());
            }
        } elseif ($action === 'reject') {
            $joinRequest->setStatus('rejected');
            $joinRequest->setUpdatedBy($this->getUser());
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_equipe_dashboard', ['id' => $id]);
    }
    
    #[Route('/{id<\d+>}/leave', name: 'app_equipe_leave', methods: ['POST'])]
    public function leave(int $id, EntityManagerInterface $entityManager): Response
    {
        $equipe = $entityManager->getRepository(Equipe::class)->find($id);
        if (!$equipe) {
            return $this->redirectToRoute('app_equipe_index');
        }
        $user = $this->getUser();
        if ($equipe->getMembers()->contains($user) && $user instanceof User) {
            $equipe->removeMember($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_equipe_index');
    }
#[Route('/discover', name: 'app_equipe_discover', methods: ['GET'])]
    public function notMember(EntityManagerInterface $entityManager): Response 
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_equipe_index');
        }

        $equipes = $entityManager->getRepository(Equipe::class)->findAll();
        $userTeamIds = [];
        foreach ($equipes as $team) {
            if ($team->getOwner() === $user || $team->getMembers()->contains($user)) {
                $userTeamIds[] = $team->getId();
            }
        }

        // Get team IDs where user has a pending join request
        $pendingRequests = $entityManager->getRepository(JoinRequest::class)->findBy([
            'user' => $user,
            'status' => 'pending',
        ]);
        $pendingTeamIds = [];
        foreach ($pendingRequests as $pr) {
            $pendingTeamIds[] = $pr->getEquipe()->getId();
        }

        $ranking = $this->computeGlobalRanking($entityManager);
        // Build a lookup: teamId -> ranking entry
        $rankingByTeamId = [];
        foreach ($ranking as $r) {
            $rankingByTeamId[$r['equipe']->getId()] = $r;
        }
        
        return $this->render('equipe/afficher.html.twig', [
            'equipes' => $equipes,
            'user_team_ids' => $userTeamIds,
            'pendingTeamIds' => $pendingTeamIds,
            'rankingByTeamId' => $rankingByTeamId,
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

        // Calculate derived stats
        foreach ($stats as &$s) {
            $s['pts'] = $s['v'] * 3 + $s['n'];
            $s['diff'] = $s['bp'] - $s['bc'];
            $s['ppm'] = $s['mj'] > 0 ? round($s['pts'] / $s['mj'], 2) : 0;

            // Badge
            if ($s['ppm'] >= 2.5) {
                $s['badge'] = 'Elite';
            } elseif ($s['ppm'] >= 1.5) {
                $s['badge'] = 'Competitive';
            } else {
                $s['badge'] = 'Beginner';
            }
        }
        unset($s);

        // Sort: PPM desc, diff desc, bp desc, wins desc
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
