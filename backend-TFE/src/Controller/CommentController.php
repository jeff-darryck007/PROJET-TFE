<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\AnouncementRepository;
use App\Repository\CommentRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_comment_')]
final class CommentController extends AbstractController
{
    // ENVOYER UN MESSAGE PRIVÉ
    #[Route('/comment', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        AnouncementRepository $anouncementRepo,
        UsersRepository $usersRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data          = json_decode($request->getContent(), true);
        $contenue      = trim($data['contenue'] ?? '');
        $anouncementId = $data['anouncementId'] ?? null;
        $threadUserId  = $data['threadUserId']  ?? null;

        if (!$contenue) {
            return $this->json(['error' => 'Le message ne peut pas être vide'], 400);
        }
        if (!$anouncementId) {
            return $this->json(['error' => 'anouncementId requis'], 400);
        }

        $anouncement = $anouncementRepo->find($anouncementId);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }

        $owner   = $anouncement->getUser();
        $isOwner = $owner && $owner->getId() === $user->getId();

        // Déterminer le threadUser (l'utilisateur non-propriétaire du fil)
        if (!$isOwner) {
            $threadUser = $user; // L'expéditeur est le demandeur
        } else {
            // Le propriétaire répond : le threadUserId doit être fourni
            if (!$threadUserId) {
                return $this->json(['error' => 'threadUserId requis pour le donneur'], 400);
            }
            $threadUser = $usersRepo->find($threadUserId);
            if (!$threadUser) {
                return $this->json(['error' => 'Utilisateur introuvable'], 404);
            }
        }

        $comment = new Comment();
        $comment->setContenue($contenue);
        $comment->setDate(new \DateTime());
        $comment->setUser($user);
        $comment->setAnouncement($anouncement);
        $comment->setThreadUser($threadUser);

        $em->persist($comment);

        // Notifications
        $senderName = $user->getName() . ' ' . $user->getSurname();

        if (!$isOwner && $owner && $owner->getId() !== $user->getId()) {
            // Demandeur → notifier le donneur
            $notif = new Notification();
            $notif->setText(sprintf('Nouveau message de %s sur "%s".', $senderName, $anouncement->getTitle()));
            $notif->setType('message');
            $notif->setIsRead(false);
            $notif->setCreateAt(new \DateTime());
            $notif->setUser($owner);
            $notif->setAnouncement($anouncement);
            $em->persist($notif);
        } elseif ($isOwner && $threadUser) {
            // Donneur répond → notifier le demandeur
            $notif = new Notification();
            $notif->setText(sprintf('Réponse de %s sur "%s".', $senderName, $anouncement->getTitle()));
            $notif->setType('message');
            $notif->setIsRead(false);
            $notif->setCreateAt(new \DateTime());
            $notif->setUser($threadUser);
            $notif->setAnouncement($anouncement);
            $em->persist($notif);
        }

        $em->flush();

        return $this->json([
            'id'           => $comment->getId(),
            'contenue'     => $comment->getContenue(),
            'date'         => $comment->getDate()->format('Y-m-d H:i:s'),
            'userId'       => $user->getId(),
            'userName'     => $senderName,
            'threadUserId' => $threadUser->getId(),
        ], 201);
    }

    // MESSAGES D'UN FIL PRIVÉ (annonce + threadUserId)
    #[Route('/anouncement/{id}/comments', name: 'by_anouncement', methods: ['GET'])]
    public function byAnouncement(
        int $id,
        Request $request,
        AnouncementRepository $anouncementRepo,
        CommentRepository $commentRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        $anouncement = $anouncementRepo->find($id);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }

        $owner        = $anouncement->getUser();
        $isOwner      = $user && $owner && $owner->getId() === $user->getId();
        $threadUserId = $request->query->get('threadUserId');

        if ($isOwner && $threadUserId) {
            // Donneur consulte un fil spécifique
            $comments = $commentRepo->findThread($id, (int) $threadUserId);
        } elseif (!$isOwner && $user) {
            // Demandeur : son propre fil
            $comments = $commentRepo->findThread($id, $user->getId());
        } else {
            $comments = [];
        }

        $data = array_map(fn(Comment $c) => [
            'id'           => $c->getId(),
            'contenue'     => $c->getContenue(),
            'date'         => $c->getDate()->format('Y-m-d H:i:s'),
            'userId'       => $c->getUser()?->getId(),
            'userName'     => $c->getUser()?->getName() . ' ' . $c->getUser()?->getSurname(),
            'threadUserId' => $c->getThreadUser()?->getId(),
        ], $comments);

        return $this->json([
            'anouncementId'    => $id,
            'anouncementTitle' => $anouncement->getTitle(),
            'donorId'          => $owner?->getId(),
            'donorName'        => $owner?->getName() . ' ' . $owner?->getSurname(),
            'comments'         => $data,
        ]);
    }

    // COMMENTAIRES PUBLICS D'UNE ANNONCE (GET)
    #[Route('/anouncement/{id}/public-comments', name: 'public_list', methods: ['GET'])]
    public function publicList(
        int $id,
        AnouncementRepository $anouncementRepo,
        CommentRepository $commentRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $anouncementRepo->find($id);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }

        $ownerId  = $anouncement->getUser()?->getId();
        $comments = $commentRepo->findPublicComments($id);

        $data = array_map(fn(Comment $c) => [
            'id'       => $c->getId(),
            'contenue' => $c->getContenue(),
            'date'     => $c->getDate()->format('Y-m-d H:i:s'),
            'userId'   => $c->getUser()?->getId(),
            'userName' => $c->getUser()?->getName() . ' ' . $c->getUser()?->getSurname(),
            'isAuthor' => $c->getUser()?->getId() === $ownerId,
        ], $comments);

        return $this->json(['comments' => $data]);
    }

    // POSTER UN COMMENTAIRE PUBLIC (POST)
    #[Route('/anouncement/{id}/public-comment', name: 'public_create', methods: ['POST'])]
    public function publicCreate(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        AnouncementRepository $anouncementRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $anouncementRepo->find($id);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }

        $data     = json_decode($request->getContent(), true);
        $contenue = trim($data['contenue'] ?? '');
        if (!$contenue) {
            return $this->json(['error' => 'Le commentaire ne peut pas être vide'], 400);
        }

        $comment = new Comment();
        $comment->setContenue($contenue);
        $comment->setDate(new \DateTime());
        $comment->setUser($user);
        $comment->setAnouncement($anouncement);
        $comment->setThreadUser(null);

        $em->persist($comment);
        $em->flush();

        $ownerId = $anouncement->getUser()?->getId();

        return $this->json([
            'id'       => $comment->getId(),
            'contenue' => $comment->getContenue(),
            'date'     => $comment->getDate()->format('Y-m-d H:i:s'),
            'userId'   => $user->getId(),
            'userName' => $user->getName() . ' ' . $user->getSurname(),
            'isAuthor' => $user->getId() === $ownerId,
        ], 201);
    }

    // SIGNALER UN COMMENTAIRE
    #[Route('/comment/{id}/report', name: 'report', methods: ['POST'])]
    public function report(
        int $id,
        CommentRepository $commentRepo,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $comment = $commentRepo->find($id);
        if (!$comment) {
            return $this->json(['error' => 'Commentaire introuvable'], 404);
        }

        $comment->incrementReportCount();

        $admin = $usersRepo->findAdmin();
        if ($admin && $comment->getReportCount() >= 3) {
            $notif = new Notification();
            $notif->setText(sprintf(
                'Commentaire signalé %d fois : "%s"',
                $comment->getReportCount(),
                mb_strimwidth($comment->getContenue(), 0, 80, '...')
            ));
            $notif->setType('report');
            $notif->setIsRead(false);
            $notif->setCreateAt(new \DateTime());
            $notif->setUser($admin);
            $notif->setAnouncement($comment->getAnouncement());
            $em->persist($notif);
        }

        $em->flush();

        return $this->json(['message' => 'Commentaire signalé avec succès']);
    }

    // LISTE DES CONVERSATIONS DE L'UTILISATEUR CONNECTÉ
    #[Route('/my-conversations', name: 'my_conversations', methods: ['GET'])]
    public function myConversations(
        CommentRepository $commentRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $seen = [];

        // 1. Conversations où je suis demandeur (threadUser = moi)
        foreach ($commentRepo->findAsRequester($user) as $c) {
            $a   = $c->getAnouncement();
            $key = 'a' . $a->getId() . '_u' . $user->getId();
            if ($a && !isset($seen[$key])) {
                $donor = $a->getUser();
                $seen[$key] = [
                    'anouncementId'    => $a->getId(),
                    'anouncementTitle' => $a->getTitle(),
                    'donorId'          => $donor?->getId(),
                    'donorName'        => $donor?->getName() . ' ' . $donor?->getSurname(),
                    'threadUserId'     => $user->getId(),
                    'threadUserName'   => $user->getName() . ' ' . $user->getSurname(),
                    'isOwner'          => false,
                    'lastMessage'      => $c->getContenue(),
                    'lastDate'         => $c->getDate()->format('Y-m-d H:i:s'),
                ];
            }
        }

        // 2. Conversations où je suis donneur (mes annonces, groupées par threadUser)
        foreach ($commentRepo->findCommentsOnUserAnouncements($user->getId()) as $c) {
            $a          = $c->getAnouncement();
            $threadUser = $c->getThreadUser();
            if (!$a || !$threadUser) continue;
            $key = 'a' . $a->getId() . '_u' . $threadUser->getId();
            if (!isset($seen[$key])) {
                $seen[$key] = [
                    'anouncementId'    => $a->getId(),
                    'anouncementTitle' => $a->getTitle(),
                    'donorId'          => $user->getId(),
                    'donorName'        => $user->getName() . ' ' . $user->getSurname(),
                    'threadUserId'     => $threadUser->getId(),
                    'threadUserName'   => $threadUser->getName() . ' ' . $threadUser->getSurname(),
                    'isOwner'          => true,
                    'lastMessage'      => $c->getContenue(),
                    'lastDate'         => $c->getDate()->format('Y-m-d H:i:s'),
                ];
            }
        }

        usort($seen, fn($a, $b) => strcmp($b['lastDate'], $a['lastDate']));

        return $this->json(['conversations' => array_values($seen)]);
    }
}
