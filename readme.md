# Chronicle - A Perforce Powered CMS

Chronicle is a Zend framework powered CMS that uses Perforce for all of its data storage. It
allows use to branch and merge websites in the same way developers branch and merge code.

A Vagrant environment is included with this package to make developing and experimenting
with Chronicle easier. This Vagrantfile was written for Vagrant 1.3.5, but should be
compatible with more recent versions.

## To bring up a local deployment using Vagrant:

1) cd to the Chronicle root directory. A file named Vagrantfile should be in that directory.
2) Run 'vagrant up'
3) Once Vagrant has finished provisioning the VM go to "http://192.168.33.2" in your web browser
   and complete the Chronicle wizard.

## To run the automated tests:

Chronicle ships with a very large test suite. Run 'ant -p' from the Chronicle root directory
to see the numerous test suites available. The 'smoke-build' target should suffice for most
testing. To execute the smoke tests:

    1) cd to the Chronicle root directory.
    2) Run 'vagrant ssh' to ssh into the VM
    3) cd to '/vagrant'
    4) Run 'ant -Dtest.http.host=chronicle.perforce.vm smoke-build'

To avoid specifying the HTTP host for every test run you can create a file named '.build.properties'
with the contents, "test.http.host=chronicle.perforce.vm".

## Installation

The included INSTALL.txt provides instructions for deploying Chronicle.

## License

See the included LICENSE.txt for licensing details.

## Support
Custom builds of Chronicle are not supported by Perforce Support.
For the most recent supported build go to:

http://ftp.perforce.com/perforce/r12.3/bin.multiarch/
