# Hotel Management System (PHP + MySQL + Bootstrap)

Educational hotel management system with staff authentication, rooms, guests, bookings, payments, and reports.

## Features
- Staff auth (admin, staff)
- Rooms CRUD with image upload
- Guests CRUD
- Bookings CRUD with availability check and total calculation
- Payments and basic billing
- Dashboard reports: KPIs and recent items

## Requirements
- PHP 8.1+
- MySQL 8.x (or MariaDB 10.5+)
- Web server (Apache with mod_php or PHP built-in server)

## Setup
1. Create database:
   - Create a schema, e.g., `hotelms`.
   - Import `schema.sql`.
2. Configure DB connection:
   - Edit `includes/config.php` with your DB credentials.
3. Serve the app:
   - Apache/Nginx: point DocumentRoot to the project folder.
   - Or run: `php -S 0.0.0.0:8080 -t /workspace/hotelms`
4. Login with seeded admin:
   - Email: `admin@example.com`
   - Password: `admin123`

## File Structure
- `includes/config.php` – DB connection and globals
- `includes/functions.php` – common helpers
- `includes/auth.php` – session auth helpers and guards
- `includes/header.php`, `includes/navbar.php`, `includes/footer.php` – layout
- `index.php` – dashboard
- `login.php`, `logout.php` – authentication
- `rooms.php`, `guests.php`, `bookings.php`, `payments.php`, `reports.php` – modules

## Notes
- All PHP files contain extensive line-by-line comments for learning.
- For production, disable display_errors and tighten permissions.