<?php

namespace App\Controller;

use App\Entity\Anouncement;
use App\Entity\Comment;
use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\AnouncementRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ContactController extends AbstractController
{
    // MESSAGE D'UN UTILISATEUR BANNI (route publique, sans auth)
    #[Route('/api/banned-appeal', name: 'api_banned_appeal', methods: ['POST'])]
    public function bannedAppeal(
        Request $request,
        EntityManagerInterface $em,
        UsersRepository $usersRepo,
        AnouncementRepository $anouncementRepo
    ): JsonResponse {
        $data    = json_decode($request->getContent(), true);
        $email   = strtolower(trim($data['email']   ?? ''));
        $message = trim($data['message'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide.'], 400);
        }
        if (strlen($message) < 10) {
            return $this->json(['error' => 'Le message doit contenir au moins 10 caractères.'], 400);
        }

        $bannedUser = $usersRepo->findOneBy(['email' => $email]);
        if (!$bannedUser || $bannedUser->getStatus() !== 0) {
            return $this->json(['error' => 'Aucun compte banni associé à cette adresse email.'], 404);
        }

        $admin = $usersRepo->findAdmin();
        if (!$admin) {
            return $this->json(['error' => 'Service indisponible.'], 503);
        }

        // Réutilise ou crée le fil de contact existant pour cet utilisateur
        $inbox = $anouncementRepo->findOneBy([
            'categorie'   => '__contact__',
            'description' => 'contact_user_' . $bannedUser->getId(),
        ]);

        if (!$inbox) {
            $inbox = new Anouncement();
            $inbox->setTitle('Support - ' . $bannedUser->getName() . ' ' . $bannedUser->getSurname());
            $inbox->setCategorie('__contact__');
            $inbox->setDescription('contact_user_' . $bannedUser->getId());
            $inbox->setAdress('');
            $inbox->setPostX(0);
            $inbox->setPostY(0);
            $inbox->setPostZ(0);
            $inbox->setStatus(1);
            $inbox->setCreateAt(new \DateTime());
            $inbox->setPictures(json_encode([]));
            $inbox->setUser($admin);
            $em->persist($inbox);
            $em->flush();
        }

        $content = sprintf("🚫 Appel de bannissement\n\nEmail : %s\n\n%s", $bannedUser->getEmail(), $message);

        $comment = new Comment();
        $comment->setContenue($content);
        $comment->setDate(new \DateTime());
        $comment->setUser($bannedUser);
        $comment->setAnouncement($inbox);
        $comment->setThreadUser($bannedUser);

        $em->persist($comment);

        $notif = new Notification();
        $notif->setText(sprintf(
            '⚠️ %s %s (compte banni) a envoyé un message de contestation.',
            $bannedUser->getName(),
            $bannedUser->getSurname()
        ));
        $notif->setType('message');
        $notif->setIsRead(false);
        $notif->setCreateAt(new \DateTime());
        $notif->setUser($admin);
        $notif->setAnouncement($inbox);
        $em->persist($notif);

        $em->flush();

        return $this->json(['message' => 'Votre message a bien été envoyé à l\'administrateur.'], 201);
    }

    #[Route('/api/contact', name: 'api_contact_send', methods: ['POST'])]
    public function send(
        Request $request,
        EntityManagerInterface $em,
        UsersRepository $usersRepo,
        AnouncementRepository $anouncementRepo,
        #[CurrentUser] ?Users $currentUser
    ): JsonResponse {
        if (!$currentUser) {
            return $this->json(['error' => 'Vous devez être connecté pour envoyer un message.'], 401);
        }

        $data    = json_decode($request->getContent(), true);
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if (!$subject || !$message) {
            return $this->json(['error' => 'Sujet et message requis.'], 400);
        }

        if (strlen($message) < 10) {
            return $this->json(['error' => 'Le message doit contenir au moins 10 caractères.'], 400);
        }

        $admin = $usersRepo->findAdmin();
        if (!$admin) {
            return $this->json(['error' => 'Service indisponible.'], 503);
        }

        // Fil privé entre cet utilisateur et l'admin (un par utilisateur)
        $inbox = $anouncementRepo->findOneBy([
            'categorie'   => '__contact__',
            'description' => 'contact_user_' . $currentUser->getId(),
        ]);

        if (!$inbox) {
            $inbox = new Anouncement();
            $inbox->setTitle('Support - ' . $currentUser->getName() . ' ' . $currentUser->getSurname());
            $inbox->setCategorie('__contact__');
            $inbox->setDescription('contact_user_' . $currentUser->getId());
            $inbox->setAdress('');
            $inbox->setPostX(0);
            $inbox->setPostY(0);
            $inbox->setPostZ(0);
            $inbox->setStatus(1);
            $inbox->setCreateAt(new \DateTime());
            $inbox->setPictures(json_encode([]));
            $inbox->setUser($admin);
            $em->persist($inbox);
            $em->flush();
        }

        $content = sprintf("📬 Sujet : %s\n\n%s", $subject, $message);

        $comment = new Comment();
        $comment->setContenue($content);
        $comment->setDate(new \DateTime());
        $comment->setUser($currentUser);
        $comment->setAnouncement($inbox);
        $comment->setThreadUser($currentUser);

        $em->persist($comment);

        // Notifier l'admin (propriétaire du fil) quand c'est l'utilisateur qui écrit
        if ($admin->getId() !== $currentUser->getId()) {
            $notif = new Notification();
            $notif->setText(sprintf(
                'Nouveau message de support de %s %s.',
                $currentUser->getName(),
                $currentUser->getSurname()
            ));
            $notif->setType('message');
            $notif->setIsRead(false);
            $notif->setCreateAt(new \DateTime());
            $notif->setUser($admin);
            $notif->setAnouncement($inbox);
            $em->persist($notif);
        }

        $em->flush();

        return $this->json([
            'message'        => 'Message envoyé avec succès.',
            'anouncementId'  => $inbox->getId(),
        ], 201);
    }
}
