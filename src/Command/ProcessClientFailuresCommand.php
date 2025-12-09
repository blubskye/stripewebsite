<?php
namespace App\Command;

use App\Entity\PurchaseToken;
use App\Services\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:payments:process-client-failures',
    description: 'Process and retry failed webhook notifications'
)]
class ProcessClientFailuresCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WebhookService $webhookService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paymentTokens = $this->em->getRepository(PurchaseToken::class)->findByClientFailure();
        foreach ($paymentTokens as $purchaseToken) {
            $this->process($purchaseToken, $output);
        }

        return Command::SUCCESS;
    }

    protected function process(PurchaseToken $purchaseToken, OutputInterface $output): void
    {
        $output->writeln('Processing ' . $purchaseToken->getToken());

        try {
            $this->webhookService->send($purchaseToken, true);
            $purchaseToken->setIsClientFailure(false);
            $this->em->flush();
        } catch (Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
        }
    }
}
