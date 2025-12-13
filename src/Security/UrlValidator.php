<?php
namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Validates URLs to prevent Open Redirect and SSRF attacks.
 *
 * Security measures:
 * - Open Redirect (CWE-601): Validates redirect URLs against allowed hosts
 * - SSRF (CWE-918): Blocks internal IPs, localhost, and cloud metadata endpoints
 */
class UrlValidator
{
    /**
     * @param array<string> $allowedRedirectHosts Hosts allowed for redirects (e.g., ['example.com'])
     * @param array<string> $allowedWebhookHosts Hosts allowed for webhooks (empty = any external host)
     */
    public function __construct(
        #[Autowire('%allowed_redirect_hosts%')]
        private readonly array $allowedRedirectHosts = [],
        #[Autowire('%allowed_webhook_hosts%')]
        private readonly array $allowedWebhookHosts = []
    ) {
    }

    /**
     * Validates a redirect URL to prevent Open Redirect attacks (CWE-601).
     *
     * @param string $url The URL to validate
     * @return bool True if the URL is safe for redirects
     */
    public function isValidRedirectUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // Only allow HTTPS in production
        if (!in_array($parsed['scheme'], ['http', 'https'], true)) {
            return false;
        }

        // Check against allowed hosts
        if (empty($this->allowedRedirectHosts)) {
            return false; // Fail-safe: no hosts configured = no redirects allowed
        }

        $host = strtolower($parsed['host']);
        foreach ($this->allowedRedirectHosts as $allowedHost) {
            $allowedHost = strtolower($allowedHost);
            // Allow exact match or subdomain match
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates a webhook URL to prevent SSRF attacks (CWE-918).
     *
     * Blocks:
     * - Private IP ranges (10.x, 172.16-31.x, 192.168.x)
     * - Localhost (127.x, ::1)
     * - Link-local addresses (169.254.x)
     * - Cloud metadata endpoints (169.254.169.254)
     *
     * @param string $url The webhook URL to validate
     * @return bool True if the URL is safe for server-side requests
     */
    public function isValidWebhookUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            return false;
        }

        // Only allow HTTPS for webhooks (security requirement)
        if ($parsed['scheme'] !== 'https') {
            return false;
        }

        $host = $parsed['host'];

        // Block obvious localhost variations
        $blockedHosts = [
            'localhost',
            '127.0.0.1',
            '::1',
            '[::1]',
            '0.0.0.0',
            'metadata.google.internal',
            'metadata.google',
            '169.254.169.254', // AWS/GCP metadata
        ];

        if (in_array(strtolower($host), $blockedHosts, true)) {
            return false;
        }

        // Resolve hostname to IP and validate
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // DNS resolution failed - could be internal hostname
            return false;
        }

        // Block private and reserved IP ranges
        if (!$this->isPublicIp($ip)) {
            return false;
        }

        // If specific webhook hosts are configured, validate against them
        if (!empty($this->allowedWebhookHosts)) {
            $host = strtolower($host);
            foreach ($this->allowedWebhookHosts as $allowedHost) {
                $allowedHost = strtolower($allowedHost);
                if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Checks if an IP address is public (not private/reserved).
     */
    private function isPublicIp(string $ip): bool
    {
        // Use PHP's built-in filter to check for private/reserved ranges
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false;
    }
}
