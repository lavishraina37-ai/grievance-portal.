# Resolve: Smart School Grievance Portal

A working front-end implementation based on the supplied UML, DFD, sequence, state, component, and deployment diagrams.

## Included workflows

- Student/parent grievance submission
- Grievance list, search, filtering, and status tracking
- Under review, in progress, resolved, and closed-style status presentation
- Notification centre
- Resolution health dashboard
- Category reporting and report export interaction
- Role switcher for Student, Teacher, School Administrator, and Government Official views

## Run

Open `index.html` in a browser. No build step or dependency installation is required.

The browser prototype uses `localStorage` as a temporary data layer. The PHP/MySQL backend foundation is now included in `api/` and `database/`.

## Deploy the frontend to Vercel

This project can be deployed to Vercel as a static frontend. The `api/` PHP/MySQL backend requires a PHP-capable host and is not executed by Vercel's static deployment.

1. Import the GitHub repository into Vercel.
2. Keep the project root set to the repository root.
3. Leave the build command empty and use the default output directory.

The deployed frontend uses browser `localStorage`; it does not connect to the PHP/MySQL API automatically.

## Start the PHP/MySQL backend

1. Install XAMPP or another PHP 8+ and MySQL environment.
2. Copy this `grievance-portal` folder into the server's web root, such as `xampp/htdocs/`.
3. Start Apache and MySQL.
4. Import `database/schema.sql` through phpMyAdmin or the MySQL client.
5. Copy `.env.example` to `.env` and set the database credentials in your PHP environment.
6. Open `http://localhost/grievance-portal/`.

Available API routes use `api/index.php?resource=...`:

- `POST ...?resource=auth`
- `GET|POST ...?resource=complaints`
- `PATCH ...?resource=complaints&id=1`
- `GET ...?resource=notifications&user_id=1`
- `GET ...?resource=reports`
