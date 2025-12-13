<?php
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Base controller with common dependencies.
 */
class Controller extends AbstractController
{
    use LoggerAwareTrait;

    public function __construct(
        protected EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->setLogger($logger);
    }
}
