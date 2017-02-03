<?php

namespace AppBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Monolog\Logger;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Package;
use AppBundle\Entity\Contributor;
use AppBundle\Node;

class GraphHandler
{
    protected $entityManager;
    protected $logger;
    protected $path;
    protected $visitedPackages;

    public function __construct(EntityManager $entityManager, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->path = [];
        $this->visitedPackages = [];
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


        $user1 = $this->getContributorByUsername($username1);
        if (!$user1)
        {
            return "ERROR - User1 not found";
        }

        $user2 = $this->getContributorByUsername($username2);
        if (!$user2)
        {
            return "ERROR - User2 not found";
        }

        $startNodes = $this->getUserPackageNodes($user1);
        if (!$startNodes || count($startNodes) == 0)
        {
            //User1 has not contributed to any packages! this should not happen really but just in case..
            return "Not connected1!";
        }

        if (!$user2->getPackages() || count($user2->getPackages()) == 0)
        {
            //User2 has not contributed to any packages! this should not happen really but just in case..
            return "Not connected2!";
        }

        while (count($startNodes) > 0)
        {
            $node = $this->checkPath($startNodes, $user2);
            if ($node)
            {
                return $this->calculatePath($node);
            }
            else
            {
                $startNodes = $this->getNextLevelNodes($startNodes);
                // return "OOOPS";
            }
        }
    }

    protected function calculatePath($node)
    {
        $path = [];
        while ($node)
        {
            array_unshift($path, $node->getPackageName());
            $node = $node->getParent();
        }

        return $path;
    }

    protected function checkPath($nodes, $user)
    {
        foreach($nodes as $currentNode)
        {
            array_push($this->visitedPackages, $currentNode->getPackageName());
            $contributors = $this->getPackageContributorsAsArray($currentNode->getPackageName());
            foreach ($contributors as $contributor)
            {
                if ($user == $contributor)
                {
                    return $currentNode;
                }
            }
        }

        return null;
    }

    protected function getNextLevelNodes($nodes)
    {
        $neighbours = [];
        foreach ($nodes as $node)
        {
            $contributors = $this->getPackageContributorsAsArray($node->getPackageName());
            foreach ($contributors as $contributor)
            {
                $userPackageNodes = $this->getUserPackageNodes($contributor, $node);
                $neighbours = array_merge($neighbours, $userPackageNodes);
            }
        }

        return $neighbours;
    }

    protected function getUserPackageNodes($user, $parent = null)
    {
        $nodes = [];
        $userPackages = $user->getPackageNamesAsArray();
        $diff = array_diff($userPackages,  $this->visitedPackages);
        $unvisitedUniquePackages = array_unique($diff);
        foreach ($unvisitedUniquePackages as $package)
        {
            $node = new Node($package, $parent);
            $nodes[$package] = $node;
        }
        return $nodes;
    }

    protected function checkConnection($packageName, $contributor)
    {
        $package = $this->entityManager->getRepository('AppBundle:Package')->findOneBy(['name' => $packageName]);
        if ($package)
        {
            return in_array($contributor, $package->getContributors()->map(function($item) { return $item->getName(); })->toArray());
        }
        else
        {
            return false;
        }

    }

    protected function getContributorByUsername($username)
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

    protected function getPackageContributorsAsArray($packageName)
    {
        $package = $this->entityManager->getRepository('AppBundle:Package')->findOneBy(['name' => $packageName]);

        if($package) return $package->getContributors()->map(function($item) { return $item; })->toArray();
        else return [];
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