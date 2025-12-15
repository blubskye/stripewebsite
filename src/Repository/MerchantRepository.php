<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Merchant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Merchant>
 */
class MerchantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Merchant::class);
    }

    public function findByID(int|string $id): ?Merchant
    {
        return $this->findOneBy(['id' => $id]);
    }
}
