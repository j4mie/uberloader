<?php
    /**
     * Tests for Uberloader
     */

    require_once dirname(__FILE__) . '/../uberloader.php';
    require_once dirname(__FILE__) . '/test_reporter.php';

    $loader = new Uberloader();
    $loader->set_cache_backend(new UberloaderCacheBackendDummy());
    $loader->add_path(dirname(__FILE__) . '/search_dir/');
    $loader->register();

    function test_load_class($classname) {
        if (class_exists($classname) || interface_exists($classname)) {
            TestReporter::report_pass($classname);
        } else {
            TestReporter::report_failure($classname);
        }
    }

    test_load_class('BracketOnSameLine');
    test_load_class('BracketOnNextLine');
    test_load_class('ClassWithSuperclass');
    test_load_class('ClassWithInterface');
    test_load_class('InterfaceTest');
    test_load_class('InterfaceTest');
    test_load_class('LOWERCASECLASSNAME');

    TestReporter::summary();
