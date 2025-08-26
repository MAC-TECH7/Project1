# PHP + MySQL + Bootstrap Starter

This is a minimal Dockerized starter using PHP 8.2 (Apache), MySQL 8, and Bootstrap 5 with a simple tasks CRUD example.

## Requirements
- Docker and Docker Compose

## Run
```bash
docker compose up -d --build
```

- App: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081` (Host: `db`, User: `appuser`, Password: `apppassword`)

## Project Structure
```
app/
  index.php          # Bootstrap UI + CRUD
  src/config.php     # PDO setup (reads env, default to docker values)
php/
  custom.ini         # PHP dev settings
mysql/
  001_init.sql       # Creates tasks table and seed
```

## Environment
Copy `.env.example` to `.env` under `app/` if you want to override defaults.

```
DB_HOST=db
DB_PORT=3306
DB_NAME=appdb
DB_USER=appuser
DB_PASSWORD=apppassword
```

## Commands
- Stop: `docker compose down`
- Reset DB: `docker compose down -v && docker compose up -d`

## Notes
- The PHP container serves files from `app/` via Apache.
- Bootstrap loaded from CDN.
