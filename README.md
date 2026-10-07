# Deadline Risk

Deadline Risk is a student planner for keeping assignments, course details, and due dates in one place. It estimates which tasks need attention from their due date, progress, remaining effort, and priority.

## What it does

- Track assignments, due dates, estimated hours, and completion.
- Review deadline pressure on the dashboard, calendar, and risk report.
- Keep course, professor, and grade details alongside task progress.
- Connect to Canvas to sync coursework.
- Ask for AI help with a task or draft a response to a Canvas prompt. AI features need a provider key; risk scores and dashboard summaries are calculated from your task data.

## Run locally

Requirements: PHP 8.3 or later, Composer, Node.js, and npm.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set up the database in `.env`. SQLite is the default; create `database/database.sqlite` if it does not exist. Then run:

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

Open the local address printed by `artisan serve` and create an account. For development with asset rebuilding, use `npm run dev` in a second terminal.

## Optional services

Add `OPENAI_API_KEY` to enable task assistance and Canvas answer drafting. Canvas sync also needs the Canvas URL and API credentials configured in `.env`. Without these credentials, the planner, calendar, and risk calculations remain available.

## Checks

Run the PHPUnit suite with:

```bash
php artisan test
```

## Stack

Built with Laravel, PHP, Blade, Bootstrap, and Vite.
