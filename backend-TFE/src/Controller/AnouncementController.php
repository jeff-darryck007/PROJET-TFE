<?php

namespace App\Controller;

use App\Entity\Anouncement;
use App\Entity\Notification;
use App\Repository\AnouncementRepository;
use App\Repository\CommentRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Users;

#[Route('/api', name: 'api_anouncement_')]
final class AnouncementController extends AbstractController
{
    // CRÉER UNE ANNONCE
    #[Route('/anouncement', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UsersRepository $usersRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $title       = trim($request->request->get('title', ''));
        $categorie   = trim($request->request->get('categorie', ''));
        $description = trim($request->request->get('description', ''));
        $adress      = trim($request->request->get('adress', ''));
        $postX       = $request->request->get('postX');
        $postY       = $request->request->get('postY');
        $postZ       = $request->request->get('postZ');

        foreach (['title' => $title, 'categorie' => $categorie, 'description' => $description, 'adress' => $adress] as $field => $value) {
            if ($value === '') {
                return $this->json(['error' => "Le champ '$field' est requis"], 400);
            }
        }

        // Upload des photos
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/upload/anoucement/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $picturesPaths = [];
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        foreach ($request->files->get('pictures', []) as $file) {
            $filename = uniqid('img_', true) . '.' . $file->guessExtension();
            $file->move($uploadDir, $filename);
            $picturesPaths[] = $filename;
        }

        $anouncement = new Anouncement();
        $anouncement->setTitle($title);
        $anouncement->setCategorie($categorie);
        $anouncement->setDescription($description);
        $anouncement->setAdress($adress);
        $anouncement->setPostX($postX !== null ? (float) $postX : 0.0);
        $anouncement->setPostY($postY !== null ? (float) $postY : 0.0);
        $anouncement->setPostZ($postZ !== null ? (float) $postZ : 0.0);
        $anouncement->setStatus(1);
        $anouncement->setCreateAt(new \DateTime());
        $anouncement->setPictures(json_encode($picturesPaths));
        $anouncement->setUser($user);

        $em->persist($anouncement);
        $em->flush();

        // Notifier tous les utilisateurs actifs (sauf le publieur)
        $allUsers = $usersRepo->findBy(['status' => 1]);
        foreach ($allUsers as $recipient) {
            if ($recipient->getId() === $user->getId()) {
                continue;
            }
            $notif = new Notification();
            $notif->setText(sprintf(
                'Nouvelle annonce : "%s" publiée par %s %s.',
                $anouncement->getTitle(),
                $user->getName(),
                $user->getSurname()
            ));
            $notif->setType('new-article');
            $notif->setIsRead(false);
            $notif->setCreateAt(new \DateTime());
            $notif->setUser($recipient);
            $notif->setAnouncement($anouncement);
            $em->persist($notif);
        }
        $em->flush();

        return $this->json([
            'message' => 'Annonce créée avec succès',
            'id' => $anouncement->getId(),
        ], 201);
    }

    // MES ANNONCES (utilisateur connecté)
    #[Route('/my-anouncements', name: 'mine', methods: ['GET'])]
    public function mine(
        AnouncementRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncements = $repo->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.categorie != :cat')
            ->setParameter('user', $user)
            ->setParameter('cat', '__contact__')
            ->orderBy('a.createAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Anouncement $a) => [
            'id'                => $a->getId(),
            'title'             => $a->getTitle(),
            'categorie'         => $a->getCategorie(),
            'description'       => $a->getDescription(),
            'adress'            => $a->getAdress(),
            'status'            => $a->getStatus(),
            'createAt'          => $a->getCreateAt()?->format('Y-m-d'),
            'pictures'          => json_decode($a->getPictures() ?? '[]', true),
            'reservedForUserId' => $a->getReservedForUser()?->getId(),
        ], $anouncements);

        return $this->json(['success' => true, 'data' => $data]);
    }

    // LISTE DE TOUTES LES ANNONCES
    #[Route('/anouncements', name: 'list', methods: ['GET'])]
    public function list(AnouncementRepository $repo): JsonResponse
    {
        $anouncements = $repo->createQueryBuilder('a')
            ->where('a.categorie != :cat')
            ->setParameter('cat', '__contact__')
            ->orderBy('a.createAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Anouncement $a) => [
            'id'          => $a->getId(),
            'title'       => $a->getTitle(),
            'categorie'   => $a->getCategorie(),
            'description' => $a->getDescription(),
            'adress'      => $a->getAdress(),
            'postX'       => $a->getPostX(),
            'postY'       => $a->getPostY(),
            'postZ'       => $a->getPostZ(),
            'status'      => $a->getStatus(),
            'createAt'    => $a->getCreateAt()?->format('Y-m-d'),
            'pictures'    => json_decode($a->getPictures() ?? '[]', true),
            'userId'      => $a->getUser()?->getId(),
            'donorName'   => $a->getUser()?->getName() . ' ' . $a->getUser()?->getSurname(),
        ], $anouncements);

        return $this->json(['success' => true, 'data' => $data]);
    }

    // DÉTAIL D'UNE ANNONCE
    #[Route('/anouncement/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, AnouncementRepository $repo): JsonResponse
    {
        $a = $repo->find($id);

        if (!$a || $a->getCategorie() === '__contact__') {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }

        return $this->json([
            'id'                => $a->getId(),
            'title'             => $a->getTitle(),
            'categorie'         => $a->getCategorie(),
            'description'       => $a->getDescription(),
            'adress'            => $a->getAdress(),
            'postX'             => $a->getPostX(),
            'postY'             => $a->getPostY(),
            'postZ'             => $a->getPostZ(),
            'status'            => $a->getStatus(),
            'createAt'          => $a->getCreateAt()?->format('Y-m-d'),
            'pictures'          => json_decode($a->getPictures() ?? '[]', true),
            'userId'            => $a->getUser()?->getId(),
            'donorName'         => $a->getUser()?->getName() . ' ' . $a->getUser()?->getSurname(),
            'donorPicture'      => $a->getUser()?->getPicture(),
            'reservedForUserId' => $a->getReservedForUser()?->getId(),
        ]);
    }

    // LISTE DES DEMANDEURS D'UNE ANNONCE (utilisateurs qui ont envoyé un message)
    #[Route('/anouncement/{id}/requesters', name: 'requesters', methods: ['GET'])]
    public function requesters(
        int $id,
        AnouncementRepository $repo,
        CommentRepository $commentRepo,
        UsersRepository $usersRepo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $repo->find($id);
        if (!$anouncement || $anouncement->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $rows = $commentRepo->findRequestersForAnouncement($id);
        $data = [];
        foreach ($rows as $row) {
            $u = $usersRepo->find((int) $row['userId']);
            if ($u) {
                $data[] = [
                    'id'   => $u->getId(),
                    'name' => $u->getName() . ' ' . $u->getSurname(),
                ];
            }
        }

        return $this->json(['requesters' => $data]);
    }

    // RÉSERVER UNE ANNONCE POUR UN DEMANDEUR (statut → 2)
    #[Route('/anouncement/{id}/reserve', name: 'reserve', methods: ['PATCH'])]
    public function reserve(
        int $id,
        Request $request,
        AnouncementRepository $repo,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $repo->find($id);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }
        if ($anouncement->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $data             = json_decode($request->getContent(), true);
        $reservedForId    = $data['reservedForUserId'] ?? null;

        if (!$reservedForId) {
            // Annuler la réservation
            $anouncement->setStatus(1);
            $anouncement->setReservedForUser(null);
            $em->flush();
            return $this->json(['message' => 'Réservation annulée', 'status' => 1]);
        }

        $reservedForUser = $usersRepo->find((int) $reservedForId);
        if (!$reservedForUser) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $anouncement->setStatus(2);
        $anouncement->setReservedForUser($reservedForUser);

        $notif = new Notification();
        $notif->setText(sprintf(
            '"%s" vous a été réservé par %s %s. Confirmez la récupération depuis votre accueil.',
            $anouncement->getTitle(),
            $user->getName(),
            $user->getSurname()
        ));
        $notif->setType('reservation');
        $notif->setIsRead(false);
        $notif->setCreateAt(new \DateTime());
        $notif->setUser($reservedForUser);
        $notif->setAnouncement($anouncement);
        $em->persist($notif);
        $em->flush();

        return $this->json([
            'message'           => 'Annonce réservée',
            'status'            => 2,
            'reservedForUserId' => $reservedForUser->getId(),
            'reservedForName'   => $reservedForUser->getName() . ' ' . $reservedForUser->getSurname(),
        ]);
    }

    // VALIDER LA RÉCUPÉRATION (statut → 3, crédits donateur +10)
    #[Route('/anouncement/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validateRecovery(
        int $id,
        AnouncementRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $repo->find($id);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }
        if ($anouncement->getReservedForUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }
        if ($anouncement->getStatus() !== 2) {
            return $this->json(['error' => 'Cette annonce n\'est pas en statut Réservé'], 400);
        }

        $anouncement->setStatus(3);
        $anouncement->setRecoveredAt(new \DateTime());
        $donor = $anouncement->getUser();

        $newCredit = 0;
        if ($donor) {
            $newCredit = ($donor->getCredit() ?? 0) + 10;
            $donor->setCredit($newCredit);

            $notif = new Notification();
            $notif->setText(sprintf(
                '%s %s a confirmé la récupération de "%s". +10 crédits !',
                $user->getName(),
                $user->getSurname(),
                $anouncement->getTitle()
            ));
            $notif->setType('credit');
            $notif->setIsRead(false);
            $notif->setCreateAt(new \DateTime());
            $notif->setUser($donor);
            $notif->setAnouncement($anouncement);
            $em->persist($notif);
        }

        $em->flush();

        return $this->json([
            'message'   => 'Récupération validée. Merci !',
            'newCredit' => $newCredit,
        ]);
    }

    // MES RÉCUPÉRATIONS EN ATTENTE (annonces réservées pour moi, statut 2)
    #[Route('/my-pending-recoveries', name: 'pending_recoveries', methods: ['GET'])]
    public function pendingRecoveries(
        AnouncementRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncements = $repo->createQueryBuilder('a')
            ->where('a.reservedForUser = :user')
            ->andWhere('a.status = 2')
            ->setParameter('user', $user)
            ->orderBy('a.createAt', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Anouncement $a) => [
            'id'        => $a->getId(),
            'title'     => $a->getTitle(),
            'categorie' => $a->getCategorie(),
            'adress'    => $a->getAdress(),
            'pictures'  => json_decode($a->getPictures() ?? '[]', true),
            'donorName' => $a->getUser()?->getName() . ' ' . $a->getUser()?->getSurname(),
        ], $anouncements);

        return $this->json(['data' => $data]);
    }

    // MES ARTICLES RÉCUPÉRÉS (statut 3, réservés pour moi)
    #[Route('/my-recovered-articles', name: 'recovered_articles', methods: ['GET'])]
    public function myRecoveredArticles(
        AnouncementRepository $repo,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncements = $repo->createQueryBuilder('a')
            ->where('a.reservedForUser = :user')
            ->andWhere('a.status = 3')
            ->setParameter('user', $user)
            ->orderBy('a.recoveredAt', 'DESC')
            ->getQuery()
            ->getResult();

        $thirtyDaysAgo = new \DateTime('-30 days');
        $recoveredThisMonth = 0;

        $data = array_map(function (Anouncement $a) use ($thirtyDaysAgo, &$recoveredThisMonth) {
            $recoveredAt = $a->getRecoveredAt();
            if ($recoveredAt && $recoveredAt >= $thirtyDaysAgo) {
                $recoveredThisMonth++;
            }
            return [
                'id'          => $a->getId(),
                'title'       => $a->getTitle(),
                'categorie'   => $a->getCategorie(),
                'adress'      => $a->getAdress(),
                'pictures'    => json_decode($a->getPictures() ?? '[]', true),
                'donorName'   => $a->getUser()?->getName() . ' ' . $a->getUser()?->getSurname(),
                'recoveredAt' => $recoveredAt?->format('Y-m-d H:i:s'),
            ];
        }, $anouncements);

        return $this->json([
            'data'               => $data,
            'recoveredThisMonth' => $recoveredThisMonth,
            'canContact'         => $recoveredThisMonth < 5,
        ]);
    }

    // SUPPRIMER UNE ANNONCE
    #[Route('/anouncement/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        AnouncementRepository $repo,
        EntityManagerInterface $em,
        #[CurrentUser] ?Users $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $repo->find($id);

        if (!$anouncement) {
            return $this->json(['error' => 'Annonce non trouvée'], 404);
        }

        if ($anouncement->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Action non autorisée'], 403);
        }

        $em->remove($anouncement);
        $em->flush();

        return $this->json(['message' => 'Annonce supprimée avec succès']);
    }
}
