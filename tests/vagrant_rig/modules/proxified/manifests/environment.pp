class proxified::environment {
  case $operatingsystem {
    ubuntu: {
      file {'/etc/environment':
        ensure => file,
        owner => "root",
        group => "root",
        mode => "0644",
        source => "puppet:///modules/proxified/etc/environment"
      }
    }
  }
}