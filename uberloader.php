<?php

   /**
    *
    * Überloader
    *
    * http://github.com/j4mie/uberloader/
    *
    * A brute-force autoloader for PHP5.
    *
    * BSD Licensed.
    *
    * Copyright (c) 2010, Jamie Matthews
    * All rights reserved.
    *
    * Redistribution and use in source and binary forms, with or without
    * modification, are permitted provided that the following conditions are met:
    *
    * * Redistributions of source code must retain the above copyright notice, this
    * list of conditions and the following disclaimer.
    *
    * * Redistributions in binary form must reproduce the above copyright notice,
    * this list of conditions and the following disclaimer in the documentation
    * and/or other materials provided with the distribution.
    *
    * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
    * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
    * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
    * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
    * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
    * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
    * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
    *
    */

    class Uberloader {


        // Directories to search. These will be searched in order.
        protected $_paths_to_search = array();

        // The cache backend to use for path lookup
        protected $_cache_backend = null;

        // Array of file extensions to search
        protected $_file_types;

        /**
         * Create a new instance of the Überloader.
         *
         * @param string $cache_directory The directory in which to store the path cache
         * @param array $file_type Array of file extensions containing your PHP code
         */
        public function __construct($file_types=array('php')) {
            $this->_file_types = $file_types;
        }

        /**
         * Add a path to search for classes. This must be called at least once
         * before Uberloader is used, or an exception will be thrown.
         */
        public function add_path($path) {
            $this->_paths_to_search[] = realpath($path);
        }

        /**
         * Set the cache backend instance to use to store paths
         *
         * @param UberloaderCacheBackend $backend the backend to use
         */
        public function set_cache_backend(UberloaderCacheBackend $backend) {
            $this->_cache_backend = $backend;
        }

        /**
         * Load the file containing the given class name
         *
         * @param string $class_name the class name to find
         */
        public function load($class_name) {
            $class_name = strtolower($class_name);
            $cached_path = $this->_cache_backend->get($class_name);
            if ($cached_path !== false) {
                require_once $cached_path;
                return;
            }

            foreach ($this->_paths_to_search as $path) {
                $result = $this->_search($path, $class_name);

                if ($result !== false) {
                    break;
                }
            }

            if ($result === false) {
                return false;
            }

            require_once $result;
            return;
        }

        /**
         * Register this class as an autoloader
         */
        public function register() {
            if (is_null($this->_cache_backend)) {
                throw new UberloaderException("No cache backend set");
            }
            if (empty($this->_paths_to_search)) {
                throw new UberloaderException("No search paths defined");
            }
            spl_autoload_register(array($this, 'load'));
        }

        /**
         * Extra convenient static method to instantiate
         * and register Überloader with one base path and a
         * filesystem-based cache backend.
         *
         * @param string $base_directory The base directory to search from, usually the root of the application
         * @param string $cache_directory The directory in which to store the path cache
         *
         */
        public static function init($base_directory, $cache_directory) {
            $loader = new self();
            $loader->set_cache_backend(new UberloaderCacheBackendFilesystem($cache_directory));
            $loader->add_path($base_directory);
            $loader->register();
            return $loader;
        }

        /**
         * Called when this Überloader instance is destroyed.
         */
        public function __destruct() {
            $this->_cache_backend->teardown();
        }

        /**
         * Recursively search for a class definition, starting
         * at the given root directory.
         * 
         * @param string $path the path to start the search from
         * @param string $target_class_name the class name to find
         */
        protected function _search($path, $target_class_name) {
            $handle = opendir($path);
            while(($item = readdir($handle)) !== false) {
                if (in_array($item, array('.', '..'))) {
                    continue;
                }
                $item_path = "$path/$item";
                if (is_dir($item_path)) {
                    $result = $this->_search($item_path, $target_class_name);
                    if ($result !== false) {
                        return $result;
                    }
                }
                $extension = pathinfo($item_path, PATHINFO_EXTENSION);
                if (in_array($extension, $this->_file_types)) {
                    $success = $this->_check_file($item_path, $target_class_name);
                    if ($success) {
                        return $item_path;
                    }
                }
            }
            return false;
        }

        /**
         * Check a file to see if it contains a class definition
         * for the given class. This uses PHP's source parsing to
         * tokenise the file and identify the correct parts of the
         * code. This is probably quite slow, but should be robust.
         *
         * @param string $file_path the file to check
         * @param string $target_class_name the class name to find
         */
        protected function _check_file($file_path, $target_class_name) {

            $contents = file_get_contents($file_path);
            $tokens = token_get_all($contents);
            $namespace = '';

            for ($index=0; $index < count($tokens); $index++) {
                $current_token = $tokens[$index];

                // Some tokens (eg semicolons, brackets)
                // are just strings. Ignore these.
                if (!is_array($current_token)) {
                    continue;
                }

                // If namespaces are supported in this version of
                // PHP (ie PHP5.3) then look for T_NAMESPACE tokens.
                if (defined('T_NAMESPACE') && $current_token[0] == T_NAMESPACE) {
                    $n = 2;

                    while (isset($tokens[$index + $n]) && is_array($tokens[$index + $n])) {
                        $namespace .= $tokens[$index + $n][1];
                        $n++;
                    }

                    $namespace .= '\\';
                }

                // First element in the array is the token type.
                // Check if this is a T_CLASS token (the string "class")
                // or a T_INTERFACE token (the string "interface").
                if (in_array($current_token[0], array(T_CLASS, T_INTERFACE))) {

                    // The immediate next token is whitespace. The one
                    // after that represents the class name.
                    $classname_token_index = $index + 2;

                    // Check it is a valid token
                    if (isset($tokens[$classname_token_index]) && is_array($tokens[$classname_token_index])) {

                        // The second element in the token array
                        // is the contents of the token.
                        $classname = strtolower($namespace . $tokens[$classname_token_index][1]);

                        // Whether or not this is the class we're looking for,
                        // we can now add this class to the cache.
                        $this->_cache_backend->set($classname, $file_path);

                        // See if we've found the class we're looking for
                        if ($classname == $target_class_name) {
                            return true;
                        }
                    }
                }
            }
        }
    }

    /**
     * Interface for an Uberloader cache backend
     */
    interface UberloaderCacheBackend {

        /**
         * Get the given key from the cache
         *
         * @param $key the key whose value should be retrieved
         * @return string or false
         */
        public function get($key);

        /**
         * Set a cache entry
         *
         * @param string $key the key to set
         * @param string $value to the value to store at the given key
         */
        public function set($key, $value);

        /**
         * Destroy the cache. Called once just before the request ends.
         * This may write the cache file to disc etc.
         */
        public function teardown();
    }

    /**
     * Class to implement filesystem-based caching
     */
    class UberloaderCacheBackendFilesystem implements UberloaderCacheBackend {

        const CACHE_FILE_NAME = "uberloader_cache.json";

        protected $_cache_directory;
        protected $_cache_file;
        protected $_cache;

        public function __construct($cache_directory) {
            $cache_directory = realpath($cache_directory);
            if (!is_writable($cache_directory)) {
                throw new UberloaderException("Cache directory does not exist or is not writable");
            }
            $this->_cache_directory = $cache_directory;
            $this->_cache_file = $cache_directory . '/' . self::CACHE_FILE_NAME;

            // Set up the cache
            if (file_exists($this->_cache_file)) {
                $contents = file_get_contents($this->_cache_file);
                $this->_cache = json_decode($contents, true);
            } else {
                $this->_cache = array();
            }
        }

        public function get($key) {
            return isset($this->_cache[$key]) ? $this->_cache[$key] : false;
        }

        public function set($key, $value) {
            $this->_cache[$key] = $value;
        }

        public function teardown() {
            file_put_contents($this->_cache_file, json_encode($this->_cache), LOCK_EX);
        }
    }

    /**
     * Dummy cache backend; returns false for all keys.
     * Shouldn't be used in production.
     */
    class UberloaderCacheBackendDummy implements UberloaderCacheBackend {
        public function get($key) {
            return false;
        }

        public function set($key, $value) {
        }

        public function teardown() {
        }
    }

    class UberloaderException extends Exception {
    }
