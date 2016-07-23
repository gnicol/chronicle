class p4cms_tester {
  include base_unix, maven, p4, package_manager::update

  package {'xvfb':
    ensure => latest,
    require => Exec['apt_update']
  }

  package {'openjdk-6-jdk':
    ensure => latest,
    require => Exec['apt_update']
  }

  package {'firefox':
    ensure => latest,
    require => Package['xvfb']
  }

}