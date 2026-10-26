<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private string $stripePublicKey
    ) {}

    #[Route('/cart/checkout', name: 'app_checkout')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

       /** @var \App\Entity\User $user */
$user = $this->getUser();
$cart = $this->cartService->getCart($user);

        if ($cart->getItems()->count() === 0) {
            $this->addFlash('warning', 'Ton panier est vide');
            return $this->redirectToRoute('app_cart_index');
        }

        return $this->render('checkout/checkout_index.html.twig', [
            'cartItems'       => $cart->getItems(),
            'total'           => $cart->getTotal(),
            'stripePublicKey' => $this->stripePublicKey, 
        ]);
    }
}
