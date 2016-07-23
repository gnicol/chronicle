class p4cms ($codeline = "main"){
  include base_unix, proxified, package_manager

  package {'apache2':
    ensure => latest,
    require => Exec['apt_update'],
  }

  package {'libapache2-mod-php5':
    ensure => latest,
    require => Exec['apt_update'],
  }

  package {'php5':
    ensure => latest,
    require => Exec['apt_update'],
  }

  package {'php-apc':
    ensure => latest,
    require => Exec['apt_update'],
  }

}