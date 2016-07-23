Perforce Chronicle development utilizes PHP CodeSniffer to check style.

To run PHP CodeSniffer against Perforce Chronicle code, simply change to
the top-level directory and run the following command:

    phpcs --standard=tests/codesniffer/P4CMS \
        --ignore=application/dojo \
        --extensions=php \
        library/P4 \
        library/P4Cms \
        application \
        sites/all/modules \
        tests/phpunit

Notes:
* Our implementation works with PHP CodeSniffer 1.2.2, not 1.3.0 (yet).

