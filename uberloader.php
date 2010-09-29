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

        const CACHE_FILE_NAME = "uberloader_cache.json";

        // Location to start the search, usually the root of the application
        protected $_base_directory;

        // File containing the path cache
        protected $_cache_file;

        // Array of file extensions to search
        protected $_file_types;

        // The path cache
        protected $_cache = null;

        // Is the path cache enabled?
        // Might be useful to set this to false in development
        protected $_cache_enabled = true;

        /**
         * Create a new instance of the Überloader.
         *
         * @param string $base_directory The base directory to search from, usually the root of the application
         * @param string $cache_directory The directory in which to store the path cache
         * @param array $file_type Array of file extensions containing your PHP code
         */
        public function __construct($base_directory, $cache_directory, $file_types=array('php')) {
            $cache_directory = realpath($cache_directory);
            if (!is_writable($cache_directory)) {
                throw new UberloaderException("Cache directory does not exist or is not writable");
            }

            $this->_base_directory = realpath($base_directory);
            $this->_file_types = $file_types;
            $this->_cache_file = $cache_directory . '/' . self::CACHE_FILE_NAME;
        }

        /**
         * Enable or disable the path cache
         *
         * @param boolean $enabled should the cache be enabled or disabled?
         */
        public function set_cache_enabled($enabled) {
            $this->_cache_enabled = $enabled;
        }

        /**
         * Load the file containing the given class name
         *
         * @param string $class_name the class name to find
         */
        public function load($class_name) {
            $cached_path = $this->_check_cache($class_name);
            if ($cached_path !== false) {
                require_once $cached_path;
                return;
            }
            $result = $this->_search($this->_base_directory, $class_name);

            if ($result === false) {
                return false;
            }

            $this->_add_to_cache($class_name, $result);
            require_once $result;
            return;
        }

        /**
         * Register this class as an autoloader
         */
        public function register() {
            spl_autoload_register(array($this, 'load'));
        }

        /**
         * Called when this Überloader instance is destroyed.
         * Writes the path cache to the cache file
         */
        public function __destruct() {
            $this->_write_cache();
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
         * for the given class.
         *
         * @param string $file_path the file to check
         * @param string £target_class_name the class name to find
         */
        protected function _check_file($file_path, $target_class_name) {
            $pattern = "/class {$target_class_name}[\s{]/";
            $contents = file_get_contents($file_path);
            $success = preg_match($pattern, $contents);
            return $success === 1;
        }

        /**
         * Load the path cache from the cache file
         */
        protected function _load_cache() {
            if ($this->_cache_enabled && file_exists($this->_cache_file)) {
                $contents = file_get_contents($this->_cache_file);
                $this->_cache = json_decode($contents, true);
            } else {
                $this->_cache = array();
            }
        }

        /**
         * Check the cache for the given class name
         *
         * @param string $class_name the class name to check
         */
        protected function _check_cache($class_name) {
            if (is_null($this->_cache)) {
                $this->_load_cache();
            }
            if ($this->_cache_enabled && isset($this->_cache[$class_name])) {
                return $this->_cache[$class_name];
            }
            return false;
        }

        /**
         * Write the cache to the cache file
         */
        protected function _write_cache() {
            if ($this->_cache_enabled) {
                file_put_contents($this->_cache_file, json_encode($this->_cache));
            }
        }

        /**
         * Write the given path to the path cache
         *
         * @param string $class_name the class name
         * @param string $path the path of the file containing the class definition
         */
        protected function _add_to_cache($class_name, $path) {
            $this->_cache[$class_name] = $path;
        }
    }

    class UberloaderException extends Exception {
    }