class p4 {
  include p4::client
  
  define sync ( $syncPath = "//depot/...",
                $clientName = "${name}_client",
                $clientRoot = "/vagrant",
                $options = "noallwrite noclobber nocompress unlocked nomodtime normdir",
                $submitOptions = "revertunchanged",
                $lineEnd = "local",
                $view,
                $user,
                $p4port = "perforce:1666"
    ) {
      p4::client::create { $clientName:
        clientRoot => $clientRoot,
        options => $options,
        submitOptions => $submitOptions,
        lineEnd => $lineEnd,
        view => $view,
        user => $user,
        p4port => $p4port,
      }

      exec { "p4_sync_${clientName}":
        command => "/usr/local/bin/p4 -u ${user} -p ${p4port} -c ${clientName} sync ${syncPath}",
        timeout => 3600,
        require => [File['p4_binary'], Exec["p4_client_${clientName}"]]
      }
  }
  
  file {'p4_binary':
    path => '/usr/local/bin/p4',
    ensure => file,
    owner => "root",
    group => "root",
    mode => "0755",
    source => "puppet:///modules/p4/usr/local/bin/p4",
  }

}