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
