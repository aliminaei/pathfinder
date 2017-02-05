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
     * @param  string $username1  -  The first contributor's github username
     * @param  string $username2  -  The second contributor's github username
     *
     * @return the shortest path in json format.
     */
    public function getShortestPath($username1, $username2)
    {
        //Checking if usernames are valid!
        if (empty($username1) || empty($username2))
        {
            return "ERROR - invalid parameter!";
        }

        //Checking if usernames are teh same!
        if ($username1 == $username2)
        {
            return "ERROR - Usernames are the same!";   
        }

        //Checking if contributor1 exists in DB!
        $contributor1 = $this->getContributorByUsername($username1);
        if (!$contributor1)
        {
            return "ERROR - Could not find any contributor for username: ".$username1;
        }

        //Checking if contributor2 exists in DB!
        $contributor2 = $this->getContributorByUsername($username2);
        if (!$contributor2)
        {
            return "ERROR - Could not find any contributor for username: ".$username2;
        }

        $startNodes = $this->getContributorPackagesAsNodes($contributor1);
        //Checking if contributor1 has any packages!
        if (!$startNodes || count($startNodes) == 0)
        {
            //User1 has not contributed to any packages! this should not happen really but just in case..
            return "Not connected! - Could not find any packages for contributor: ". $username1;
        }

        //Checking if contributor2 has any packages!
        if (!$contributor2->getPackages() || count($contributor2->getPackages()) == 0)
        {
            //User2 has not contributed to any packages! this should not happen really but just in case..
            return "Not connected! - Could not find any packages for contributor: ". $username2;
        }

        //So to claculate the shortest path, we are creating a linked list of packages. Each node has a package name and also keeps a reference of it's parent node.
        //To start we get the list of the packages that username1 has contributed to. These packages are all our root nodes, so we may have more than one root nodes. The parent ref for root noed is NULL.
        //Then we check if username2 has contributed to any of the root nodes. This means the path = 1! If we could not find a connection, we go one level down.
        //This means we get a list of all other contributors for all the root nodes one by one and then for each one of those github users we get a list of packages that they made a contribution.
        //Now we have new sets of noes to check if user2 is a contributor. We continue this process and build the linked list until either find user2 in the contributors or we run out of packages!.
        //When building the linked list and getting the next level nodes, we ignore all the packages we have checked before.
        //Once we found user2, we have a package node(destination node) and navigating back on the linked list from the destination node to the root (parent == NULL) we can calculate the path!
        while (count($startNodes) > 0)
        {
            //checking if user2 exists in any of theses packages contributors!
            $node = $this->checkConnection($startNodes, $contributor2);
            if ($node)
            {
                //We found user2! time to calculate the path by going back on the created linked list from the destination node!.
                return $this->calculatePath($node);
            }
            else
            {
                //Getting next level nodes!
                $startNodes = $this->getNextLevelNodes($startNodes);
            }
        }
    }

    /**
     * Calculates the shortest path from destination node.
     * 
     * 
     * @param  Node $node  -  The destination node.
     *
     * @return the shortest path as an array.
     */
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

    /**
     * Checks if the given contributor exists in the given packages' contributors.
     * 
     * 
     * @param  Node $nodes  -  The packags as nodes.
     * @param  $contributor  -  The contributor.
     *
     * @return the package node that has the given contributor or null.
     */
    protected function checkConnection($nodes, $contributor)
    {
        foreach($nodes as $currentNode)
        {
            array_push($this->visitedPackages, $currentNode->getPackageName());
            $contributors = $this->getPackageContributorsAsArray($currentNode->getPackageName());
            foreach ($contributors as $_contributor)
            {
                if ($contributor == $_contributor)
                {
                    return $currentNode;
                }
            }
        }

        return null;
    }

    /**
     * Retrives a list of all neighbor nodes(packages) for the given nodes.
     * 
     * 
     * @param  Node $nodes  -  The packags as nodes.
     *
     * @return the neightbor nodes for the given nodes.
     */
    protected function getNextLevelNodes($nodes)
    {
        $neighbours = [];
        foreach ($nodes as $node)
        {
            $contributors = $this->getPackageContributorsAsArray($node->getPackageName());
            foreach ($contributors as $contributor)
            {
                $userPackageNodes = $this->getContributorPackagesAsNodes($contributor, $node);
                $neighbours = array_merge($neighbours, $userPackageNodes);
            }
        }

        return $neighbours;
    }

    /**
     * Retrives a list of all packages as nodes for the given contributor.
     * 
     * 
     * @param  $contributor  -  The contributor.
     * @param  Node $parent  -  The node's parent. Default value is null, (root nodes have their parents sets as null).
     *
     * @return the packages as nodes for the given contributor.
     */
    protected function getContributorPackagesAsNodes($contributor, $parent = null)
    {
        $nodes = [];
        $contributorPackages = $contributor->getPackageNamesAsArray();

        //ignoring previously visited packages!
        $diff = array_diff($contributorPackages,  $this->visitedPackages);
        
        //removing duplicate packages, we might get duplicate packages as their might be more than one users contributed to the same package!
        $unvisitedUniquePackages = array_unique($diff);
        foreach ($unvisitedUniquePackages as $package)
        {
            $node = new Node($package, $parent);
            $nodes[$package] = $node;
        }
        return $nodes;
    }

    /**
     * Checks if the given contributor ecists in the given package's contributors.
     * 
     * 
     * @param  $packageName  -  The name of the package.
     * @param  $contributor  -  The contributor.
     *
     * @return true if contributor exists in the given package, otherwise false!
     */
    protected function checkConnection_old($packageName, $contributor)
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

    /**
     * Retrives the Contributor object from the database for the given github username.
     * 
     * 
     * @param  $username  -  github username.
     *
     * @return Contributor.
     */
    protected function getContributorByUsername($username)
    {
        $contributor = $this->entityManager->getRepository('AppBundle:Contributor')->findOneBy(['name' => $username]);
        if ($contributor)
        {
            return $contributor;
        }
        else
        {
            return null;
        }
    }

    /**
     * Retrives the list Contributors from the database that has contributed to the given package.
     * 
     * 
     * @param  $packageName  -  The package name.
     *
     * @return list of Contributors.
     */
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