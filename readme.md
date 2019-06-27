# SimpleClassFinder

## Usage

```
$finder = new SimpleClassFinder\Finder();
$finder->getClassesIn("SimpleClassFinder");

$finder->getClassesThatImplements("Symfony\Component\Console\Helper\Helper");

// depends on `composer dumpautoload --optimize`
$f->loadClassesFrom("src")
    ->getClassesIn("SimpleClassFinder");

$f->getClassesThatUses("Awesome\Trait");
```

# Similar Projects

- [haydenpierce/class-finder](https://gitlab.com/hpierce1102/ClassFinder)

# References

This project was inspired by the following links:
- [PHP - get all class names inside a particular namespace](https://stackoverflow.com/a/22762333/2214160)
- [PHP: How to get all classes when using autoloader](https://stackoverflow.com/a/46435124/2214160)
- [Creating your first Composer/Packagist package](https://blog.jgrossi.com/2013/creating-your-first-composer-packagist-package/)
