<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Package;
use AppBundle\Entity\Contributor;

class PersistPackageCommand extends ContainerAwareCommand{

    protected $entityManager;
    protected $logger;

    protected function configure()
    {
        $this->setName('app:package:persist')->addArgument("message");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->logger = $this->getContainer()->get('logger');

        $message = $input->getArgument('message');
        $messageJson = json_decode($message);

        $packageName = $messageJson->package_name;
        $contributorsUrl = $messageJson->contributors_url;
        $contributors = $messageJson->contributors;

        $package = $this->savePackage($packageName, $contributorsUrl);
        foreach ($contributors as $name) 
        {
            $contributor = $this->saveContributor($name);
            $package->addContributor($contributor);
        }

        $this->entityManager->persist($package);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function saveContributors($names, $package)
    {
        $contributors = [];
        foreach ($names as $name) 
        {
            $contributor = $this->saveContributor($name, $package);
            $contributors[] = $contributor;
        }

        return $contributors;
    }

    protected function saveContributor($name)
    {
        $contributor = $this->entityManager->getRepository('AppBundle\Entity\Contributor')->findOneByName($name);
        if (!$contributor)
        {
            $contributor = new Contributor();
            $contributor->setName($name);
        }
        $this->entityManager->persist($contributor);
        return $contributor;
    }

    protected function savePackage($packageName, $contributorsUrl)
    {
        $package = $this->entityManager->getRepository('AppBundle\Entity\Package')->findOneByName($packageName);
        if (!$package)
        {
            $package = new Package();
            $package->setName($packageName);
            $package->setContributorsUrl($contributorsUrl);
            $this->entityManager->persist($package);
        }
        return $package;
    }
}