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

    private const BATCH_SIZE = 50;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paymentTokens = $this->em->getRepository(PurchaseToken::class)->findByClientFailure();
        $processed = 0;

        foreach ($paymentTokens as $purchaseToken) {
            $this->process($purchaseToken, $output);
            $processed++;

            // Batch flush every BATCH_SIZE records for efficiency
            if ($processed % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear(PurchaseToken::class);
            }
        }

        // Final flush for remaining records
        $this->em->flush();

        $output->writeln(sprintf('Processed %d failed webhooks.', $processed));
        return Command::SUCCESS;
    }

    protected function process(PurchaseToken $purchaseToken, OutputInterface $output): void
    {
        $output->writeln('Processing ' . $purchaseToken->getToken());

        try {
            $this->webhookService->send($purchaseToken, true);
            $purchaseToken->setIsClientFailure(false);
        } catch (Exception $e) {
            $output->writeln('Error: ' . $e->getMessage());
        }
    }
}
