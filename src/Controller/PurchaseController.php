<?php
namespace App\Controller;

use App\Entity\PurchaseToken;
use App\Services\StripeService;
use App\Services\WebhookService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PurchaseController extends Controller
{
    #[Route('/purchase/checkout/stripe/{token}', name: 'purchase_checkout_stripe')]
    public function checkoutAction(string $token, Request $request, StripeService $stripeService): Response
    {
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
        if (!$purchaseToken) {
            throw $this->createNotFoundException();
        }

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

        $url = $purchaseToken->getSuccessURL();
        if (stripos($url, '?') !== false) {
            $url .= '&t=' . $purchaseToken->getToken();
        } else {
            $url .= '?t=' . $purchaseToken->getToken();
        }

        return new RedirectResponse($url);
    }

    #[Route('/purchase/cancel', name: 'purchase_cancel')]
    public function cancelAction(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $token   = $session->get('token');
        $session->remove('token');
        if (!$token) {
            $this->logger->error('No token found in session in cancel action.');
            throw $this->createNotFoundException();
        }

        $purchaseToken = $this->getPurchaseTokenOrThrow($token);
        if (!$purchaseToken) {
            throw $this->createNotFoundException();
        }

        return new RedirectResponse($purchaseToken->getCancelURL());
    }

    #[Route('/purchase/{token}', name: 'purchase', methods: ['GET'])]
    public function indexAction(string $token): Response
    {
        $purchaseToken = $this->getPurchaseTokenOrThrow($token);

        return $this->render('purchase/index.html.twig', [
            'token'  => $purchaseToken,
            'action' => $this->generateUrl('purchase_checkout_stripe', ['token' => $token])
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
