<?php
namespace App\Controller;

use App\Entity\Merchant;
use App\Entity\PurchaseToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends Controller
{
    #[Route('/api/v1/token', name: 'api_token', methods: ['POST'])]
    public function tokenAction(Request $request): JsonResponse
    {
        $values = $this->getJsonPayload($request);
        if (empty($values['transactionID'])
            || empty($values['price'])
            || empty($values['description'])
            || empty($values['successURL'])
            || empty($values['cancelURL'])
            || empty($values['failureURL'])
            || empty($values['webhookURL'])
        ) {
            throw $this->createNotFoundException();
        }

        $this->verifyMerchant($request);

        $token = (new PurchaseToken())
            ->setTransactionID($values['transactionID'])
            ->setPrice($values['price'])
            ->setDescription($values['description'])
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
        $values = $this->getJsonPayload($request);
        if (empty($values['token'])
            || empty($values['code'])
            || empty($values['transactionID'])
            || empty($values['price'])
        ) {
            return new JsonResponse([
                'valid' => false
            ]);
        }

        $this->verifyMerchant($request);

        $purchaseToken = $this->em->getRepository(PurchaseToken::class)->findByID($values['code']);
        if (!$purchaseToken
            || $purchaseToken->getToken() !== $values['token']
            || $purchaseToken->getPrice() !== $values['price']
        ) {
            return new JsonResponse([
                'valid' => false
            ]);
        }

        return new JsonResponse([
            'valid' => true
        ]);
    }

    protected function verifyMerchant(Request $request): void
    {
        $clientID     = $request->headers->get('X-Client-ID');
        $clientSecret = $request->headers->get('X-Client-Secret');
        if (!$clientID || !$clientSecret) {
            throw $this->createAccessDeniedException();
        }
        $merchant = $this->em->getRepository(Merchant::class)->findByID($clientID);
        if (!$merchant || !password_verify($clientSecret, $merchant->getPassword())) {
            throw $this->createAccessDeniedException();
        }
    }

    protected function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if (!$content) {
            return [];
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }
}
