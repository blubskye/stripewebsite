<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\PurchaseToken;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PurchaseToken>
 */
class PurchaseTokenRepository extends ServiceEntityRepository
{
    private const TOKEN_EXPIRATION = 43200; // 12 hours

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
        // Use DQL with expiration check in database for efficiency
        $expirationTime = new DateTimeImmutable(sprintf('-%d seconds', self::TOKEN_EXPIRATION));

        return $this->createQueryBuilder('pt')
            ->where('pt.token = :token')
            ->andWhere('pt.dateCreated > :expiration')
            ->setParameter('token', $token)
            ->setParameter('expiration', $expirationTime)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<PurchaseToken>
     */
    public function findByClientFailure(): array
    {
        return $this->findBy(['isClientFailure' => true]);
    }
}
