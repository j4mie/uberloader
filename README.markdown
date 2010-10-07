Überloader
==========

A brute-force autoloader for PHP5.

Released under a [BSD license](http://en.wikipedia.org/wiki/BSD_licenses).

About
-----

Are you fed up of framework autoloaders that require confusing mappings between class names and file names? Annoyed about having to lay out your project in a rigorously specified structure? Or just bored of writing `require_once` dozens of times?

**Enter the Überloader**. This brute-force autoloader recursively searches your project directory, looking inside every PHP file to find where the class you're trying to use has been defined.

This might sound horribly slow, but once the Überloader has found the file, **it caches the path** so that next time you need to autoload the class, the lookup is lightning fast.

It's not pretty, it's not clean, but it works.

Let's See Some Code
-------------------

### Setup ###

Setting up Überloader is quite simple. Copy the `uberloader.php` file to somewhere into your project directory and include it:

`require_once 'your/path/to/uberloader.php';`

Create an instance of the `Uberloader` class. The constructor takes two arguments:

1. The directory in which to store the path cache. This directory must exist and must be writable by the web server.
2. [Optional] An array of extensions for the files to be searched. This defaults to `array('php')`.

`$loader = new Uberloader(dirname(__FILE__) . "/cache/");`

Add a path to be searched for classes:

`$loader->add_path(dirname(__FILE__));`

Register the autoloader. Überloader provides a helper method to do this:

`$loader->register();`

That's it!

### Convenience method ###

Überloader provides a static convenience method called `init` that instantiates and registers the loader. It is suitable for simple use cases where only one path will be searched. It takes three arguments. The first is the name of the path to search. The second two are the same at the main class constructor.

`require_once 'your/path/to/uberloader.php';`

`Uberloader::init(dirname(__FILE__), dirname(__FILE__) . "/cache/");`

### Advanced ###

#### Multiple search paths ####

You can add multiple paths for Überloader to search by calling the `add_path` method more than once. The paths you supply will be searched in the order they are added.

This feature allows you to easily implement something like Kohana's [cascading filesystem](http://kohanaframework.org/guide/about.filesystem]). Just add the search paths in a sensible order (for example: your application first, then custom modules, then the system path) and Überloader will load whichever class it finds first.

#### Disabling the cache ####

During development, you may wish to disable the path cache. This mean that the whole directory tree will be searched each and every time a class is loaded, so **make sure you re-enable the cache in production**.

`$loader->set_cache_enabled(false);`
