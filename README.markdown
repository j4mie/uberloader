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

### Concepts ###

Überloader has two core concepts, *search paths* and *cache backends*. Search paths are directories in which to look for files containing class definitions. Cache backends are interfaces to store file paths so they won't need to be searched again. Überloader ships with two simple cache backends; these are discussed below.

### Installation ###

Copy the `uberloader.php` file to somewhere into your project directory and `require` it:

`require_once 'your/path/to/uberloader.php';`

### Minimal setup: convenience method ###

Überloader provides a static convenience method called `init` that instantiates and registers the loader. It is suitable for simple use cases where only one path will be searched, and the basic filesystem cache backend will be used. It takes two arguments. The first is the name of the path at which to start the recursive search, and the second is the directory in which to store the cache file.

`Uberloader::init(dirname(__FILE__), dirname(__FILE__) . "/cache/");`

That's it!

### Manual setup ###

Setting up Überloader manually is quite simple. First, create an instance of the `Uberloader` class:

`$loader = new Uberloader();`

Next, we need to create a cache backend. Überloader ships with a simple filesystem-based cache backend. The constructor for this backend takes one argument: the directory in which to store the cache file.

`$backend = new UberloaderCacheBackendFilesystem(dirname(__FILE__) . "/cache/");`

You should then tell Überloader to use this backend:

`$loader->set_cache_backend($backend);`

Then, add a path to be searched for classes. This is usually the root directory of your application:

`$loader->add_path(dirname(__FILE__));`

Finally, register the autoloader. Überloader provides a helper method to do this:

`$loader->register();`

Done.

### Advanced setup ###

#### Specifying file extensions to search ####

Überloader's constructor takes a single optional argument: an array of file extensions to search. This defaults to `array('php')`, so any files ending in `.php` will be searched.

#### Multiple search paths ####

You can add multiple paths for Überloader to search by calling the `add_path` method more than once. The paths you supply will be searched in the order they are added.

This feature allows you to easily implement something like Kohana's [cascading filesystem](http://kohanaframework.org/guide/about.filesystem). Just add the search paths in a sensible order (for example: your application first, then custom modules, then the system path) and Überloader will load whichever class it finds first.

#### Disabling the cache ####

During development, you may wish to disable the path cache. To do this, simply set the cache backend to be an instance of the supplied `UberloaderCacheBackendDummy`.

`$loader->set_cache_backend(new UberloaderCacheBackendDummy());`

Disabling the cache means that the whole directory tree will be searched each and every time a class is loaded, so **make sure you re-enable the cache in production**.

#### Implementing custom cache backends ####

You may wish to create your own custom cache backend (for example, to store cached class paths in [Memcached](http://memcached.org/) or [Redis](http://redis.io/)). Your cache backend class should implement the `UberloaderCacheBackend` interface, which requires that it supplies three methods: `get($key)`, `set($key, $value)` and `teardown()`. The latter is called once at the end of the request and may be used to perform and necessary cleanup of your cache or connection. Of course, you may also implement a constructor to initialise your cache and any other necessary helper methods.

    class MyCustomCacheBackend implements UberloaderCacheBackend {
        public function get($key) {
            // get the value for the specified key from your cache
        }

        public function set($key, $value) {
            // Set the given value in your cache at the specified key
        }

        public function teardown() {
            // Disconnect or save the cache
        }
    }
