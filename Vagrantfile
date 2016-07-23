# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
 
  config.vm.define :chronicle do |chronicle_config|
    chronicle_config.vm.box = "precise64"
    chronicle_config.vm.box_url = "http://files.vagrantup.com/precise64.box"
	  
    chronicle_config.vm.network :forwarded_port, guest: 80, host: 8080
    chronicle_config.vm.network :forwarded_port, guest: 1666, host: 1666
    chronicle_config.vm.network :forwarded_port, guest: 9000, host: 9000
    chronicle_config.vm.network :private_network, ip: "192.168.33.02"
    chronicle_config.vm.hostname = "chronicle.perforce.vm"

    chronicle_config.vm.synced_folder "./", "/vagrant", id: "vagrant-root", owner: "vagrant", group: "vagrant", type: "nfs"
    chronicle_config.vm.synced_folder "application/", "/var/www/chronicle/application", owner: "www-data", group: "www-data", type: "nfs"
    chronicle_config.vm.synced_folder "data/", "/var/www/chronicle/data", owner: "www-data", group: "www-data", type: "nfs"
    chronicle_config.vm.synced_folder "library/", "/var/www/chronicle/library", owner: "www-data", group: "www-data", type: "nfs"
    chronicle_config.vm.synced_folder "sites/", "/var/www/chronicle/sites", owner: "www-data", group: "www-data", type: "nfs"
    chronicle_config.vm.synced_folder "p4-bin/", "/var/www/chronicle/p4-bin", owner: "www-data", group: "www-data", type: "nfs"
    chronicle_config.vm.synced_folder "tests/", "/var/www/chronicle/tests", owner: "www-data", group: "www-data", type: "nfs"
    chronicle_config.vm.synced_folder "docs/", "/var/www/chronicle/docs", owner: "www-data", group: "www-data", type: "nfs"

    chronicle_config.vm.provision :puppet do |puppet|
      puppet.module_path   = "manifests/modules"
      puppet.manifests_path = "manifests"
      puppet.manifest_file = "chronicle.pp"
    end

    chronicle_config.vm.provider :virtualbox do |vb|
      vb.customize ["modifyvm", :id, "--memory", "2048"]
      vb.customize ["modifyvm", :id, "--cpus", "2"]   
    end

  end

end
