class package_manager::update {
  include proxified::apt
  
  exec { 'apt_update':
    command => '/usr/bin/apt-get update',
    refreshonly => true,
    subscribe => File['/etc/update_initiator'],
    timeout => 3600
  }
}