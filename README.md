# Project Setup with Docker

This project uses Docker Compose to manage and run multiple services, including Nginx, PHP, Composer, Artisan, npm, and MySQL. This setup allows for a seamless and consistent development environment.

## Services Overview

- **Nginx**: Serves as the web server for your application.
- **PHP**: Runs PHP code for the application.
- **Composer**: Manages PHP dependencies.
- **Artisan**: Executes Laravel Artisan commands.
- **npm**: Manages Node.js dependencies for frontend development.
- **MySQL**: Provides the MySQL database service.

## Prerequisites

Ensure you have Docker and Docker Compose installed on your machine


## How to Use

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/yourproject.git

cd yourproject

docker-compose up --build

```
### 2.Add MySQL User And Give Permission Name

```bash
docker exec -it <container_name> bash

CREATE USER 'user1'@'%' IDENTIFIED BY 'user1_password';

GRANT ALL PRIVILEGES ON *.* TO 'user1'@'%' WITH GRANT OPTION;

FLUSH PRIVILEGES;

```
### 3. Make Database Migration And Seeding
```bash

docker-compose run artisan <command>

docker-compose run artisan migrate

docker-compose run artisan migrate:refresh --seed


