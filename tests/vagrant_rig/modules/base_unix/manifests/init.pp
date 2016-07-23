class base_unix {
  group {'puppet':
    ensure => present
  }

  user {'puppet':
    ensure => present,
    gid => 'puppet',
    require => Group['puppet']
  }
  
  file {'/etc/sudoers':
    ensure => file,
    owner => "root",
    group => "root",
    mode => "0440",
    source => "puppet:///modules/base_unix/etc/sudoers"
  }
  
}