# -*- mode: ruby -*-
# vi: set ft=ruby :

$network_interface = "enp0s8"
$display_ip_address = <<IP_ADDRESS
ipaddress=`/sbin/ifconfig #{$network_interface} | grep 'inet addr' | awk -F' ' '{print $2}'`
echo "ip address: $ipaddress"
IP_ADDRESS

VAGRANT_FILE_PATH = File.dirname(__FILE__)

Vagrant.configure(2) do |config|

    config.vm.box = "ubuntu/xenial64"
    config.vm.box_check_update = true
    config.vm.hostname = "tcmm-vagrant"

    config.vm.network :public_network

    config.vm.network :forwarded_port, guest: 80, host: 2323
    config.vm.network :forwarded_port, guest: 3306, host: 3306
    config.vm.network :forwarded_port, guest: 35729, host: 35729

    # run before the guest is halted
    config.trigger.before :halt do |trigger|
      trigger.info = "Stopping containers"
      trigger.run_remote = {path: "./config/vagrant/halt.sh"}
    end

    config.vm.provider :virtualbox do |v|
        v.name = "tcmm-vagrant"
        v.customize ["modifyvm", :id, "--memory", "2048"]
        v.customize ["modifyvm", :id, "--vram", "32"]
    end

    config.vm.provision "shell", path: "./config/vagrant/setup.sh"
    config.vm.provision "shell", privileged: false, inline: $display_ip_address, run: "always"
    config.vm.provision "shell", path: "./config/vagrant/startup.sh", run: "always"
end
