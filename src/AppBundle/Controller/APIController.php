<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\Annotations as FOSRest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class APIController extends FOSRestController
{
    /**
     * Returns the shortest path between the two given github users.
     *
     * @ApiDoc(
     *   description="This method returnes the shortest path between the two given gothub users. The path is calculated based on the Packagist packgaes they have contributed to.",
     *   statusResponse = {
     *     200 = "Returned when successful",
     *     404 = "Returned when one or both users are not found"
     *   }
     * )
     *
     *
     * @return JSONResponse
     */
    public function shortestPathAction($user1, $user2)
    {
        // $data = [
        //     "package_name" => "00f100/cakephp-opauth"
        // ];
        // $this->container->get("rs_queue.producer")->produce("crawler", $data);
        $graphHandler = $this->get("graph_handler");
        $message = $graphHandler->getShortestPath($user1, $user2);
        return new JsonResponse(array("message"=> $message), 404);
    }

    /**
     * Returns the a list of github users who might potentially contribute to the given package.
     *
     * @ApiDoc(
     *   description="This method returns the a list of github users who might potentially contribute to the given package.",
     *   statusResponse = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the package is not found"
     *   }
     * )
     *
     *
     * @return JSONResponse
     */
    public function potentialContributorsAction($vendor, $package)
    {
        return new JsonResponse(array("message"=> "Package not found"), 404);
    }
}
