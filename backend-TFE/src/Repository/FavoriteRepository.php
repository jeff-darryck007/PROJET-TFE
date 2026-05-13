<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /** @return int[] IDs des annonces aimées par l'utilisateur */
    public function findAnouncementIdsByUser(Users $user): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.anouncement) as aid')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(fn($r) => (int) $r['aid'], $rows);
    }
}
