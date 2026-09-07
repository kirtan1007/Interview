FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files to Apache web root
COPY . /var/www/html/

# Setup entrypoint script for dynamic Render port binding
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set proper permissions for web root
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
