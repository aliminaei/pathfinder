<?php

namespace AppBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use AppBundle\Adapter\PackagistAdapter;
use AppBundle\Adapter\GithubAdapter;
use AppBundle\Adapter\ArangoDBAdapter;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Package;
use AppBundle\Entity\Contributor;

class GraphHandler
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Retrives the shortest path between the two contributors.
     * 
     * 
     * @param  string $user1  -  The first contributor's github username
     * @param  string $user2  -  The second contributor's github username
     *
     * @return the shortest path in json format.
     */
    public function getShortestPath($username1, $username2)
    {
        if (empty($username1) || empty($username2))
        {
            return "ERROR - invalid parameter!";
        }

        if ($username1 == $username2)
        {
            return "ERROR - Users are the same!";   
        }


        $user1 = $this->getUserByUsername($username1);
        if (!$user1)
        {
            return "ERROR - User1 not found";
        }

        $user2 = $this->getUserByUsername($username2);
        if (!$user2)
        {
            return "ERROR - User2 not found";
        }

        $sourcePackages = $this->getUserPackages($user1);
        if (!$sourcePackages || count($sourcePackages) == 0)
        {
            //User1 has not contributed to any packages! this should not happen really but just in case..
            return "Not connected!";
        }

        $destPackages = $this->getUserPackages($user2);
        if (!$destPackages || count($destPackages) == 0)
        {
            //User2 has not contributed to any packages! this should not happen really but just in case..
            return "Not connected!";
        }

        $package = $this->entityManager->getRepository('AppBundle:Package')->findOneBy(['name' => '00f100/cakephp-opauth']);

        if($package) return $package->getContributors()->map(function($item) { return $item->getName(); })->toArray();
        else return [];
    }

    protected function getNeighbours($user)
    {
        
    }

    protected function getUserByUsername($username)
    {
        $user = $this->entityManager->getRepository('AppBundle:Contributor')->findOneBy(['name' => $username]);
        if ($user)
        {
            return $user;
        }
        else
        {
            return null;
        }
    }

    protected function getUserPackages($user)
    {
        if ($user)
        {
            return $user->getPackages()->map(function($item) { return $item->getName(); })->toArray();
        }
        else
        {
            return [];
        }
    }

    /**
     * Retrives the list of top users who might want to contribute to the given package.
     * Users are ranked based on their number of contributions to other packages.
     * 
     * 
     * @param  string $package  -  The name of the package.
     *
     * @return the shortest path in json format.
     */
    public function getPotentialContributors($package)
    {
        $package = $this->entityManager->getRepository('AppBundle:Package')->findOneBy(['name' => '00f100/cakephp-opauth']);

        if($package) return $package->getContributors()->map(function($item) { return $item->getName(); })->toArray();
        else return [];
    }
}