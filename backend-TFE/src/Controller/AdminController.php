<?php

namespace App\Controller;

use App\Entity\Anouncement;
use App\Entity\Comment;
use App\Entity\Users;
use App\Repository\AnouncementRepository;
use App\Repository\CommentRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/admin', name: 'api_admin_')]
final class AdminController extends AbstractController
{
    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(
        UsersRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        if (!in_array('admin', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $users = $repo->createQueryBuilder('u')
            ->orderBy('u.credit', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Users $u) => [
            'id'       => $u->getId(),
            'name'     => $u->getName() . ' ' . $u->getSurname(),
            'email'    => $u->getEmail(),
            'roles'    => array_filter($u->getRoles(), fn($r) => $r !== 'ROLE_USER'),
            'credit'   => $u->getCredit() ?? 0,
            'status'   => $u->getStatus(),
            'createAt' => $u->getCreateAt()?->format('Y-m-d'),
        ], $users);

        return $this->json(['users' => array_values($data)]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(
        UsersRepository $usersRepo,
        AnouncementRepository $anouncementRepo,
        CommentRepository $commentRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user || !in_array('admin', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // Utilisateurs
        $totalUsers  = count($usersRepo->findAll());
        $activeUsers = count($usersRepo->findBy(['status' => 1]));
        $bannedUsers = count($usersRepo->findBy(['status' => 0]));
        $totalCredits = (int) $usersRepo->createQueryBuilder('u')
            ->select('SUM(u.credit)')->getQuery()->getSingleScalarResult();

        // Annonces (hors __contact__)
        $anouncements = $anouncementRepo->createQueryBuilder('a')
            ->where('a.categorie != :cat')->setParameter('cat', '__contact__')
            ->getQuery()->getResult();

        $totalAnouncements = count($anouncements);
        $byStatus = [1 => 0, 2 => 0, 3 => 0];
        $byCategory = [];
        foreach ($anouncements as $a) {
            $byStatus[$a->getStatus()] = ($byStatus[$a->getStatus()] ?? 0) + 1;
            $cat = $a->getCategorie();
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;
        }
        arsort($byCategory);

        // Commentaires publics
        $totalComments   = $commentRepo->createQueryBuilder('c')
            ->select('COUNT(c.id)')->where('c.threadUser IS NULL')
            ->getQuery()->getSingleScalarResult();
        $reportedCount   = $commentRepo->createQueryBuilder('c')
            ->select('COUNT(c.id)')->where('c.reportCount >= 3')
            ->getQuery()->getSingleScalarResult();

        // Top 5 donneurs (par annonces données statut=3)
        $topDonors = $anouncementRepo->createQueryBuilder('a')
            ->select('IDENTITY(a.user) as userId, COUNT(a.id) as total')
            ->where('a.status = 3')->andWhere('a.categorie != :cat')
            ->setParameter('cat', '__contact__')
            ->groupBy('a.user')->orderBy('total', 'DESC')
            ->setMaxResults(5)->getQuery()->getScalarResult();

        $topDonorsFull = array_map(function ($row) use ($usersRepo) {
            $u = $usersRepo->find((int) $row['userId']);
            return [
                'name'  => $u ? $u->getName() . ' ' . $u->getSurname() : '—',
                'total' => (int) $row['total'],
                'credit'=> $u?->getCredit() ?? 0,
            ];
        }, $topDonors);

        return $this->json([
            'users' => [
                'total'   => $totalUsers,
                'active'  => $activeUsers,
                'banned'  => $bannedUsers,
                'credits' => $totalCredits,
            ],
            'anouncements' => [
                'total'      => $totalAnouncements,
                'disponible' => $byStatus[1] ?? 0,
                'reserve'    => $byStatus[2] ?? 0,
                'donne'      => $byStatus[3] ?? 0,
            ],
            'comments' => [
                'total'    => (int) $totalComments,
                'reported' => (int) $reportedCount,
            ],
            'topDonors'  => $topDonorsFull,
            'categories' => $byCategory,
        ]);
    }

    #[Route('/reported-comments', name: 'reported_comments', methods: ['GET'])]
    public function reportedComments(
        CommentRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user || !in_array('admin', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $comments = $repo->createQueryBuilder('c')
            ->where('c.reportCount >= 3')
            ->andWhere('c.threadUser IS NULL')
            ->orderBy('c.reportCount', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Comment $c) => [
            'id'          => $c->getId(),
            'contenue'    => $c->getContenue(),
            'date'        => $c->getDate()->format('Y-m-d H:i:s'),
            'reportCount' => $c->getReportCount(),
            'userName'    => $c->getUser()?->getName() . ' ' . $c->getUser()?->getSurname(),
            'userId'      => $c->getUser()?->getId(),
            'anouncement' => $c->getAnouncement()?->getTitle(),
            'anouncementId' => $c->getAnouncement()?->getId(),
        ], $comments);

        return $this->json(['comments' => $data]);
    }

    #[Route('/comment/{id}', name: 'delete_comment', methods: ['DELETE'])]
    public function deleteComment(
        int $id,
        CommentRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user || !in_array('admin', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $comment = $repo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Commentaire introuvable'], 404);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(['message' => 'Commentaire supprimé']);
    }

    #[Route('/users/{id}/ban', name: 'ban_user', methods: ['PATCH'])]
    public function ban(
        int $id,
        UsersRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user || !in_array('admin', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $target = $repo->find($id);
        if (!$target) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        if ($target->getId() === $user->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas vous bannir vous-même'], 400);
        }
        if (in_array('admin', $target->getRoles())) {
            return $this->json(['error' => 'Impossible de bannir un administrateur'], 400);
        }

        $target->setStatus(0);
        $em->flush();

        return $this->json(['message' => 'Utilisateur banni', 'status' => 0]);
    }

    #[Route('/users/{id}/unban', name: 'unban_user', methods: ['PATCH'])]
    public function unban(
        int $id,
        UsersRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user || !in_array('admin', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $target = $repo->find($id);
        if (!$target) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $target->setStatus(1);
        $em->flush();

        return $this->json(['message' => 'Utilisateur débanni', 'status' => 1]);
    }
}
