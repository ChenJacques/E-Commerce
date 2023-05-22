<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class LoginController extends AbstractController
{

    /**
     * @Route("/api/login", methods={"POST"})
     */
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $json = json_decode($request->getContent(), true);
        $username = $json['username'];
        $password = $json['password'];


        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['login' => $username]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid login or password'], Response::HTTP_UNAUTHORIZED);
        }

        // Verify the password
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['message' => 'Invalid login or password'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $jwtManager->create($user);
        return new JsonResponse(['token' => $token]);
    }

}
