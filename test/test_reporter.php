<?php
    /**
     * Class to provide simple colourised test reporting
     */
    class TestReporter {

        private static $passed_tests = array();
        private static $failed_tests = array();
        private static $db;

        private static $term_colours = array(
            'BLACK' => '30',
            'RED' => '31',
            'GREEN' => '32',
            'DEFAULT' => '00',
        );

        private static $html_colours = array(
            'BLACK' => 'black',
            'RED' => 'red',
            'GREEN' => 'green',
            'DEFAULT' => 'black',
        );

        /**
         * Format a line for printing. Detects
         * if the script is being run from the command
         * line or from a browser.
         *
         * Colouring code loosely based on
         * http://www.zend.com//code/codex.php?ozid=1112&single=1
         */
        private static function format_line($line, $colour='DEFAULT') {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $colour = self::$html_colours[$colour];
                return "<p style=\"color: $colour;\">$line</p>\n";
            } else {
                $colour = self::$term_colours[$colour];
                return chr(27) . "[0;{$colour}m{$line}" . chr(27) . "[00m\n";
            }
        }

        /**
         * Report a passed test
         */
        public static function report_pass($test_name) {
            echo self::format_line("PASS: $test_name", 'GREEN');
            self::$passed_tests[] = $test_name;
        }

        /**
         * Report a failed test
         */
        public static function report_failure($test_name, $expected=null, $actual=null) {
            echo self::format_line("FAIL: $test_name", 'RED');
            if ($expected && $actual) {
                echo self::format_line("Expected: $expected", 'RED');
                echo self::format_line("Actual: $actual", 'RED');
            }
            self::$failed_tests[] = $test_name;
        }

        /**
         * Print a summary of passed and failed test counts
         */
        public static function summary() {
            $passed_count = count(self::$passed_tests);
            $failed_count = count(self::$failed_tests);
            echo self::format_line('');
            echo self::format_line("$passed_count tests passed. $failed_count tests failed.");

            if ($failed_count != 0) {
                echo self::format_line("Failed tests: " . join(", ", self::$failed_tests));
            }
        }
    }
