<?php

namespace App\Security;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JWTAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $em
    ) {}

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): JsonResponse {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return new JsonResponse(['error' => 'Invalid user'], 401);
        }

        if ($user instanceof Users) {
            // Force le rechargement depuis la DB (bypasse l'identity map Doctrine)
            $this->em->refresh($user);
            $status = $user->getStatus();

            if ($status === 0) {
                return new JsonResponse(
                    ['error' => 'Votre compte a été banni. Contactez l\'administrateur.'],
                    401
                );
            }
            if ($status !== 1) {
                return new JsonResponse(
                    ['error' => 'Votre compte n\'est pas actif. Contactez l\'administrateur.'],
                    401
                );
            }
        }

        return new JsonResponse([
            'token' => $this->jwtManager->create($user),
            'id'    => method_exists($user, 'getId') ? $user->getId() : null,
            'roles' => $user->getRoles()
        ]);
    }
}