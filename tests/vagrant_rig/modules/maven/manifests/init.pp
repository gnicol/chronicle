class maven {
  include package_manager::update
  
  package {'maven2':
    ensure => latest,
    require => Exec['apt_update']
  }
  
  file {'maven_global_settings':
    path => '/usr/share/maven2/conf/settings.xml',
    ensure => file,
    owner => "root",
    group => "root",
    mode => "0644",
    source => "puppet:///modules/maven/usr/share/maven2/conf/settings.xml",
    require => Package['maven2']
  }
}