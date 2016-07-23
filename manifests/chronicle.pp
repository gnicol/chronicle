
Exec { path => [ "/bin/", "/sbin/" , "/usr/bin/", "/usr/sbin/", "/vagrant/vendor/bin/", "/usr/local/bin/" ] }

# set the time zone to PDT
exec {'set time':
    command => 'echo "America/Los_Angeles" > /etc/timezone; dpkg-reconfigure --frontend noninteractive tzdata',
    unless  => 'grep "Los_Angeles" /etc/timezone',
}

file {'/vagrant/perforce':
    ensure => directory,
}

file { "/etc/profile.d/chronicle_path.sh":
  content => '
export PATH=/vagrant/vendor/bin:$PATH
'
}

#
# Setup Perforce
#
exec {'fetch p4':
    command => 'wget -O /vagrant/perforce/p4 ftp://ftp.perforce.com/perforce/r13.2/bin.linux26x86_64/p4',
    creates => '/vagrant/perforce/p4',
    require => File['/vagrant/perforce'],
}

exec {'fetch p4d':
    command => 'wget -O /vagrant/perforce/p4d ftp://ftp.perforce.com/perforce/r13.2/bin.linux26x86_64/p4d',
    creates => '/vagrant/perforce/p4d',
    require => File['/vagrant/perforce'],
}

file {'p4':
    path => '/vagrant/perforce/p4',
    mode => 0775,
    require => Exec['fetch p4'],
}

file {'p4d':
    path => '/vagrant/perforce/p4d',
    mode => 0775,
    require => Exec['fetch p4d'],
}

file {'/usr/local/bin/p4':
    source => '/vagrant/perforce/p4',
    mode => 0555,
    require => File['p4'],
}

# setup a startup script for p4d
file {'/etc/init/perforce-server.conf':
    source => '/vagrant/manifests/files/perforce-server.conf',
    ensure  => 'present',
    owner   => root, group => root, mode => 644,
    require => [ File['p4d'], File['p4'] ]
}

#
# Setup Swarm
#
class { 'apache':
        mpm_module => 'prefork',
        default_vhost => false,
      }

include apache::mod::php

apache::mod { 'rewrite': }

exec { "apt-get update":
    path => "/usr/bin",
}

package { "apt-show-versions":
    ensure => present,
    require => Exec["apt-get update"],
}

package { "php-pear":
    ensure => present,
    require => [ Exec["apt-get update"], Package['build-essential'] ],
}

package { "php-apc":
    ensure => present,
    require => [ Exec["apt-get update"], Package['php-pear'] ],
}

exec { 'get xdebug':
    command => 'pecl install xdebug',
    require =>  Package['php-pear'],
    unless  =>  'find /usr/lib -name xdebug.so | grep xdebug',
}

package { "imagemagick":
    ensure => present,
    require => [ Exec["apt-get update"] ],
}

package { "ant":
    ensure => present,
    require => [ Exec["apt-get update"], Package["openjdk-7-jdk"] ],
}

package { "openjdk-7-jdk":
    ensure => present,
    require => [ Exec["apt-get update"] ],
}

exec { "get composer":
    command => 'php -r "readfile(\'https://getcomposer.org/installer\');" | php -- --install-dir=/vagrant/perforce',
    unless  =>  'find /vagrant/perforce -name composer.phar | grep composer.phar',
}

file {'/usr/local/bin/composer':
    source => '/vagrant/perforce/composer.phar',
    mode => 0555,
    require => Exec['get composer'],
}

exec { "get phpunit":
   command => 'composer --working-dir=/vagrant install',
   unless  =>  'which phpunit',
   require => File['/usr/local/bin/composer']
}

package { "build-essential":
    ensure => present,
    require => Exec["apt-get update"],
}

file {'/var/www/chronicle/index.php':
    source => '/vagrant/index.php',
    owner   => www-data, group => www-data, mode => 0555,
}

file {'/var/www/chronicle/.htaccess':
    source => '/vagrant/.htaccess',
    owner   => www-data, group => www-data, mode => 0555,
}

apache::vhost { 'localhost':
  port        => '80',
  docroot     => '/var/www/chronicle',
  serveradmin => 'admin@example.com',
  directories => [ { path          => '/var/www/chronicle', 
                    allow_override => ['All'] }, 
                    order => 'Allow, Deny', 
                    allow => 'from all' 
                 ],
  error_log_file    => 'chronicle.error.log',
  access_log_file   => 'chronicle.access.log',
  access_log_format => 'combined',
  docroot_group     => 'www-data',
  docroot_owner     => 'www-data',
  require           => [ Package['php-apc']],
  notify            => Service['httpd'],
}

# copy the P4PHP module some place in the VM so that Apache doesn't need a restart on boot
file {'/etc/php5/perforce-php53.so':
  source => '/vagrant/p4-bin/bin.linux26x86_64/p4php/perforce-php53.so',
  ensure => present,
  owner => root, group => root, mode => 444,
  require => Package['php-pear'],
}

# this adds P4PHP support to PHP. 
file {'/etc/php5/conf.d/p4php.ini':
  ensure => present,
  owner => root, group => root, mode => 444,
  content => "extension=/etc/php5/perforce-php53.so\n",
  require => File['/etc/php5/perforce-php53.so'],
}

# this adds P4PHP support to PHP. 
file {'/etc/php5/conf.d/xdebug.ini':
  ensure => present,
  owner => root, group => root, mode => 444,
  content => "zend_extension=/usr/lib/php5/20090626/xdebug.so\nxdebug.remote_enable=1\nxdebug.remote_host=192.168.33.02\n",
  require => File['/etc/php5/perforce-php53.so'],
}
