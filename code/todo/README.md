Psst! Looking for a template with Svelma included? [Click here!](https://github.com/mihaimiculescu/docker-svelte-template/tree/withsvelma)

# Svelte within Docker container
Develop [Svelte](https://svelte.dev) apps using Docker containers!

#### No Nodejs install required on the host machine
Just make sure you have Docker installed and up and running.
#### No `node_modules` folder in your project
It just sits into your container. On your local machine, all you deal with are the source and output folders.
#### Multiple projects, one image
Each new project just requires a new project folder, a new container that you initalize and you're good to go!

This fork adds Docker compatibility to the original project template for [Svelte](https://svelte.dev) apps, that lives at https://github.com/sveltejs/template.
In order to do so, some tweakings were applied to `package.json` and `rollup.config.js` files. Also a `Dockerfile` was added.

Tested so far on Windows 10 with Linux containers and on (K)ubuntu Bionic LTS.

---
## Usage
###### Note to Linux users: please prefix all `docker` commands with `sudo` if your distro requires it, [or follow these steps](https://docs.docker.com/install/linux/linux-postinstall/).

To create a new project based on this template, open terminal (Windows users, please use PowerShell only) and `cd` into the folder where you keep all your projects. Then do the following steps:
### 1. ###
Clone the repo on your local machine

  either
  
  1a) via git: 
``` 
git clone --single-branch --branch master https://github.com/mihaimiculescu/docker-svelte-template.git 
```
  
  or
  
  1b) via zip:
[Click here to download the zip file](https://github.com/mihaimiculescu/docker-svelte-template/archive/master.zip)
and then put it in the folder where you keep all your projects. Then extract it there.
### 2. ###
Step 1 creates a folder called `docker-svelte-template`.  
Go ahead and rename it to something meaningful for you. Then cd into it:
```
cd <folder_name>
```
### 3. ###
Build your image:
either

3a) either create it from Dockerfile
```
docker build -t <name-of-image> .
```

or 

3b) download the image from docker hub:
```
docker pull mihaimiculescu/svelte
```

### 4. ###
Create and start your container:
#### Windows:
```
docker run -ti --name <container_name> -p 5000:5000 -p 35729:35729 -p 3572:3572 -v ${PWD}/src:/sveltedev/src -v ${PWD}/public:/sveltedev/public --init <name-of-image> sh
```
#### Linux
```
docker run -ti --name <container_name> -p 5000:5000 -p 35729:35729 -p 3572:3572 -v $(pwd)/src:/sveltedev/src -v $(pwd)/public:/sveltedev/public --init <name-of-image> sh
```
### 5. ###
Once inside the container, type:
```
npm install
npm run dev
```
At this point you should be able to see the familiar purple "Hello world" at http://localhost:5000/
Leave the terminal window open.

### 6. ###
Please notice the 2 folders: `src` and `public`within your project folder. 
Go ahead and edit the files in the `src`folder and watch the changes in your browser!

### 7. ###
To stop your session: press CTRL+C twice and ENTER in your terminal window. Then type `exit`. 
Once out of the container, type
```
docker stop <container_name>
```
### 8. ### 
To resume your session, just type:
```
docker start <container_name>
docker exec -ti <container_name> sh
```
once inside the container, type
```
npm run dev
``` 
and that's it.

---
### Good luck with your Svelte projects!
