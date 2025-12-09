<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

/**
 * @ORM\Table(name="merchant")
 * @ORM\Entity(repositoryClass="App\Repository\MerchantRepository")
 */
class Merchant
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $password;

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
        $this->dateCreated = new DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return Merchant
     */
    public function setPassword(string $password): Merchant
    {
        $this->password = $password;

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
     * @return Merchant
     */
    public function setDateCreated(DateTime $dateCreated): Merchant
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
