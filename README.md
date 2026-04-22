# University-Web-Applications-System A

This is the repository of our  web application Student-Advisor system
for out University with the purpose of keeping versions , updates and 
tracking our progress as a team.

## Environment Variables

Create a `.env` file in the project root with these values:

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=advicut
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

APP_BASE_URL=http://localhost/University-Web-Applications-System-A/
APP_TIMEZONE=asia/nicosia
PASSWORD_RESET_SMTP_USER=your-smtp-username
PASSWORD_RESET_SMTP_PASS=your-smtp-password

The application reads database variables for PDO connection, app base/timezone variables for URL and timezone behavior, and password-reset variables for reset-link generation and SMTP email delivery.
