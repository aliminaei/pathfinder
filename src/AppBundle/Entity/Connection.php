<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Connection
 *
 * @ORM\Table(name="connections")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ConnectionRepository")
 */
class Connection
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="user1", type="text")
     */
    private $user1;

    /**
     * @var string
     *
     * @ORM\Column(name="user2", type="text")
     */
    private $user2;

    /**
     * @var string
     *
     * @ORM\Column(name="via_package", type="text")
     */
    private $viaPackage;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user1
     *
     * @param string $user1
     *
     * @return Connection
     */
    public function setUser1($user1)
    {
        $this->user1 = $user1;

        return $this;
    }

    /**
     * Get user1
     *
     * @return string
     */
    public function getUser1()
    {
        return $this->user1;
    }

    /**
     * Set user2
     *
     * @param string $user2
     *
     * @return Connection
     */
    public function setUser2($user2)
    {
        $this->user2 = $user2;

        return $this;
    }

    /**
     * Get user2
     *
     * @return string
     */
    public function getUser2()
    {
        return $this->user2;
    }

    /**
     * Set viaPackage
     *
     * @param string $viaPackage
     *
     * @return Connection
     */
    public function setViaPackage($viaPackage)
    {
        $this->viaPackage = $viaPackage;

        return $this;
    }

    /**
     * Get viaPackage
     *
     * @return string
     */
    public function getViaPackage()
    {
        return $this->viaPackage;
    }
}

