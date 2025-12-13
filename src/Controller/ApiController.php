<?php
namespace App\Controller;

use App\Entity\Merchant;
use App\Entity\PurchaseToken;
use App\Security\RateLimiter;
use App\Security\UrlValidator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends Controller
{
    /**
     * Minimum price in cents (e.g., $0.50 minimum for Stripe)
     */
    private const MIN_PRICE = 50;

    /**
     * Maximum price in cents ($10,000 max)
     */
    private const MAX_PRICE = 1000000;

    /**
     * Rate limit: requests per minute for authentication
     */
    private const AUTH_RATE_LIMIT = 10;

    /**
     * Rate limit: token creations per hour
     */
    private const TOKEN_RATE_LIMIT = 100;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly RateLimiter $rateLimiter,
        private readonly UrlValidator $urlValidator
    ) {
        parent::__construct($em, $logger);
    }

    #[Route('/api/v1/token', name: 'api_token', methods: ['POST'])]
    public function tokenAction(Request $request): JsonResponse
    {
        // Security: Rate limiting to prevent abuse (CWE-799)
        if (!$this->rateLimiter->isAllowed($request, 'api_token', self::TOKEN_RATE_LIMIT, 3600)) {
            return new JsonResponse(
                ['error' => 'Rate limit exceeded. Try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $values = $this->getJsonPayload($request);

        // Validate required fields
        $requiredFields = ['transactionID', 'price', 'description', 'successURL', 'cancelURL', 'failureURL', 'webhookURL'];
        if (!array_all($requiredFields, fn(string $field): bool => !empty($values[$field]))) {
            return new JsonResponse(['error' => 'Missing required fields.'], Response::HTTP_BAD_REQUEST);
        }

        // Security: Validate price to prevent manipulation (CWE-20)
        $price = $values['price'];
        if (!is_int($price) && !is_numeric($price)) {
            return new JsonResponse(['error' => 'Price must be a number.'], Response::HTTP_BAD_REQUEST);
        }
        $price = (int) $price;
        if ($price < self::MIN_PRICE || $price > self::MAX_PRICE) {
            return new JsonResponse(
                ['error' => sprintf('Price must be between %d and %d cents.', self::MIN_PRICE, self::MAX_PRICE)],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Security: Validate URLs to prevent open redirect and SSRF
        foreach (['successURL', 'cancelURL', 'failureURL'] as $urlField) {
            if (!$this->urlValidator->isValidRedirectUrl($values[$urlField])) {
                return new JsonResponse(
                    ['error' => "Invalid {$urlField} - must be HTTPS and from an allowed domain."],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        if (!$this->urlValidator->isValidWebhookUrl($values['webhookURL'])) {
            return new JsonResponse(
                ['error' => 'Invalid webhookURL - must be HTTPS and publicly accessible.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->verifyMerchant($request);

        $token = (new PurchaseToken())
            ->setTransactionID((string) $values['transactionID'])
            ->setPrice($price)
            ->setDescription((string) $values['description'])
            ->setSuccessURL($values['successURL'])
            ->setCancelURL($values['cancelURL'])
            ->setFailureURL($values['failureURL'])
            ->setWebhookURL($values['webhookURL']);
        $this->em->persist($token);
        $this->em->flush();

        return new JsonResponse(['token' => $token->getToken()]);
    }

    #[Route('/api/v1/verify', name: 'api_verify', methods: ['POST'])]
    public function verifyAction(Request $request): JsonResponse
    {
        // Security: Rate limiting to prevent enumeration attacks
        if (!$this->rateLimiter->isAllowed($request, 'api_verify', self::AUTH_RATE_LIMIT, 60)) {
            return new JsonResponse(
                ['error' => 'Rate limit exceeded. Try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $values = $this->getJsonPayload($request);

        $requiredFields = ['token', 'code', 'transactionID', 'price'];
        if (!array_all($requiredFields, fn(string $field): bool => !empty($values[$field]))) {
            return new JsonResponse(['valid' => false]);
        }

        $this->verifyMerchant($request);

        $purchaseToken = $this->em->getRepository(PurchaseToken::class)->findByID($values['code']);

        // Security: Use hash_equals for timing-safe comparison (CWE-208)
        $tokenValid = $purchaseToken !== null
            && hash_equals($purchaseToken->getToken(), (string) $values['token'])
            && $purchaseToken->getPrice() === (int) $values['price'];

        return new JsonResponse(['valid' => $tokenValid]);
    }

    /**
     * Verifies merchant credentials with timing-attack protection.
     *
     * Security: Uses constant-time comparison to prevent timing attacks (CWE-208).
     */
    protected function verifyMerchant(Request $request): void
    {
        // Security: Rate limiting on authentication to prevent brute force
        if (!$this->rateLimiter->isAllowed($request, 'api_auth', self::AUTH_RATE_LIMIT, 60)) {
            throw $this->createAccessDeniedException('Rate limit exceeded.');
        }

        $clientID     = $request->headers->get('X-Client-ID');
        $clientSecret = $request->headers->get('X-Client-Secret');

        if (!$clientID || !$clientSecret) {
            throw $this->createAccessDeniedException();
        }

        $merchant = $this->em->getRepository(Merchant::class)->findByID($clientID);

        // Security: Always perform password_verify to prevent timing attacks (CWE-208)
        // Even if merchant doesn't exist, we verify against a dummy hash
        // This ensures consistent response time regardless of merchant existence
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuOBKJvGi/c3GJzKs6yPz2KFwOBFpvO6S';
        $hashToVerify = $merchant?->getPassword() ?? $dummyHash;

        $passwordValid = password_verify($clientSecret, $hashToVerify);

        if (!$merchant || !$passwordValid) {
            $this->logger->warning('Failed merchant authentication attempt', [
                'client_id' => $clientID,
                'ip' => $request->getClientIp()
            ]);
            throw $this->createAccessDeniedException();
        }
    }

    protected function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if (!$content) {
            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
