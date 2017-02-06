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
    public function shortestPathAction($username1, $username2)
    {
        $graphHandler = $this->get("graph_handler");
        $message = $graphHandler->getShortestPath($username1, $username2);
        $responseCode = 200;
        if ($message['ack'] == 'Error')
        {
            $responseCode = 404;
        }
        return new JsonResponse($message, $responseCode);
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
    public function potentialContributorsAction($vendorName, $packageName)
    {
        $graphHandler = $this->get("graph_handler");
        $message = $graphHandler->getPotentialContributors($vendorName, $packageName);
        $responseCode = 200;
        if ($message['ack'] == 'Error')
        {
            $responseCode = 404;
        }
        return new JsonResponse($message, $responseCode);
    }
}
