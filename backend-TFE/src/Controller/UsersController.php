<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse; // formater les reponses en json
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; // hascher le mot de passe
use Symfony\Component\HttpFoundation\Request; // corps de la requete
use Doctrine\ORM\EntityManagerInterface;


use App\Entity\Users;
use App\Repository\AnouncementRepository;
use App\Repository\UsersRepository;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Cache\CacheItemPoolInterface;

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

    // RECUPERER L'UTILISATEUR CONNECTÉ
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(
        #[CurrentUser] ?Users $user,
        AnouncementRepository $anouncementRepo
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $recoveredCount = $anouncementRepo->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.reservedForUser = :user')
            ->andWhere('a.status = 3')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        $thirtyDaysAgo = new \DateTime('-30 days');
        $recoveredThisMonth = (int) $anouncementRepo->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.reservedForUser = :user')
            ->andWhere('a.status = 3')
            ->andWhere('a.recoveredAt >= :limit')
            ->setParameter('user', $user)
            ->setParameter('limit', $thirtyDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'id'                 => $user->getId(),
            'email'              => $user->getEmail(),
            'name'               => $user->getName(),
            'surname'            => $user->getSurname(),
            'roles'              => $user->getRoles(),
            'credit'             => $user->getCredit(),
            'createAt'           => $user->getCreateAt()?->format('d/m/Y'),
            'picture'            => $user->getPicture(),
            'recoveredCount'     => (int) $recoveredCount,
            'recoveredThisMonth' => $recoveredThisMonth,
            'canContact'         => $recoveredThisMonth < 5,
        ]);
    }

    // MODIFIER LE PROFIL DE L'UTILISATEUR CONNECTÉ
    #[Route('/me', name: 'api_me_update', methods: ['PATCH'])]
    public function updateMe(
        Request $request,
        #[CurrentUser] ?Users $user,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name']) && trim($data['name']) !== '') {
            $user->setName(trim(strip_tags($data['name'])));
        }
        if (isset($data['surname']) && trim($data['surname']) !== '') {
            $user->setSurname(trim(strip_tags($data['surname'])));
        }
        if (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $user->setEmail(strtolower(trim($data['email'])));
        }

        $em->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'name'    => $user->getName(),
            'surname' => $user->getSurname(),
            'email'   => $user->getEmail(),
        ]);
    }

    // CHANGER LE MOT DE PASSE DE L'UTILISATEUR CONNECTÉ
    #[Route('/me/change-password', name: 'api_me_change_password', methods: ['POST'])]
    public function changeMyPassword(
        Request $request,
        #[CurrentUser] ?Users $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $oldPassword = $data['oldPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        if (!$oldPassword || !$newPassword) {
            return $this->json(['error' => 'Champs manquants'], 400);
        }

        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            return $this->json(['error' => 'Ancien mot de passe incorrect'], 400);
        }

        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            return $this->json(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères avec lettres et chiffres'], 422);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        return $this->json(['message' => 'Mot de passe modifié avec succès']);
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


    // ENVOI DU CODE DE RÉINITIALISATION PAR EMAIL
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        CacheItemPoolInterface $cache
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = strtolower(trim($data['email'] ?? ''));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse email invalide.'], 400);
        }

        $user = $usersRepository->findOneBy(['email' => $email]);

        // Même réponse que le compte existe ou non (évite de révéler les emails inscrits)
        $genericResponse = [
            'message'   => 'Si un compte existe pour cette adresse, un code a été envoyé.',
            'expiresIn' => self::RESET_CODE_TTL,
        ];

        if (!$user) {
            return $this->json($genericResponse, 200);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = new \DateTime('+' . self::RESET_CODE_TTL . ' seconds');

        $user->setResetCode($code);
        $user->setResetCodeExpiresAt($expiresAt);
        $em->flush();

        // Nouveau code → on remet à zéro le compteur d'essais
        $cache->deleteItem($this->resetAttemptsKey($email));

        $mailerFrom = $_ENV['MAILER_FROM'] ?? 'noreply@partagegratuit.be';

        $emailMessage = (new Email())
            ->from($mailerFrom)
            ->to($email)
            ->subject('Code de réinitialisation — PartageGratuit')
            ->text(
                "Bonjour,\n\n" .
                "Votre code de réinitialisation de mot de passe est :\n\n" .
                "  $code\n\n" .
                "Ce code expire dans " . (self::RESET_CODE_TTL / 60) . " minutes.\n" .
                "Si vous n'avez pas demandé de réinitialisation, ignorez cet email.\n\n" .
                "— L'équipe PartageGratuit"
            );

        try {
            $mailer->send($emailMessage);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Échec de l\'envoi de l\'email. Réessayez plus tard.'], 500);
        }

        // Le code n'est jamais renvoyé dans la réponse : il n'est connu que par email
        return $this->json($genericResponse, 200);
    }

    private const MAX_RESET_ATTEMPTS = 5;
    private const RESET_CODE_TTL     = 600; // durée de validité du code, en secondes (10 min)

    private function resetAttemptsKey(string $email): string
    {
        return 'reset_attempts_' . sha1($email);
    }

    /**
     * Vérifie le code de réinitialisation (essais limités, code, expiration).
     * Renvoie l'utilisateur si le code est bon, sinon la réponse d'erreur à renvoyer.
     * Le champ "reason" (invalid / expired / too_many) permet au frontend de réagir
     * sans dépendre du texte du message.
     */
    private function checkResetCode(
        string $email,
        string $code,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        CacheItemPoolInterface $cache
    ): Users|JsonResponse {
        // Limite les essais pour empêcher de deviner le code à 6 chiffres
        $attempts = $cache->getItem($this->resetAttemptsKey($email));
        $count = (int) $attempts->get();

        if ($count >= self::MAX_RESET_ATTEMPTS) {
            return $this->json(['error' => 'Trop de tentatives. Demandez un nouveau code.', 'reason' => 'too_many'], 429);
        }

        $user = $usersRepository->findOneBy(['email' => $email]);

        if (!$user || !$user->getResetCode()) {
            return $this->json(['error' => 'Code invalide.', 'reason' => 'invalid'], 400);
        }

        if (!hash_equals($user->getResetCode(), $code)) {
            $attempts->set($count + 1);
            $attempts->expiresAfter(self::RESET_CODE_TTL);
            $cache->save($attempts);

            if ($count + 1 >= self::MAX_RESET_ATTEMPTS) {
                // Code grillé : l'utilisateur doit en redemander un
                $user->setResetCode(null);
                $user->setResetCodeExpiresAt(null);
                $em->flush();
                return $this->json(['error' => 'Trop de tentatives. Demandez un nouveau code.', 'reason' => 'too_many'], 429);
            }

            $left = self::MAX_RESET_ATTEMPTS - $count - 1;
            return $this->json([
                'error'  => sprintf('Code invalide. Il vous reste %d essai%s.', $left, $left > 1 ? 's' : ''),
                'reason' => 'invalid',
            ], 400);
        }

        if (!$user->getResetCodeExpiresAt() || $user->getResetCodeExpiresAt() < new \DateTime()) {
            return $this->json(['error' => 'Code expiré. Veuillez recommencer.', 'reason' => 'expired'], 400);
        }

        return $user;
    }

    // VÉRIFICATION DU CODE (étape 2 du formulaire, avant de choisir le nouveau mot de passe)
    #[Route('/verify-reset-code', name: 'verify_reset_code', methods: ['POST'])]
    public function verifyResetCode(
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        CacheItemPoolInterface $cache
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true);
        $email = strtolower(trim($data['email'] ?? ''));
        $code  = trim($data['code'] ?? '');

        if (!$email || !$code) {
            return $this->json(['error' => 'Champs manquants.'], 400);
        }

        $result = $this->checkResetCode($email, $code, $usersRepository, $em, $cache);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->json([
            'valid'     => true,
            'expiresIn' => max(0, $result->getResetCodeExpiresAt()->getTimestamp() - time()),
        ]);
    }

    // RÉINITIALISATION DU MOT DE PASSE AVEC LE CODE
    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        CacheItemPoolInterface $cache
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email    = strtolower(trim($data['email'] ?? ''));
        $code     = trim($data['code'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$code || !$password) {
            return $this->json(['error' => 'Champs manquants.'], 400);
        }

        $user = $this->checkResetCode($email, $code, $usersRepository, $em, $cache);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères avec lettres et chiffres.'], 422);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setResetCode(null);
        $user->setResetCodeExpiresAt(null);
        $em->flush();
        $cache->deleteItem($this->resetAttemptsKey($email));

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.'], 200);
    }

    // LISTE DES UTILISATEURS
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/users', name: 'app_users', methods: ['GET'])]
    public function index(UsersRepository $userRepository): JsonResponse
    {
        // Récupération de tous les utilisateurs
        $users = $userRepository->findAll();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'roles' => $user->getRoles(),
                'status' => $user->getStatus(),
                'credit' => $user->getCredit(),
            ];
        }

        return $this->json([
            "success" => true,
            "data" => $data
        ]);
    }


    // MODIFIER UN UTILISATEUR
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
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
