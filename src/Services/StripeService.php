<?php
declare(strict_types=1);

namespace App\Services;

use Psr\Cache\CacheItemPoolInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'stripe_session_';

    public function __construct(
        protected string $apiKey,
        protected string $secretKey,
        protected ?CacheItemPoolInterface $cache = null
    ) {
        Stripe::setApiKey($secretKey);
    }

    /**
     * @param array<string, mixed> $item
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
        // Check cache first to avoid expensive API iteration
        if ($this->cache !== null) {
            $cacheKey = self::CACHE_PREFIX . md5($token);
            $cacheItem = $this->cache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        }

        $events = Event::all([
            'type' => 'checkout.session.completed',
            'created' => [
                'gte' => ($gte ?? time() - 24 * 60 * 60)
            ],
            'limit' => 100 // Limit initial fetch for better performance
        ]);

        $session = null;
        foreach ($events->autoPagingIterator() as $event) {
            if ($event->data->object->client_reference_id === $token) {
                $session = $event->data->object;
                break;
            }
        }

        // Cache the result
        if ($this->cache !== null && $session !== null) {
            $cacheItem = $this->cache->getItem(self::CACHE_PREFIX . md5($token));
            $cacheItem->set($session);
            $cacheItem->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);
        }

        return $session;
    }
}
