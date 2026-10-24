# How to Run — FantasyXI (Fantasy Cricket Points League)

Assumes prerequisites in `PREREQUISITES.md` are already done. No Composer step — this project has no dependencies.

## One-time setup

```
mysql -u root -P 3306 < database/schema.sql
```
This drops and recreates the `fantasyxi` database, tables, and demo seed data (88 real IPL players with percentile-ranked-by-role credit values). This same command is also the wipe command — just re-run it any time to reset the data.

## Start MySQL

Open **XAMPP Control Panel** and make sure **MySQL** is running.

## Run the app

From this project's folder:
```
php -S localhost:8025 -t public public/index.php
```
(`-t public` is required — without it, assets under `public/assets` 404.)

## Open it

Visit http://localhost:8025/

Sign in with any of the demo credentials in `README.md` under "Default Login Credentials".

## Stop the app

`Ctrl+C` in the terminal running the `php -S` command.
