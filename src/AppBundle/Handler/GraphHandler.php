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
     * Retrieves the shortest path between the two contributors.
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
            return $this->getErrorResponse("Invalid parameter!");
        }

        //Checking if usernames are teh same!
        if ($username1 == $username2)
        {
            return $this->getErrorResponse("Usernames are the same!");
        }

        //Checking if contributor1 exists in DB!
        $contributor1 = $this->getContributorByUsername($username1);
        if (!$contributor1)
        {
            return $this->getErrorResponse("Could not find any contributor for username: ".$username1);
        }

        //Checking if contributor2 exists in DB!
        $contributor2 = $this->getContributorByUsername($username2);
        if (!$contributor2)
        {
            return $this->getErrorResponse("Could not find any contributor for username: ".$username2);
        }

        $startNodes = $this->getContributorPackagesAsNodes($contributor1);
        //Checking if contributor1 has any packages!
        if (!$startNodes || count($startNodes) == 0)
        {
            //User1 has not contributed to any packages! this should not happen really but just in case..
            return $this->getErrorResponse("Could not find any packages for contributor: ". $username1);
        }

        //Checking if contributor2 has any packages!
        if (!$contributor2->getPackages() || count($contributor2->getPackages()) == 0)
        {
            //User2 has not contributed to any packages! this should not happen really but just in case..
            return $this->getErrorResponse("Could not find any packages for contributor: ". $username2);
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
                $path = $this->calculatePath($node);
                $result = [];
                if (count($path) < 1)
                {
                    $result = $this->getErrorResponse(sprintf("users '%s' and '%s' are not connected!", $username1, $username2));
                }
                else
                {
                    $result['ack'] = "OK";
                    $result['path_len'] = count($path);
                    $result['path'] = $path;
                }
                
                return $result;
            }
            else
            {
                //Getting next level nodes!
                $startNodes = $this->getNextLevelNodes($startNodes);
            }
        }

        // No more packages left and we are still here, so there is no connection!
        $result = $this->getErrorResponse(sprintf("users '%s' and '%s' are not connected!", $username1, $username2));
        return $result;
    }

    protected function getErrorResponse($errorMessage)
    {
        $result = [];
        $result['ack'] = "Error";
        $result['message'] = $errorMessage;
        return $result;
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
     * Retrieves a list of all neighbor nodes(packages) for the given nodes. USING DOCTORINE I have decided to use native SQL as I believe it's more efficient. so this method is depricated.
     * 
     * 
     * @param  Node $nodes  -  The packags as nodes.
     *
     * @return the neightbor nodes for the given nodes.
     */
    protected function getNextLevelNodes_doctorine($nodes)
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
     * Retrieves a list of all neighbor nodes(packages) for the given nodes.
     * 
     * 
     * @param  Node $nodes  -  The packags as nodes.
     *
     * @return the neightbor nodes for the given nodes.
     */
    protected function getNextLevelNodes($nodes)
    {
        $neighbours = [];
        
        $queryTemplate = "SELECT * FROM PACKAGES WHERE ID IN (SELECT DISTINCT package_id FROM packages_contributors WHERE contributor_id IN (SELECT contributor_id FROM packages_contributors WHERE package_id = %s) AND package_id <> %s)";

        foreach ($nodes as $node)
        {
            $sql = sprintf($queryTemplate, $node->getPackageId(), $node->getPackageId());
            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->execute();
            $queryResults = $stmt->fetchAll();
            foreach ($queryResults as $result)
            {
                $neighbour = new Node($result['id'], $result['name'], $node);
                $neighbours[$result['id']] = $neighbour;
            }
        }
        

        return $neighbours;
    }

    /**
     * Retrieves a list of all packages as nodes for the given contributor.
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
        $contributorPackages = $contributor->getPackagesAsArray();

        //ignoring previously visited packages!
        $diff = array_diff($contributorPackages,  $this->visitedPackages);
        
        //removing duplicate packages, we might get duplicate packages as their might be more than one users contributed to the same package!
        $unvisitedUniquePackages = array_unique($diff);
        foreach ($unvisitedUniquePackages as $packageId => $packageName)
        {
            $node = new Node($packageId, $packageName, $parent);
            $nodes[$packageId] = $node;
        }
        return $nodes;
    }

    /**
     * Retrieves the Contributor object from the database for the given github username.
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
     * Retrieves the list Contributors from the database that has contributed to the given package.
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
     * Retrieves the list of top users who might want to contribute to the given package.
     * Top potentials are calculated based on this formula:
     * 1. If the given package does not have any contributors, then we just return the users sorted by their total number of contributions to other packages.
     * 2. If the given package has some contributors, we find other contributors that have the shortest path to the current contributors and we sort them based on their number of connections
     * to the given package. For example imagine package A has 2 users (X, Y). user X has contributed to packages B and C and user Y has contributed to packages B and D.
     * now we have 3 packages to process, B, C and D.
     * Package B has 4 contributors: W, X, Y and Z
     * Package C has 4 contributors: U, V, W and X
     * Package D has 3 contributorsL W, Y and Z
     * now we have a list of all contributors for all 3 packages, we have to exclude X and Y as they are already contributing to package A.
     * So our ranked potential contributors are W(score 3), Z(score 2), U(score 1) and V(score 1).
     *
     * Please note, if the package has some contributoprs but we could not find any other contributors with the path==1, we return the overl top ranked users (like the packages without any contributors)
     * 
     * 
     * @param  string $package  -  The name of the package.
     *
     * @return the shortest path in json format.
     */
    public function getPotentialContributors($vendorName, $packageName)
    {
         //Checking if usernames are valid!
        if (empty($vendorName) || empty($packageName))
        {
            return $this->getErrorResponse("Invalid parameter!");
        }
        $packageName = $vendorName."/".$packageName;
        $package = $this->entityManager->getRepository('AppBundle:Package')->findOneBy(['name' => $packageName]);

        if (!$package)
        {
            return $this->getErrorResponse("Package not found!");
        }

        $potentials = [];

        $contributors = $this->getPackageContributorsAsArray($packageName);
        if (count($contributors) == 0)
        {
            //the package has no contributors! returning top users as potentials!!
            $potentials = $this->getTopContributors();
        }
        else
        {
            //The package has some contributors, return top neighbors as potentials.
            $potentials = $this->getRankedNeighbourContributors($contributors);
            if (count($potentials) == 0)
            {
                $potentials = $this->getTopContributors();   
            }
        }

        $result = [];
        $result["ack"] = "OK";
        $result["potential_contributors"] = $potentials;
        return $result;
    }

    /**
     * Retrieves the list of users (top 20) sorted by the total number of packages they have contributed into.
     * 
     * 
     * @return the list of ranked users.
     */
    protected function getTopContributors()
    {
        $repository = $this->entityManager->getRepository('AppBundle:Contributor');

        $query = $repository->createQueryBuilder('c')
            ->select('c.name, COUNT(p.id) as total')
            ->innerJoin('c.packages', 'p')
            ->groupby('c.name')
            ->orderby('total', 'desc')
            ->setMaxResults(20)
            ->getQuery();

        $results = $query->getResult();

        $potentials = [];
        foreach ($results as $result)
        {
            $potential = [];
            $potential['name'] = $result['name'];
            $potential['score'] = $result['total'];
            $potentials[] = $potential;
        }

        return $potentials;
    }

    /**
     * Retrieves the list of users who have the shortest path to the given contributors.
     * Users are ranked based on their number of connections to given contributors.
     * 
     * 
     * @param  $contributors  -  The name of the package.
     *
     * @return the list of ranked users.
     */
    protected function getRankedNeighbourContributors($contributors)
    {
        $results = [];
        foreach ($contributors as $contributor)
        {
            foreach ($contributor->getPackages() as $package)
            {
                foreach ($package->getContributors() as $neightbor)
                {
                    if (!in_array($neightbor, $contributors))
                    {
                        if (!array_key_exists($neightbor->getName(), $results))
                        {
                            $results[$neightbor->getName()] = 0;
                        }
                        $results[$neightbor->getName()] = $results[$neightbor->getName()] + 1;
                    }
                }
            }    
        }

        asort($results);

        $potentials = [];
        foreach ($results as $name => $score)
        {
            $potential = [];
            $potential['name'] = $name;
            $potential['score'] = $score;
            $potentials[] = $potential;
        }

        return $potentials;

        return $potentials;
    }

}