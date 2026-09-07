#!/bin/bash

# Ensure MariaDB runtime directory exists with proper permissions
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

# Start MariaDB service
service mariadb start || /etc/init.d/mariadb start

# Wait for MariaDB to be fully operational
for i in {1..30}; do
    if mysqladmin ping --silent 2>/dev/null; then
        echo "MariaDB is ready."
        break
    fi
    echo "Waiting for MariaDB..."
    sleep 1
done

# Create database and grant full permissions to interview_user and root
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS interview;
CREATE USER IF NOT EXISTS 'interview_user'@'localhost' IDENTIFIED BY 'Interview@123';
CREATE USER IF NOT EXISTS 'interview_user'@'127.0.0.1' IDENTIFIED BY 'Interview@123';
CREATE USER IF NOT EXISTS 'interview_user'@'%' IDENTIFIED BY 'Interview@123';
GRANT ALL PRIVILEGES ON interview.* TO 'interview_user'@'localhost';
GRANT ALL PRIVILEGES ON interview.* TO 'interview_user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON interview.* TO 'interview_user'@'%';
ALTER USER 'root'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON interview.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON interview.* TO 'root'@'127.0.0.1';
FLUSH PRIVILEGES;
EOF

# Import interview.sql if users table is not yet present
if ! mysql -u interview_user -p'Interview@123' -e "USE interview; DESCRIBE users;" >/dev/null 2>&1; then
    echo "Importing interview.sql into database..."
    mysql -u interview_user -p'Interview@123' interview < /var/www/html/interview.sql || true
    echo "Database import finished."
fi

# Support dynamic Render PORT
if [ -n "$PORT" ] && [ "$PORT" != "80" ] && [ "$PORT" != "10000" ]; then
    echo "Listen $PORT" >> /etc/apache2/ports.conf
fi

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
