<?php

namespace SimpleClassFinder;

use ReflectionClass;
use RuntimeException;
use InvalidArgumentException;
use ReflectionException;

/**
 * Class Finder
 * @package SimpleClassFinder
 */
class Finder
{
    protected $map = [];
    protected $defaultNamespace = "global";

    public function __construct()
    {
        $this->loadClasses();
    }

    /**
     * @return $this
     */
    final protected function loadClasses()
    {
        foreach(get_declared_classes() as $class)
        {
            $namespacePieces = explode("\\", $class);

            $hash = md5($class);
            $this->updateNamespaceMap($namespacePieces, $class, $hash);
        }

        return $this;
    }

    /**
     * @param array $namespacePieces
     * @param $classNamespace
     * @param $hash
     */
    protected function updateNamespaceMap(array $namespacePieces, $classNamespace, $hash)
    {
        if (!$namespacePieces) {
            // register the class in the global namespace
            $this->map[$this->defaultNamespace][$hash] = $classNamespace;
            return;
        }

        // discard last part of the namespace
        array_pop($namespacePieces);

        $namespaceKey = implode('\\', $namespacePieces);
        if (!$namespaceKey) {
            return $this->updateNamespaceMap($namespacePieces, $classNamespace, $hash);
        }

        $this->map[$namespaceKey][$hash] = $classNamespace;
        return $this->updateNamespaceMap($namespacePieces, $classNamespace, $hash);
    }

    /**
     * Load the classes from a given path
     * @param $path
     * @return Finder
     */
    public function loadClassesFrom($path)
    {
        $autoloaderClass = "";

        foreach ($this->map['global'] as $class) {
            if (strpos($class, 'ComposerAutoloaderInit') === 0) {
                $autoloaderClass = $class; // sample ComposerAutoloaderInite7ea52338f6c93b4dc60b2cee949379c
                break;
            }
        }

        if (!$autoloaderClass) {
            throw new RuntimeException("The ComposerAutoloaderInit class was not found!");
        }

        $loader = $autoloaderClass::getLoader();

        // you need to run composer dump-autoload with the flag to optmize it
        // if your project does not use classmap
        foreach ($loader->getClassMap() as $classFilename) {
            if (strpos($classFilename, $path) == false) {
                continue;
            }

            require_once $classFilename;
        }

        return $this->loadClasses();
    }

    /**
     * Return the namespaces traversed
     * @return array
     */
    public function getNamespaces()
    {
        return array_keys($this->map);
    }

    /**
     * Return all found classes
     * @return array
     */
    public function all()
    {
        return $this->map;
    }

    /**
     * Get all the classes inside the given namespace
     * @param string $namespace
     * @return array
     */
    public function getClassesFromNamespace($namespace = '')
    {
        if (!$namespace) {
            $namespace = $this->defaultNamespace;
        } else if(!isset($this->map[$namespace])) {
            throw new InvalidArgumentException("The namespace '$namespace' was not found!");
        }

        return array_values($this->map[$namespace]);
    }

    /**
     * Get all the classes that implements the given baseClass (it can be an abstract class, normal class or interface)
     * @param $baseClass
     * @param string $namespace
     * @return array
     * @throws ReflectionException
     */
    public function getClassesThatImplements($baseClass, $namespace = '')
    {
        $baseClass = new ReflectionClass($baseClass);
        $classes = [];

        foreach ($this->getClassesFromNamespace($namespace) as $class) {
            $reflectionClass = new ReflectionClass($class);

            if ($baseClass->isInterface() && $reflectionClass->implementsInterface($baseClass)) {
                $classes[] = $class;
            } else if ($reflectionClass->isSubclassOf($baseClass)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Get all the classes that implements a given trait
     * @param $trait
     * @param string $namespace
     * @return array
     * @throws ReflectionException
     */
    public function getClassesThatUses($trait, $namespace = '')
    {
        $traitClass = new ReflectionClass($trait); // yep, we don't use.
        // But just to make sure that the trait exists, we will instantiate the Reflection of it
        $classes = [];

        foreach ($this->getClassesFromNamespace($namespace) as $class) {
            $traits = $this->getTraitsFromClass($class);

            if (in_array($trait, $traits)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * Get traits from a class with the traits used from their parents
     * @param string $class
     * @param array  $traits
     * @return array
     */
    protected function getTraitsFromClass($class, $traits = [])
    {
        if (!$class) {
            return $traits;
        } else if (!class_parents($class)) {
            return array_merge($traits, class_uses($class));
        }

        $parents = class_parents($class);
        $parent = array_pop($parents);
        return $this->getTraitsFromClass($parent, class_uses($parent));
    }
}
