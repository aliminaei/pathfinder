<?php
namespace AppBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * Package
 *
 * @ORM\Table(name="packages")
 * @ORM\Entity
 * @UniqueEntity("name")
 */
class Package
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique = true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="contributors_url", type="text", nullable=true)
     */
    private $contributorsUrl;

    /**
     * @ManyToMany(targetEntity="Contributor", mappedBy="packages")
     * @JoinTable(name="packages_contributors")
     **/
    private $contributors;

    public function __construct()
    {
        $this->contributors = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Package
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set contributorsUrl
     *
     * @param string $contributorsUrl
     *
     * @return Package
     */
    public function setContributorsUrl($contributorsUrl)
    {
        $this->contributorsUrl = $contributorsUrl;

        return $this;
    }

    /**
     * Get contributorsUrl
     *
     * @return string
     */
    public function getContributorsUrl()
    {
        return $this->contributorsUrl;
    }

    /**
     * Set contributors
     *
     * @param array $contributors
     *
     * @return Package
     */
    public function setContributors($contributors)
    {
        $this->contributors = $contributors;

        return $this;
    }

    /**
     * Get contributors
     *
     * @return array
     */
    public function getContributors()
    {
        return $this->contributors;
    }

    /**
     * Add contributor to contributor
     *
     * @param Contributor $contributor
     *
     * @return void
     */
    public function addContributor(Contributor $contributor)
    {
        if (!$this->contributors->contains($contributor))
        {
            $contributor->addPackage($this);
            $this->contributors[] = $contributor;
        }
    }
}