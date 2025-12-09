<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

/**
 * @ORM\Table(name="purchase_token", indexes={
 *      @ORM\Index(columns={"token"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\PurchaseTokenRepository")
 */
class PurchaseToken
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $token;

    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    protected $transactionID;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $price;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $isSuccess;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isClientFailure;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $successURL;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $cancelURL;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $failureURL;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $webhookURL;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isPurchased;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $stripeID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $stripePaymentIntent;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $stripCustomer;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

    /**
     * Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated     = new DateTime();
        $this->isPurchased     = false;
        $this->isClientFailure = false;
        $this->token           = bin2hex(random_bytes(16));
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return PurchaseToken
     */
    public function setToken(string $token): PurchaseToken
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionID(): string
    {
        return $this->transactionID;
    }

    /**
     * @param string $transactionID
     *
     * @return PurchaseToken
     */
    public function setTransactionID(string $transactionID): PurchaseToken
    {
        $this->transactionID = $transactionID;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param int $price
     *
     * @return PurchaseToken
     */
    public function setPrice(int $price): PurchaseToken
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return PurchaseToken
     */
    public function setDescription(string $description): PurchaseToken
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isSuccess(): ?bool
    {
        return $this->isSuccess;
    }

    /**
     * @param bool $isSuccess
     *
     * @return PurchaseToken
     */
    public function setIsSuccess(bool $isSuccess): PurchaseToken
    {
        $this->isSuccess = $isSuccess;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClientFailure(): bool
    {
        return $this->isClientFailure;
    }

    /**
     * @param bool $isClientFailure
     *
     * @return PurchaseToken
     */
    public function setIsClientFailure(bool $isClientFailure): PurchaseToken
    {
        $this->isClientFailure = $isClientFailure;

        return $this;
    }

    /**
     * @return string
     */
    public function getSuccessURL(): string
    {
        return $this->successURL;
    }

    /**
     * @param string $successURL
     *
     * @return PurchaseToken
     */
    public function setSuccessURL(string $successURL): PurchaseToken
    {
        $this->successURL = $successURL;

        return $this;
    }

    /**
     * @return string
     */
    public function getCancelURL(): string
    {
        return $this->cancelURL;
    }

    /**
     * @param string $cancelURL
     *
     * @return PurchaseToken
     */
    public function setCancelURL(string $cancelURL): PurchaseToken
    {
        $this->cancelURL = $cancelURL;

        return $this;
    }

    /**
     * @return string
     */
    public function getFailureURL(): string
    {
        return $this->failureURL;
    }

    /**
     * @param string $failureURL
     *
     * @return PurchaseToken
     */
    public function setFailureURL(string $failureURL): PurchaseToken
    {
        $this->failureURL = $failureURL;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebhookURL(): string
    {
        return $this->webhookURL;
    }

    /**
     * @param string $webhookURL
     *
     * @return PurchaseToken
     */
    public function setWebhookURL(string $webhookURL): PurchaseToken
    {
        $this->webhookURL = $webhookURL;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPurchased(): bool
    {
        return $this->isPurchased;
    }

    /**
     * @param bool $isPurchased
     *
     * @return PurchaseToken
     */
    public function setIsPurchased(bool $isPurchased): PurchaseToken
    {
        $this->isPurchased = $isPurchased;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripeID(): ?string
    {
        return $this->stripeID;
    }

    /**
     * @param string $stripeID
     *
     * @return PurchaseToken
     */
    public function setStripeID(string $stripeID): PurchaseToken
    {
        $this->stripeID = $stripeID;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripePaymentIntent(): ?string
    {
        return $this->stripePaymentIntent;
    }

    /**
     * @param string $stripePaymentIntent
     *
     * @return PurchaseToken
     */
    public function setStripePaymentIntent(string $stripePaymentIntent): PurchaseToken
    {
        $this->stripePaymentIntent = $stripePaymentIntent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripeCustomer(): ?string
    {
        return $this->stripCustomer;
    }

    /**
     * @param string $stripeCustomer
     *
     * @return PurchaseToken
     */
    public function setStripeCustomer(string $stripeCustomer): PurchaseToken
    {
        $this->stripCustomer = $stripeCustomer;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return PurchaseToken
     */
    public function setDateCreated(DateTime $dateCreated): PurchaseToken
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
