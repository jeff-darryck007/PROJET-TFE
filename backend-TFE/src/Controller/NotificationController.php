<?php

namespace App\Controller;

use App\Entity\Anouncement;
use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_notification_')]
final class NotificationController extends AbstractController
{
    // MES NOTIFICATIONS
    #[Route('/notifications', name: 'list', methods: ['GET'])]
    public function list(
        NotificationRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Annonces chargées dans la même requête (évite une requête par notification pour le champ « link »)
        $notifications = $repo->createQueryBuilder('n')
            ->addSelect('a')
            ->leftJoin('n.anouncement', 'a')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createAt', 'DESC')
            ->getQuery()
            ->getResult();

        $isAdmin = in_array('admin', $user->getRoles(), true);
        // Annonce encore consultable par cet utilisateur : disponible (1) ou réservée (2) pour tous ;
        // sinon (en attente, donnée, refusée) seulement pour le donneur, la personne concernée ou un admin
        $available = function (?Anouncement $a) use ($user, $isAdmin): bool {
            if (!$a) {
                return false;
            }
            return in_array($a->getStatus(), [1, 2], true)
                || $isAdmin
                || $a->getUser()?->getId() === $user->getId()
                || $a->getReservedForUser()?->getId() === $user->getId();
        };

        $data = array_map(fn(Notification $n) => [
            'id'             => $n->getId(),
            'text'           => $n->getText(),
            'type'           => $n->getType(),
            'isRead'         => $n->isRead(),
            'createAt'       => $n->getCreateAt()->format('Y-m-d H:i:s'),
            'anouncementId'  => $n->getAnouncement()?->getId(),
            // true si la notification concerne une annonce (pas la boîte de contact interne)
            'link'           => $n->getAnouncement() !== null && $n->getAnouncement()->getCategorie() !== '__contact__',
            // false si l'annonce n'est plus disponible pour cet utilisateur → « N'est plus disponible »
            'available'      => $available($n->getAnouncement()),
        ], $notifications);

        $unreadCount = count(array_filter($data, fn($n) => !$n['isRead']));

        return $this->json([
            'notifications' => $data,
            'unreadCount'   => $unreadCount,
        ]);
    }

    // NOMBRE DE NON-LUES (pour badge navbar)
    #[Route('/notifications/unread-count', name: 'unread_count', methods: ['GET'])]
    public function unreadCount(
        NotificationRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['count' => 0]);
        }

        $count = $repo->count(['user' => $user, 'isRead' => false]);

        return $this->json(['count' => $count]);
    }

    // MARQUER COMME LU
    #[Route('/notification/{id}/read', name: 'mark_read', methods: ['PATCH'])]
    public function markRead(
        int $id,
        NotificationRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notif = $repo->find($id);

        if (!$notif || $notif->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Notification introuvable'], 404);
        }

        $notif->setIsRead(true);
        $em->flush();

        return $this->json(['success' => true]);
    }

    // TOUT MARQUER COMME LU
    #[Route('/notifications/read-all', name: 'read_all', methods: ['PATCH'])]
    public function readAll(
        NotificationRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $unread = $repo->findBy(['user' => $user, 'isRead' => false]);
        foreach ($unread as $notif) {
            $notif->setIsRead(true);
        }
        $em->flush();

        return $this->json(['success' => true, 'marked' => count($unread)]);
    }

    // SUPPRIMER UNE NOTIFICATION
    #[Route('/notification/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        NotificationRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notif = $repo->find($id);

        if (!$notif || $notif->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Notification introuvable'], 404);
        }

        $em->remove($notif);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
