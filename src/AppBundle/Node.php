<?php

namespace AppBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Node
{
    protected $packageName;
    protected $parent;

    public function __construct($packageName, Node $parent = null)
    {
        $this->packageName = $packageName;
        $this->parent = $parent;
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function getContributors()
    {

    }

    public function getParent()
    {
        return $this->parent;
    }
}