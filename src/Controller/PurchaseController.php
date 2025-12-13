<?php
namespace App\Controller;

use App\Entity\PurchaseToken;
use App\Security\UrlValidator;
use App\Services\StripeService;
use App\Services\WebhookService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PurchaseController extends Controller
{
    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        private readonly UrlValidator $urlValidator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
        parent::__construct($em, $logger);
    }

    #[Route('/purchase/checkout/stripe/{token}', name: 'purchase_checkout_stripe', methods: ['POST'])]
    public function checkoutAction(string $token, Request $request, StripeService $stripeService): Response
    {
        // Security: Validate CSRF token to prevent cross-site request forgery (CWE-352)
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('checkout_' . $token, $submittedToken))) {
            $this->logger->warning('Invalid CSRF token in checkout action.');
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $request->getSession()->set('token', $token);
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);
        $session       = $stripeService->createSession(
            [
                'name'        => 'Premium Server Upgrade',
                'description' => $purchaseToken->getDescription(),
                'amount'      => $purchaseToken->getPrice(),
                'currency'    => 'usd',
                'quantity'    => 1
            ],
            $token,
            $this->generateUrl('purchase_complete', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->generateUrl('purchase_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $this->render('purchase/checkout.html.twig', [
            'stripeAPIKey'    => $this->getParameter('stripeApiKey'),
            'stripeSessionID' => $session->id
        ]);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    #[Route('/purchase/complete', name: 'purchase_complete')]
    public function completeAction(Request $request, WebhookService $webhookService, StripeService $stripeService): Response
    {
        $session = $request->getSession();
        $token   = $session->get('token');
        $session->remove('token');
        if (!$token) {
            $this->logger->error('No token found in session in complete action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken = $this->getPurchaseTokenOrThrow($token);

        $stripeSession = $stripeService->findByToken($token);
        if (!$stripeSession) {
            $this->logger->error('Stripe session not found in complete action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken
            ->setIsSuccess(true)
            ->setIsPurchased(true)
            ->setStripeID($stripeSession->id)
            ->setStripeCustomer($stripeSession->customer)
            ->setStripePaymentIntent($stripeSession->payment_intent);
        $this->em->flush();

        try {
            $webhookService->send($purchaseToken, true);
        } catch (Exception $e) {
            $purchaseToken->setIsClientFailure(true);
            $this->em->flush();

            return $this->render('purchase/failure.html.twig');
        }

        // Security: Validate redirect URL to prevent open redirect attacks (CWE-601)
        $url = $purchaseToken->getSuccessURL();
        if (!$this->urlValidator->isValidRedirectUrl($url)) {
            $this->logger->warning('Invalid success redirect URL blocked: ' . $url);
            return $this->render('purchase/success.html.twig', [
                'message' => 'Payment completed successfully!'
            ]);
        }

        // Append token to URL for verification
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator . 't=' . $purchaseToken->getToken();

        return new RedirectResponse($url);
    }

    #[Route('/purchase/cancel', name: 'purchase_cancel')]
    public function cancelAction(Request $request): Response
    {
        $session = $request->getSession();
        $token   = $session->get('token');
        $session->remove('token');
        if (!$token) {
            $this->logger->error('No token found in session in cancel action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken = $this->getPurchaseTokenOrThrow($token);

        // Security: Validate redirect URL to prevent open redirect attacks (CWE-601)
        $url = $purchaseToken->getCancelURL();
        if (!$this->urlValidator->isValidRedirectUrl($url)) {
            $this->logger->warning('Invalid cancel redirect URL blocked: ' . $url);
            return $this->render('purchase/cancelled.html.twig', [
                'message' => 'Payment was cancelled.'
            ]);
        }

        return new RedirectResponse($url);
    }

    #[Route('/purchase/{token}', name: 'purchase', methods: ['GET'])]
    public function indexAction(string $token): Response
    {
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);

        return $this->render('purchase/index.html.twig', [
            'token'      => $purchaseToken,
            'action'     => $this->generateUrl('purchase_checkout_stripe', ['token' => $token]),
            'csrf_token' => $this->csrfTokenManager->getToken('checkout_' . $token)->getValue()
        ]);
    }

    private function getPurchaseTokenOrThrow(string $token): PurchaseToken
    {
        $purchaseToken = $this->em->getRepository(PurchaseToken::class)->findByToken($token);
        if (!$purchaseToken || $purchaseToken->isPurchased()) {
            throw $this->createNotFoundException();
        }

        return $purchaseToken;
    }
}
