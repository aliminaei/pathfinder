<?php

namespace AppBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Node
{
    protected $packageId;
    protected $packageName;
    protected $parent;

    public function __construct($packageId, $packageName, Node $parent = null)
    {
        $this->packageId = $packageId;
        $this->packageName = $packageName;
        $this->parent = $parent;
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function getPackageId()
    {
        return $this->packageId;
    }

    public function getParent()
    {
        return $this->parent;
    }
}