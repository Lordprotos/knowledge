<?php

namespace App\Service;

use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\User;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Prépare le paiement hébergé par Stripe et récupère les sessions à vérifier. */
final class StripeCheckoutService
{
    public function __construct(private string $secretKey, private UrlGeneratorInterface $urls) {}

    public function create(User $user, Curriculum|Lesson $product): string
    {
        // Le projet accepte uniquement les clés du mode test pour créer un paiement.
        if (!str_starts_with($this->secretKey, 'sk_test_')) {
            throw new \LogicException('Ajoute une clé secrète Stripe de test dans .env.local.');
        }
        $type = $product instanceof Curriculum ? 'curriculum' : 'lesson';
        $session = (new StripeClient($this->secretKey))->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $user->getEmail(),
            // Ces références servent au contrôle du client et du produit lors du retour.
            'client_reference_id' => (string) $user->getId(),
            'metadata' => ['product_type' => $type, 'product_id' => (string) $product->getId()],
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'eur',
                        // Le prix vient du catalogue côté serveur et est exprimé en centimes.
                        'unit_amount' => $product->getPriceCents(),
                        'product_data' => ['name' => $product->getTitle()],
                    ],
                ],
            ],
            // Stripe remplace le marqueur par l’identifiant de la session Checkout.
            'success_url' =>
                $this->urls->generate(
                    'app_stripe_success',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urls->generate(
                'app_stripe_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ]);
        return $session->url;
    }

    /** Interroge Stripe : une simple URL de succès ne prouve pas le paiement. */
    public function retrieve(string $sessionId): \Stripe\Checkout\Session
    {
        return (new StripeClient($this->secretKey))->checkout->sessions->retrieve($sessionId);
    }
}
