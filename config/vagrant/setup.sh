#!/bin/bash
# Using Ubuntu

sudo echo "127.0.1.1 ubuntu-xenial" >> /etc/hosts

sudo apt-get update > /dev/null
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    dirmngr \
    lsb-release \
    software-properties-common

curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -

curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -

sudo add-apt-repository \
   "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
   $(lsb_release -cs) \
   stable"
sudo apt-get update > /dev/null
sudo apt-get install -y docker-ce
sudo curl -L "https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

sudo apt-get install -y nodejs

sudo apt-get install -y npm


sudo echo "#!/bin/bash
docker exec -it php task \"$@\"" >> /usr/bin/task && \
  chmod +x /usr/bin/task
