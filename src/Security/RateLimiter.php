<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Simple rate limiter for API endpoints.
 *
 * Prevents brute force attacks on merchant authentication and
 * abuse of token creation endpoints.
 */
class RateLimiter
{
    private const DEFAULT_LIMIT = 60;
    private const DEFAULT_WINDOW = 60; // seconds

    public function __construct(
        private readonly AdapterInterface $cache
    ) {
    }

    /**
     * Check if a request should be rate limited.
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
        $cacheKey = 'rate_limit_' . md5($identifier);

        try {
            $item = $this->cache->getItem($cacheKey);
            $data = $item->isHit() ? $item->get() : ['count' => 0, 'reset' => time() + $windowSeconds];

            // Reset window if expired
            if (time() > $data['reset']) {
                $data = ['count' => 0, 'reset' => time() + $windowSeconds];
            }

            // Check if over limit
            if ($data['count'] >= $limit) {
                return false;
            }

            // Increment counter
            $data['count']++;
            $item->set($data);
            $item->expiresAfter($windowSeconds);
            $this->cache->save($item);

            return true;
        } catch (\Exception) {
            // On cache failure, allow request (fail open for availability)
            return true;
        }
    }

    /**
     * Get remaining requests in current window.
     */
    public function getRemaining(Request $request, string $key, int $limit = self::DEFAULT_LIMIT): int
    {
        $identifier = $this->getIdentifier($request, $key);
        $cacheKey = 'rate_limit_' . md5($identifier);

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
        } catch (\Exception) {
            return $limit;
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
