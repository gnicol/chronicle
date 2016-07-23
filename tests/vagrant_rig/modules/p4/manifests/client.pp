class p4::client {
  include p4
  
  define create ( $clientName = $name,
                  $clientRoot = "/vagrant",
                  $options = "noallwrite noclobber nocompress unlocked nomodtime normdir",
                  $submitOptions = "revertunchanged",
                  $lineEnd = "local",
                  $view,
                  $user,
                  $p4port = "perforce:1666"
  ) {
    $client_tmpfile = "/tmp/p4clients/${clientName}"
    file { "/tmp/p4clients":
      ensure => directory,
      mode => "0777",
      owner => "puppet",
      group => "puppet"
    }

    file { $clientName:
      path => $client_tmpfile,
      mode => "0644",
      content => template("p4/p4_client.erb"),
      owner => "puppet",
      group => "puppet",
      require => File["/tmp/p4clients"]
    }
    
    exec { "p4_client_${clientName}":
      command => "/usr/local/bin/p4 -u ${user} -p ${p4port} client -i < ${client_tmpfile}",
      timeout => 3600,
      require => [File[$clientName], File['p4_binary']]
    }
  }
}
