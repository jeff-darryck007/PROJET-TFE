<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Users;
use App\Repository\AnouncementRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_favorite_')]
final class FavoriteController extends AbstractController
{
    #[Route('/favorite/toggle/{id}', name: 'toggle', methods: ['POST'])]
    public function toggle(
        int $id,
        #[CurrentUser] ?Users $user,
        AnouncementRepository $anouncementRepo,
        FavoriteRepository $favoriteRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $anouncement = $anouncementRepo->find($id);
        if (!$anouncement) {
            return $this->json(['error' => 'Annonce introuvable'], 404);
        }

        $existing = $favoriteRepo->findOneBy(['user' => $user, 'anouncement' => $anouncement]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return $this->json(['liked' => false]);
        }

        $favorite = new Favorite();
        $favorite->setUser($user);
        $favorite->setAnouncement($anouncement);
        $favorite->setCreateAt(new \DateTime());
        $em->persist($favorite);
        $em->flush();

        return $this->json(['liked' => true]);
    }

    #[Route('/favorites', name: 'list', methods: ['GET'])]
    public function list(
        #[CurrentUser] ?Users $user,
        FavoriteRepository $favoriteRepo,
        AnouncementRepository $anouncementRepo
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $ids = $favoriteRepo->findAnouncementIdsByUser($user);

        $anouncements = array_map(
            fn(int $aid) => $anouncementRepo->find($aid),
            $ids
        );

        $result = [];
        foreach ($anouncements as $a) {
            if (!$a) continue;
            $donor = $a->getUser();
            $pictures = $a->getPictures();
            if (is_string($pictures)) {
                $pictures = json_decode($pictures, true) ?? [];
            }
            $result[] = [
                'id'          => $a->getId(),
                'title'       => $a->getTitle(),
                'description' => $a->getDescription(),
                'categorie'   => $a->getCategorie(),
                'status'      => $a->getStatus(),
                'adress'      => $a->getAdress(),
                'createAt'    => $a->getCreateAt()?->format('d/m/Y'),
                'pictures'    => $pictures,
                'donorName'   => $donor ? $donor->getName() . ' ' . $donor->getSurname() : '',
            ];
        }

        return $this->json(['favorites' => $result, 'likedIds' => $ids]);
    }

    #[Route('/favorites/ids', name: 'ids', methods: ['GET'])]
    public function likedIds(
        #[CurrentUser] ?Users $user,
        FavoriteRepository $favoriteRepo
    ): JsonResponse {
        if (!$user) {
            return $this->json(['likedIds' => []]);
        }

        return $this->json(['likedIds' => $favoriteRepo->findAnouncementIdsByUser($user)]);
    }
}
