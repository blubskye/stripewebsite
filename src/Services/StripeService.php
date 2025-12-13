<?php
namespace App\Services;

use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeService
{
    public function __construct(
        protected string $apiKey,
        protected string $secretKey
    ) {
        Stripe::setApiKey($secretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createSession(array $item, string $token, string $successURL, string $cancelURL): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'client_reference_id'  => $token,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $item['currency'] ?? 'usd',
                        'product_data' => [
                            'name' => $item['name'] ?? 'Purchase',
                            'description' => $item['description'] ?? null,
                        ],
                        'unit_amount' => $item['amount'],
                    ],
                    'quantity' => $item['quantity'] ?? 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successURL,
            'cancel_url'  => $cancelURL
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function findByToken(string $token, ?int $gte = null): ?Session
    {
        $events = Event::all([
            'type' => 'checkout.session.completed',
            'created' => [
                'gte' => ($gte ?? time() - 24 * 60 * 60)
            ]
        ]);

        $session = null;
        foreach ($events->autoPagingIterator() as $event) {
            if ($event->data->object->client_reference_id === $token) {
                $session = $event->data->object;
                break;
            }
        }

        return $session;
    }
}
