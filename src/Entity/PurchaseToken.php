<?php
namespace App\Entity;

use App\Repository\PurchaseTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

#[ORM\Entity(repositoryClass: PurchaseTokenRepository::class)]
#[ORM\Table(name: 'purchase_token')]
#[ORM\Index(columns: ['token'], name: 'idx_token')]
class PurchaseToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 32)]
    protected string $token;

    #[ORM\Column(type: 'string', length: 32)]
    protected string $transactionID;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $price;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $isSuccess = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $isClientFailure;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $successURL;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $cancelURL;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $failureURL;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $webhookURL;

    #[ORM\Column(type: 'boolean')]
    protected bool $isPurchased;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $stripeID = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $stripePaymentIntent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $stripeCustomer = null;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated     = new DateTime();
        $this->isPurchased     = false;
        $this->isClientFailure = false;
        $this->token           = bin2hex(random_bytes(16));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getTransactionID(): string
    {
        return $this->transactionID;
    }

    public function setTransactionID(string $transactionID): self
    {
        $this->transactionID = $transactionID;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->isSuccess;
    }

    public function setIsSuccess(bool $isSuccess): self
    {
        $this->isSuccess = $isSuccess;

        return $this;
    }

    public function isClientFailure(): bool
    {
        return $this->isClientFailure;
    }

    public function setIsClientFailure(bool $isClientFailure): self
    {
        $this->isClientFailure = $isClientFailure;

        return $this;
    }

    public function getSuccessURL(): string
    {
        return $this->successURL;
    }

    public function setSuccessURL(string $successURL): self
    {
        $this->successURL = $successURL;

        return $this;
    }

    public function getCancelURL(): string
    {
        return $this->cancelURL;
    }

    public function setCancelURL(string $cancelURL): self
    {
        $this->cancelURL = $cancelURL;

        return $this;
    }

    public function getFailureURL(): string
    {
        return $this->failureURL;
    }

    public function setFailureURL(string $failureURL): self
    {
        $this->failureURL = $failureURL;

        return $this;
    }

    public function getWebhookURL(): string
    {
        return $this->webhookURL;
    }

    public function setWebhookURL(string $webhookURL): self
    {
        $this->webhookURL = $webhookURL;

        return $this;
    }

    public function isPurchased(): bool
    {
        return $this->isPurchased;
    }

    public function setIsPurchased(bool $isPurchased): self
    {
        $this->isPurchased = $isPurchased;

        return $this;
    }

    public function getStripeID(): ?string
    {
        return $this->stripeID;
    }

    public function setStripeID(string $stripeID): self
    {
        $this->stripeID = $stripeID;

        return $this;
    }

    public function getStripePaymentIntent(): ?string
    {
        return $this->stripePaymentIntent;
    }

    public function setStripePaymentIntent(string $stripePaymentIntent): self
    {
        $this->stripePaymentIntent = $stripePaymentIntent;

        return $this;
    }

    public function getStripeCustomer(): ?string
    {
        return $this->stripeCustomer;
    }

    public function setStripeCustomer(string $stripeCustomer): self
    {
        $this->stripeCustomer = $stripeCustomer;

        return $this;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
