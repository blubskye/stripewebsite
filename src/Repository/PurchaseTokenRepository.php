<?php
namespace App\Repository;

use App\Entity\PurchaseToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PurchaseTokenRepository extends ServiceEntityRepository
{
    const TOKEN_EXPIRATION = 43200; // 12 hours

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseToken::class);
    }

    public function findByID(int|string $id): ?PurchaseToken
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByToken(string $token): ?PurchaseToken
    {
        $purchaseToken = $this->findOneBy(['token' => $token]);
        if (!$purchaseToken) {
            return null;
        }

        $now  = new DateTime();
        $diff = $now->getTimestamp() - $purchaseToken->getDateCreated()->getTimestamp();
        if ($diff > self::TOKEN_EXPIRATION) {
            return null;
        }

        return $purchaseToken;
    }

    /**
     * @return PurchaseToken[]
     */
    public function findByClientFailure(): array
    {
        return $this->findBy(['isClientFailure' => true]);
    }
}
