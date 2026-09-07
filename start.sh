#!/bin/bash
set -e

# Start MariaDB service
service mariadb start

# Ensure root user is accessible by PHP without auth_socket restriction
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED BY ''; FLUSH PRIVILEGES;" 2>/dev/null || true

# Create database and import schema if tables don't exist yet
mysql -u root -e "CREATE DATABASE IF NOT EXISTS interview;"
mysql -u root interview < /var/www/html/interview.sql 2>/dev/null || true

# Support dynamic PORT from Render
if [ -n "$PORT" ] && [ "$PORT" != "80" ] && [ "$PORT" != "10000" ]; then
    echo "Listen $PORT" >> /etc/apache2/ports.conf
fi

# Run Apache in foreground
exec apache2-foreground
