FROM php:8.2-apache

# Install MariaDB server & client, and PHP PDO MySQL extensions
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        mariadb-server \
        mariadb-client && \
    docker-php-ext-install pdo pdo_mysql && \
    a2enmod rewrite && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure Apache to listen on port 80 and Render's default port 10000
RUN echo "Listen 10000" >> /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:80 *:10000>/g' /etc/apache2/sites-available/000-default.conf

# Copy project files into container
COPY . /var/www/html/

# Copy and prepare startup script
COPY start.sh /usr/local/bin/start.sh
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 10000

CMD ["/usr/local/bin/start.sh"]
