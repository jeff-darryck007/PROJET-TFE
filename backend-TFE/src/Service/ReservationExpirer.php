<?php

namespace App\Service;

use App\Entity\Anouncement;
use App\Entity\Notification;
use App\Entity\Users;
use App\Repository\AnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Délai de récupération d'une annonce réservée.
 *
 * Quand le donneur réserve une annonce pour quelqu'un (statut 2), cette personne a
 * HOURS heures pour confirmer la récupération. Passé ce délai, l'annonce redevient
 * disponible (statut 1) et les deux personnes sont prévenues par une notification.
 */
final class ReservationExpirer
{
    public const HOURS = 10;

    public function __construct(
        private AnouncementRepository $repo,
        private EntityManagerInterface $em,
    ) {}

    public static function deadlineFromNow(): \DateTime
    {
        return new \DateTime('+' . self::HOURS . ' hours');
    }

    /** Libère toutes les réservations dont le délai est dépassé ; renvoie leur nombre. */
    public function expire(): int
    {
        /** @var Anouncement[] $expired */
        $expired = $this->repo->createQueryBuilder('a')
            ->where('a.status = 2')
            ->andWhere('a.reservationExpiresAt IS NOT NULL')
            ->andWhere('a.reservationExpiresAt <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        foreach ($expired as $a) {
            $this->release($a);
        }

        if ($expired) {
            $this->em->flush();
        }

        return count($expired);
    }

    /** Remet une annonce en disponible et prévient le bénéficiaire et le donneur (sans flush). */
    public function release(Anouncement $a): void
    {
        $beneficiary = $a->getReservedForUser();
        $donor       = $a->getUser();
        // Titre raccourci : le texte d'une notification est limité à 255 caractères
        $title       = mb_strimwidth((string) $a->getTitle(), 0, 60, '…');

        $a->setStatus(1);
        $a->setReservedForUser(null);
        $a->setReservationExpiresAt(null);

        if ($beneficiary) {
            $this->notify($beneficiary, $a, sprintf(
                'Le délai de %d h pour récupérer "%s" est dépassé : l\'annonce est de nouveau disponible pour tous.',
                self::HOURS,
                $title
            ));
        }
        if ($donor) {
            $this->notify($donor, $a, sprintf(
                'L\'objet "%s" n\'a pas été récupéré dans le délai de %d h : votre annonce est de nouveau disponible.',
                $title,
                self::HOURS
            ));
        }
    }

    private function notify(Users $user, Anouncement $a, string $text): void
    {
        $notif = new Notification();
        $notif->setText($text);
        $notif->setType('reservation-expired');
        $notif->setIsRead(false);
        $notif->setCreateAt(new \DateTime());
        $notif->setUser($user);
        $notif->setAnouncement($a);
        $this->em->persist($notif);
    }
}
