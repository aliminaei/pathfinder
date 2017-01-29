<?php
namespace AppBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * Contributor
 *
 * @ORM\Table(name="contributors")
 * @ORM\Entity
 * @UniqueEntity("name")
 */
class Contributor
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
     * @ManyToMany(targetEntity="Package", inversedBy="contributors")
     * @JoinTable(name="packages_contributors")
     **/
    private $packages;

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
     * @return Contributor
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
     * Set packages
     *
     * @param array $packages
     *
     * @return Contributor
     */
    public function setPackages($packages)
    {
        $this->packages = $packages;

        return $this;
    }

    /**
     * Get packages
     *
     * @return array 
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * Add package to packages
     *
     * @param Package $package
     *
     * @return void
     */
    public function addPackage(Package $package)
    {
        $this->packages[] = $package;
    }
}