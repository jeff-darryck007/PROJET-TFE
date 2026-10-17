<?php

namespace App\EventListener;

use App\Service\ReservationExpirer;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Libère les réservations dont le délai de récupération est dépassé.
 *
 * Pas besoin de tâche planifiée (cron) : la vérification se fait lors des appels à l'API,
 * au plus une fois toutes les CHECK_EVERY secondes pour ne pas solliciter la base à chaque requête.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 5)]
final class ReservationExpiryListener
{
    private const CHECK_EVERY = 15;

    public function __construct(
        private ReservationExpirer $expirer,
        private CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $lastCheck = $this->cache->getItem('reservation_expiry_last_check');
        if ($lastCheck->isHit()) {
            return;
        }
        $lastCheck->set(true)->expiresAfter(self::CHECK_EVERY);
        $this->cache->save($lastCheck);

        $this->expirer->expire();
    }
}
