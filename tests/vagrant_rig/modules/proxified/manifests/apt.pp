class proxified::apt {
  file {'apt_proxy_config':
    path => '/etc/apt/apt.conf.d/01proxy',
    ensure => file,
    owner => "root",
    group => "root",
    mode => "0644",
    source => "puppet:///modules/proxified/etc/apt/apt.conf.d/01proxy"
  }
  
  file {'/etc/update_initiator':
    ensure => file,
    owner => "root",
    group => "root",
    mode => "0440",
    content => "2",
    require => File['apt_proxy_config']
  }
}