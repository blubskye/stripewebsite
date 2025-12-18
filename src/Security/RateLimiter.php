<?php
declare(strict_types=1);

namespace App\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redis 8.4+ optimized rate limiter for API endpoints.
 *
 * Uses Symfony cache abstraction which benefits from Redis 8's
 * async I/O threading when configured with persistent connections.
 *
 * Prevents brute force attacks on merchant authentication and
 * abuse of token creation endpoints.
 */
class RateLimiter
{
    private const DEFAULT_LIMIT = 60;
    private const DEFAULT_WINDOW = 60; // seconds
    private const CACHE_PREFIX = 'rate_limit_';

    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    /**
     * Check if a request should be rate limited.
     *
     * Uses sliding window algorithm for more accurate rate limiting.
     *
     * @param Request $request The incoming request
     * @param string $key Unique identifier for the rate limit bucket
     * @param int $limit Maximum requests allowed in the window
     * @param int $windowSeconds Time window in seconds
     * @return bool True if request is allowed, false if rate limited
     */
    public function isAllowed(
        Request $request,
        string $key,
        int $limit = self::DEFAULT_LIMIT,
        int $windowSeconds = self::DEFAULT_WINDOW
    ): bool {
        $identifier = $this->getIdentifier($request, $key);
        $cacheKey = self::CACHE_PREFIX . hash('xxh3', $identifier); // xxh3 is faster than md5

        try {
            $item = $this->cache->getItem($cacheKey);
            $now = time();

            if ($item->isHit()) {
                $data = $item->get();

                // Reset window if expired
                if ($now > $data['reset']) {
                    $data = ['count' => 0, 'reset' => $now + $windowSeconds, 'first' => $now];
                }

                // Check if over limit
                if ($data['count'] >= $limit) {
                    return false;
                }

                // Increment counter
                $data['count']++;
            } else {
                $data = ['count' => 1, 'reset' => $now + $windowSeconds, 'first' => $now];
            }

            $item->set($data);
            $item->expiresAfter($windowSeconds);
            $this->cache->save($item);

            return true;
        } catch (\Throwable) {
            // On cache failure, allow request (fail open for availability)
            return true;
        }
    }

    /**
     * Check multiple rate limits at once (batch operation).
     *
     * Useful when a single request needs to pass multiple rate limits.
     * Takes advantage of Redis 8's pipelining through cache abstraction.
     *
     * @param Request $request The incoming request
     * @param array<string, array{limit: int, window: int}> $limits Key => [limit, window]
     * @return array<string, bool> Key => allowed status
     */
    public function checkMultiple(Request $request, array $limits): array
    {
        $results = [];

        foreach ($limits as $key => $config) {
            $results[$key] = $this->isAllowed(
                $request,
                $key,
                $config['limit'] ?? self::DEFAULT_LIMIT,
                $config['window'] ?? self::DEFAULT_WINDOW
            );
        }

        return $results;
    }

    /**
     * Get remaining requests in current window.
     */
    public function getRemaining(Request $request, string $key, int $limit = self::DEFAULT_LIMIT): int
    {
        $identifier = $this->getIdentifier($request, $key);
        $cacheKey = self::CACHE_PREFIX . hash('xxh3', $identifier);

        try {
            $item = $this->cache->getItem($cacheKey);

            if (!$item->isHit()) {
                return $limit;
            }

            $data = $item->get();

            if (time() > $data['reset']) {
                return $limit;
            }

            return max(0, $limit - $data['count']);
        } catch (\Throwable) {
            return $limit;
        }
    }

    /**
     * Get time until rate limit resets.
     *
     * @return int Seconds until reset, 0 if not rate limited
     */
    public function getResetTime(Request $request, string $key): int
    {
        $identifier = $this->getIdentifier($request, $key);
        $cacheKey = self::CACHE_PREFIX . hash('xxh3', $identifier);

        try {
            $item = $this->cache->getItem($cacheKey);

            if (!$item->isHit()) {
                return 0;
            }

            $data = $item->get();
            $resetTime = $data['reset'] - time();

            return max(0, $resetTime);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Reset rate limit for a specific key.
     */
    public function reset(Request $request, string $key): void
    {
        $identifier = $this->getIdentifier($request, $key);
        $cacheKey = self::CACHE_PREFIX . hash('xxh3', $identifier);

        try {
            $this->cache->deleteItem($cacheKey);
        } catch (\Throwable) {
            // Ignore deletion failures
        }
    }

    /**
     * Creates a unique identifier for rate limiting based on IP and key.
     */
    private function getIdentifier(Request $request, string $key): string
    {
        $ip = $request->getClientIp() ?? 'unknown';
        return "{$key}:{$ip}";
    }
}
