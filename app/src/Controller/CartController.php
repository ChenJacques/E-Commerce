<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Entity\User;
use App\Entity\Cart;
use App\Entity\Orders;
use App\Repository\OrdersRepository;
use App\Repository\CartRepository;
use App\Repository\CatalogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{


    /**
     * @Route("/api/carts/validate", methods={"POST"})
     */
    public function validateCart(Request $request, CartRepository $cartRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, EntityManagerInterface $entityManager, OrdersRepository $ordersRepository): JsonResponse
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

        $cart = $cartRepository->findOneBy(['userId' => $userId]);
    
        if (!$cart) {
            return new JsonResponse([
                'message' => 'Cart is empty'
            ]);
        }

        $price = 0;
        $products = $cart->getProducts();
        foreach ($products as $productId) {
            $product = $entityManager->getRepository(Catalog::class)->find($productId);
            if ($product) {
                $price += $product->getPrice();
            }
        }

        $order = new Orders();
        $order->setUserId($userId);
        $order->setTotalPrice($price);
        $order->setCreationDate(new \DateTime());
        $order->setProducts($products);
        $ordersRepository->save($order);

        $cartRepository->remove($cart, true);
        
        return new JsonResponse([
            'message' => 'Order created successfully'
        ]);
    }

    /**
     * @Route("/api/carts/{id}", methods={"POST"})
     */
    public function addProduct(Request $request, CartRepository $cartRepository,CatalogRepository $catalogRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, $id): JsonResponse
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

        $cart = $cartRepository->findOneBy(['userId' => $userId]);
    
        if (!$cart) {
            $cart = new Cart();
            $cart->setUserId($userId);
            $cart->setProducts([]);
        }

        $product = $catalogRepository->find($id);
        if (!$product) {
            return new JsonResponse([
                'message' => 'Product not found'
            ], 404);
        }
    
        $products = $cart->getProducts();
        $products[] = intval($id);
        $cart->setProducts($products);
        $cartRepository->save($cart);

        return new JsonResponse([
            'message' => 'Product added to cart successfully'
        ]);
    }

    /**
     * @Route("/api/carts/{id}", methods={"DELETE"})
     */
    public function deleteProduct(Request $request, CartRepository $cartRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, $id): JsonResponse
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

        $cart = $cartRepository->findOneBy(['userId' => $userId]);
    
        if (!$cart) {
            return new JsonResponse([
                'message' => 'Cart is empty'
            ]);
        }

        $products = $cart->getProducts();
        $found = false;
        foreach ($products as $key => $value) {
            if ($value == intval($id)) {
                unset($products[$key]);
                $found = true;
                break;
            }
        }

        if ($found) {
            $cart->setProducts($products);
            $cartRepository->save($cart);
        }
        
        return new JsonResponse([
            'message' => 'Product removed from cart successfully'
        ]);
    }

    /**
     * @Route("/api/carts", methods={"GET"})
     */
    public function getProduct(Request $request, CartRepository $cartRepository, JWTTokenManagerInterface $jwtManager, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
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

        $cart = $cartRepository->findOneBy(['userId' => $userId]);
    
        if (!$cart) {
            return new JsonResponse([
                'message' => 'Cart is empty'
            ]);
        }

        $products = $cart->getProducts();
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
            $productDetails
        ]);
    }

}
