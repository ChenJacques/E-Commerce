<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;


class ProfileController extends AbstractController
{
    /**
     * @Route("/api/profile", methods={"GET"})
     */
    public function index(Request $request, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository): JsonResponse
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (!$authorizationHeader) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        $token =  str_replace('Bearer ', '', $authorizationHeader);

        try {
            $user = $jwtManager->parse($token);
        } catch (AuthenticationException $exception) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }


        $userObject = $userRepository->findOneBy(['email' => $user['username']]);

        // return the user's first name and last name in the JSON response
        return new JsonResponse([
            'firstname' => $userObject->getFirstname(),
            'lastname' => $userObject->getLastname(),
            'id' => $userObject->getId(),
            'email' => $userObject->getEmail(),
            'login' => $userObject->getLogin(),
        ]);
    }
}
