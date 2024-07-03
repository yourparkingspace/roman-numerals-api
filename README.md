# Roman Numerals API Tech Test
This is an example repository of a roman numerals API

## Directory Structure
The repo is split up into the following diretories:

- .docs: a directory for any relevant documentation.

-  .docker: this houses all of the docker contextual files for the running of the application(s). This could house future docker specific things for deployments.

- .vscode: this directory is optional and wouldn't normally be committed but I like the consistency of some settings that I have found in VS Code, usually a keen PHPStorm user.

- packages: this houses the actual application, included in this test is a `roman-numerals-api` and `roman-numerals-frontend`

## Running The application
Running the application is simple:

- `cp .env.example .env`
- Add the following items to the newly made .env
    - DATABASE_USERNAME=default
    - DATABASE_PASSWORD=secret  
- `cd .docker`
- `docker-compose up -d`
- You can either now use Docker Desktop to execute commands or find the relevant docker container id to run commands
    - For Docker Desktop
        - To install the composer packages, open Docker Desktop, select the PHP container, navigate to exec and run `composer install`
    - For a CLI approach
        - Open a terminal
        - run `docker ps` and find the PHP container or alternatively use `docker ps -aqf "name=docker-php"` on a mac you can also do `pbcopy < docker ps -aqf "name=docker-php`
        - Using the id of the container run the following command `docker exec {YOUR CONTAINER ID} composer install`
- Navigate to `http://localhost/` to see the application

## Calling the endpoints

## Running tests

## Future considerations