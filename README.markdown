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

Create an instance of the `Uberloader` class. The constructor takes three arguments:

1. The base directory to search inside. This is usually the root directory of your project.
2. The directory in which to store the path cache. This directory must exist and must be writable by the web server.
3. [Optional] An array of extensions for the files to be searched. This defaults to `array('php')`.


`$loader = new Uberloader(dirname(__FILE__), dirname(__FILE__) . "/cache/");`

Register the autoloader. Überloader provides a helper method to do this:

`$loader->register();`

That's it!

### Convenience method ###

Überloader provides a static convenience method called `init` that instantiates and registers the loader. It takes the same arguments as the class constructor:

`require_once 'your/path/to/uberloader.php';`

`Uberloader::init(dirname(__FILE__), dirname(__FILE__) . "/cache/");`

#### Disabling the cache ####

During development, you may wish to disable the path cache. This mean that the whole directory tree will be searched each and every time a class is loaded, so **make sure you re-enable the cache in production**.

`$loader->set_cache_enabled(false);`
