<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    // Messages d'un fil privé (annonce + utilisateur non-propriétaire)
    public function findThread(int $anouncementId, int $threadUserId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.anouncement = :aid')
            ->andWhere('c.threadUser = :tuid')
            ->setParameter('aid', $anouncementId)
            ->setParameter('tuid', $threadUserId)
            ->orderBy('c.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Conversations d'un utilisateur en tant que demandeur (threadUser = lui)
    public function findAsRequester(Users $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.anouncement', 'a')
            ->where('c.threadUser = :user')
            ->andWhere('a.categorie != :cat')
            ->setParameter('user', $user)
            ->setParameter('cat', '__contact__')
            ->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Liste des utilisateurs uniques qui ont envoyé un message sur une annonce
    public function findRequestersForAnouncement(int $anouncementId): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT IDENTITY(c.threadUser) as userId')
            ->join('c.threadUser', 'tu')
            ->where('c.anouncement = :aid')
            ->andWhere('c.threadUser IS NOT NULL')
            ->setParameter('aid', $anouncementId)
            ->getQuery()
            ->getScalarResult();
    }

    // Commentaires publics d'une annonce (threadUser IS NULL)
    public function findPublicComments(int $anouncementId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.anouncement = :aid')
            ->andWhere('c.threadUser IS NULL')
            ->setParameter('aid', $anouncementId)
            ->orderBy('c.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Conversations reçues par un donneur (annonces lui appartenant, groupées par threadUser)
    public function findCommentsOnUserAnouncements(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.anouncement', 'a')
            ->where('a.user = :userId')
            ->andWhere('c.threadUser != :userId OR c.threadUser IS NULL')
            ->andWhere('a.categorie != :cat')
            ->setParameter('userId', $userId)
            ->setParameter('cat', '__contact__')
            ->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
