Perforce Chronicle development utilizes PHPUnit for unit testing.

To run PHPUnit against Perforce Chronicle code, simply run the
following command from this directory (tests/phpunit):

    phpunit

Notes:
* We use PHPUnit 3.6.
* We've seen some issues with UntestedModulesTest failing when run under
  PHP 5.3.8+, so you may want to comment that test out of phpunit.xml.

