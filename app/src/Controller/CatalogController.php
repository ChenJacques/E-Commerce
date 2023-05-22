<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Entity\User;
use App\Repository\CatalogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CatalogController extends AbstractController
{
    /**
     * @Route("/api/products", methods={"GET"})
     */
    public function index(CatalogRepository $catalogRepository): JsonResponse
    {
        $products = $catalogRepository->findAll();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'photo' => $product->getPhoto(),
                'price' => $product->getPrice(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * @Route("/api/products/{id}", methods={"GET"})
     */
    public function show(Catalog $product = null): JsonResponse
    {
        if (!$product) {
            return new JsonResponse([
                'message' => 'Product not found'
            ], 404);
        }

        return new JsonResponse([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'photo' => $product->getPhoto(),
            'price' => $product->getPrice(),
        ]);
    }
    
     /**
     * @Route("/api/products", methods={"POST"})
     */
    public function new(Request $request, CatalogRepository $catalogRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository): JsonResponse
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

        $data = json_decode($request->getContent(), true);

        $product = new Catalog();
        $product->setName($data['name']);
        $product->setDescription($data['description']);
        $product->setPhoto($data['photo']);
        $product->setPrice($data['price']);

        $catalogRepository->save($product);


        return new JsonResponse(['message' => 'Product created successfully'], Response::HTTP_CREATED);

    }

    /**
     * @Route("/api/products/{id}", methods={"PATCH"})
     */
    public function edit(Request $request, Catalog $product = null, CatalogRepository $catalogRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository): JsonResponse
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

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $product->setName($data['name'] ?? $product->getName());
        $product->setDescription($data['description'] ?? $product->getDescription());
        $product->setPhoto($data['photo'] ?? $product->getPhoto());
        $product->setPrice($data['price'] ?? $product->getPrice());

        $catalogRepository->save($product);

        return new JsonResponse(['message' => 'Product updated successfully'], Response::HTTP_OK);
    }

    /**
     * @Route("/api/products/{id}", methods={"DELETE"})
     */
    public function delete(Request $request, 
    Catalog $product = null, CatalogRepository $catalogRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository): Response
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

        if (!$product) {
            return new JsonResponse(['message' => 'Product not found'], 404);
        }
        
        $catalogRepository->remove($product, true);

        return new JsonResponse(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }


}
