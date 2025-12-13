<?php
namespace App\Services;

use App\Entity\PurchaseToken;
use App\Security\UrlValidator;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Handles outbound webhook notifications to merchant systems.
 *
 * Security: Validates webhook URLs to prevent SSRF attacks (CWE-918).
 */
class WebhookService
{
    private const TIMEOUT = 5.0;

    public function __construct(
        private readonly UrlValidator $urlValidator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send webhook notification to merchant.
     *
     * @throws GuzzleException
     * @throws InvalidArgumentException If webhook URL fails security validation
     */
    public function send(PurchaseToken $purchaseToken, bool $success): string
    {
        $webhookUrl = $purchaseToken->getWebhookURL();

        // Security: Validate webhook URL to prevent SSRF attacks (CWE-918)
        if (!$this->urlValidator->isValidWebhookUrl($webhookUrl)) {
            $this->logger->error('SSRF attempt blocked - invalid webhook URL', [
                'url' => $webhookUrl,
                'token_id' => $purchaseToken->getId()
            ]);
            throw new InvalidArgumentException('Invalid webhook URL - must be HTTPS and public.');
        }

        $body = [
            'success'       => $success,
            'token'         => $purchaseToken->getToken(),
            'code'          => $purchaseToken->getId(),
            'price'         => $purchaseToken->getPrice(),
            'transactionID' => $purchaseToken->getTransactionID()
        ];

        $options = [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent'   => 'StripeWebsite-Webhook/1.0'
            ],
            'body'    => json_encode($body),
            'timeout' => self::TIMEOUT,
            // Security: Prevent following redirects to internal URLs
            'allow_redirects' => [
                'max' => 3,
                'protocols' => ['https'],
                'strict' => true
            ]
        ];

        $client = new Guzzle();
        $resp = $client->request('POST', $webhookUrl, $options);

        return (string) $resp->getBody();
    }
}
