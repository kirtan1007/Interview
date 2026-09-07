FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to listen on both standard port 80 and Render's default port 10000
RUN echo "Listen 10000" >> /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:80 *:10000>/g' /etc/apache2/sites-available/000-default.conf

# Copy project files to Apache web root
COPY . /var/www/html/

# Set proper permissions for web root
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 10000

# Start Apache directly with fallback to PORT env variable
CMD ["sh", "-c", "if [ -n \"$PORT\" ] && [ \"$PORT\" != \"80\" ] && [ \"$PORT\" != \"10000\" ]; then echo \"Listen $PORT\" >> /etc/apache2/ports.conf; fi && exec apache2-foreground"]
