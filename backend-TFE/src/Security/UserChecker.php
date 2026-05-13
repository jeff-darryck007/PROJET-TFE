<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        if ($user->getStatus() === 0) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été banni. Contactez l\'administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}
