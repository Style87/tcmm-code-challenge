
# TCMM Coding Challenge Todo App

This repository fulfills the requirement of the tcmm coding challenge.

## Description

The application is a todo app allowing users to add tags and notes with assoicated tags.

## Getting Started

### Dependencies

#### Windows
* Vagrant

#### Linux
* docker-compose

### Install and Execute

Clone the repository at the desired location on your machine.

#### Windows
* Using the terminal navigate to the project directory and run `vagrant up`.
* Access the site by navigating to the ip address of the vagrant machine in a web browser.

#### Linux
* Navigate to the project directory and run `docker-compose up -d webserver`.
* Execute `docker exec -it php task "database run update"`
* Access the site by navigating to `localhost` or `127.0.0.1` in a web browser.
