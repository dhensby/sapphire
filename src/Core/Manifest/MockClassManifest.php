<?php

namespace SilverStripe\Core\Manifest;

class MockClassManifest extends ClassManifest
{
    public function __construct($base = null, CacheFactory $cacheFactory = null)
    {
    }

    public function init($includeTests = false, $forceRegen = false)
    {
    }

    public function regenerate($includeTests)
    {
    }

    public function getItemPath($name)
    {
        $lowerName = strtolower($name);
        foreach ([
             $this->classes,
             $this->interfaces,
             $this->traits,
         ] as $source) {
            if (isset($source[$lowerName])) {
                return $source[$lowerName];
            }
        }
        return null;
    }

    public function registerMockObject($object)
    {
        $reflection = new \ReflectionClass($object);
        $className = $reflection->getName();
        $lClassName = strtolower($className);

        $this->classes[$lClassName] = $reflection->getFileName();
        $this->classNames[$lClassName] = $className;

        if ($realParentClass = get_parent_class($object)) {
            $lRealParentClass = strtolower($realParentClass);
            if (empty($this->children[$lRealParentClass])) {
                $this->children[$lRealParentClass] = [];
            }
            $this->children[$lRealParentClass][$lRealParentClass] = $realParentClass;
        } else {
            $this->roots[$lClassName] = $className;
        }

        foreach ($reflection->getInterfaceNames() as $interfaceName) {
            $lInterfaceName = strtolower($interfaceName);
            if (empty($this->implementors[$lInterfaceName])) {
                $this->implementors[$lInterfaceName] = [];
            }
            $this->implementors[$lInterfaceName][$lClassName] = $className;
        }

        $this->coalesceDescendants($className);

    }
}
