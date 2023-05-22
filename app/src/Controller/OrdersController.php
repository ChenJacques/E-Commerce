<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\User;
use App\Entity\Catalog;
use App\Entity\Cart;
use App\Repository\CartRepository;
use App\Repository\UserRepository;
use App\Repository\OrdersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrdersController extends AbstractController
{
    /**
     * @Route("/api/orders/", methods={"GET"})
     */
    public function orderList(Request $request, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, EntityManagerInterface $entityManager, OrdersRepository $ordersRepository, CartRepository $cartRepository): JsonResponse
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
        $userId = $userObject->getId();

        $orders = $ordersRepository->findBy(['user_id' => $userId]);
    
        if (!$orders) {
            return new JsonResponse([
                'message' => 'No orders'
            ]);
        }

        $orderslist = array();
        foreach($orders as $order) {

            $products = $order->getProducts();
            $productDetails = array();
            foreach ($products as $productId) {
                $product = $entityManager->getRepository(Catalog::class)->find($productId);
                if ($product) {
                    $productDetails[] = array(
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'description' => $product->getDescription(),
                        'photo' => $product->getPhoto(),
                        'price' => $product->getPrice()
                    );
                }
            }  
            $orderslist[] = array(
                'id' => $order->getId(),
                'totalPrice' => $order->getTotalPrice(),
                'creationDate' => $order->getCreationDate(),
                'products' => $productDetails
            );
        }

        return new JsonResponse([
            $orderslist
        ]);

    }
    /**
     * @Route("/api/orders/{id}", methods={"GET"})
     */
    public function order(Request $request, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, EntityManagerInterface $entityManager, OrdersRepository $ordersRepository, CartRepository $cartRepository, $id): JsonResponse
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
        $userId = $userObject->getId();

        $order = $ordersRepository->findOneBy(['id' => $id]);

        if(!$order || $order->getUserId() != $userId ) {
            return new JsonResponse([
                'message' => 'Order not found Or you are not the owner of this order'
            ]);
        }

        $products = $order->getProducts();
        $productDetails = array();
        foreach ($products as $productId) {
            $product = $entityManager->getRepository(Catalog::class)->find($productId);
            if ($product) {
                $productDetails[] = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'photo' => $product->getPhoto(),
                    'price' => $product->getPrice()
                );
            }
        }  
        return new JsonResponse([
            'id' => $order->getId(),
            'totalPrice' => $order->getTotalPrice(),
            'creationDate' => $order->getCreationDate(),
            'products' => $productDetails
        ]);
    }
}
