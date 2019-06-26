<?php

namespace SimpleClassFinder;

use ReflectionClass;
use RuntimeException;
use InvalidArgumentException;
use ReflectionException;

class Finder
{
    protected $map = [];
    protected $defaultNamespace = "global";

    public function __construct()
    {
        $this->loadClasses();
    }

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

    public function getNamespaces()
    {
        return array_keys($this->map);
    }

    public function all()
    {
        return $this->map;
    }

    public function getClassesFromNamespace($namespace = '')
    {
        if (!$namespace) {
            $namespace = $this->defaultNamespace;
        } else if(!isset($this->map[$namespace])) {
            throw new InvalidArgumentException("The namespace '$namespace' was not found!");
        }

        return array_values($this->map[$namespace]);
    }

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
}
