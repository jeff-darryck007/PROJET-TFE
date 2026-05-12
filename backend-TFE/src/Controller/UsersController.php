<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse; // formater les reponses en json
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // hascher le mot de passe
use Symfony\Component\HttpFoundation\Request; // corps de la requete
use Doctrine\ORM\EntityManagerInterface;


use App\Entity\Users;
use App\Repository\UsersRepository;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api', name: 'api_')]
final class UsersController extends AbstractController
{

    /**
     * Cette route ne sera jamais réellement exécutée :
     * le firewall "login" interceptera la requête avant.
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Ce point ne devrait pas être atteint — vérifie la configuration de sécurité.'
        ], 400);
    }

    // RECUPERER UN UTILISATEUR PAR TOKEN  
    #[Route('/me', name: 'api_me', methods: ['GET'])] 
    public function me(#[CurrentUser] ?Users $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        return $this->json([
            'status' => 'success',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UsersRepository $usersRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

        // Vérification des champs requis
        foreach (['email', 'password', 'name', 'surname', 'role'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Le champ '$field' est requis"], 400);
            }
        }

        // Sanitize
        $email   = strtolower(trim($data['email']));
        $name    = trim(strip_tags($data['name']));
        $surname = trim(strip_tags($data['surname']));
        $role    = $data['role'];
        $password = $data['password'];

        // Validation email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide'], 422);
        }

        // Email déjà utilisé
        if ($usersRepository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Cette adresse email est déjà utilisée'], 409);
        }

        // Validation nom / prénom
        if (strlen($name) < 2 || strlen($surname) < 2) {
            return $this->json(['error' => 'Le nom et le prénom doivent contenir au moins 2 caractères'], 422);
        }

        // Validation mot de passe
        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 422);
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins une lettre et un chiffre'], 422);
        }

        // Rôle autorisé
        if (!in_array($role, ['donateur', 'visiteur'], true)) {
            return $this->json(['error' => 'Rôle invalide'], 422);
        }

        $user = new Users();
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setEmail($email);
        $user->setName($name);
        $user->setSurname($surname);
        $user->setRoles([$role]);
        $user->setPicture(null);
        $user->setCredit(0);
        $user->setStatus(1);

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Compte créé avec succès'], 201);
    }


    // LISTE DES UTILISATEURS
    #[Route('/users', name: 'app_users', methods: ['GET'])]
    public function index(UsersRepository $userRepository): JsonResponse
    {
        // Récupération de tous les utilisateurs
        $users = $userRepository->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                ‘id’ => $user->getId(),
                ‘email’ => $user->getEmail(),
                ‘name’ => $user->getName(),
                ‘surname’ => $user->getSurname(),
                ‘roles’ => $user->getRoles(),
                ‘status’ => $user->getStatus(),
                ‘credit’ => $user->getCredit(),
            ];
        }

        return $this->json([
            "success" => true,
            "data" => $data
        ]);
    }


    // MODIFIER UN UTILISATEUR
    #[Route('/update_user/{id}', name: 'app_user_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        UsersRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        // Récupérer l'utilisateur à modifier
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Décoder les données JSON
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Corps de requête invalide'], 400);
        }

        // Mise à jour des champs si présents
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['surname'])) {
            $user->setSurname($data['surname']);
        }

        if (isset($data['role'])) {
            $user->setRoles([$data['role']]);
        }

        if (isset($data['picture'])) {
            $user->setPicture($data['picture']);
        }

        if (isset($data['credit'])) {
            $user->setCredit($data['credit']);
        }

        if (isset($data['status'])) {
            $user->setStatus($data['status']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        // Mettre à jour la date de modification si tu as ce champ
        if (method_exists($user, 'setUpdatedAt')) {
            $user->setUpdatedAt(new \DateTime());
        }

        // Sauvegarder les modifications
        $em->persist($user);
        $em->flush();

        // Réponse JSON
        return $this->json([
            'message' => 'Utilisateur modifié avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'roles' => $user->getRoles(),
                'status' => $user->getStatus(),
                'credit' => $user->getCredit(),
            ]
        ], 200);
    }


    // SUPPRIMER UN UTILISATEUR
    #[Route('/delete_user/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        UsersRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Récupérer l'utilisateur
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Suppression
        $em->remove($user);
        $em->flush();

        // Réponse JSON
        return $this->json([
            'message' => 'Utilisateur supprimé avec succès',
            'id' => $id
        ], 200);
    }



    // MODIFIER LE STATUS D'UN UTILISATEUR
    #[Route('/update_status/{id}', name: 'app_user_update_status', methods: ['PATCH'])]
    public function updateStatus(
        int $id,
        Request $request,
        UsersRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Récupérer l'utilisateur
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Décoder le JSON
        $data = json_decode($request->getContent(), true);

        if (!isset($data['status'])) {
            return $this->json(['error' => 'Paramètre status manquant'], 400);
        }

        // Mettre à jour le status
        $user->setStatus($data['status']);

        // Sauvegarder
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Status modifié avec succès',
            'user' => [
                'id' => $user->getId(),
                'status' => $user->getStatus()
            ]
        ], 200);
    }


}
