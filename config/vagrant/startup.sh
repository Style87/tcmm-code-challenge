#!/usr/bin/env bash

cd /vagrant
sudo docker-compose up -d webserver
sudo task database run update
