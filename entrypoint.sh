#!/bin/bash
set -e

# Default to port 80 if PORT is not set
PORT="${PORT:-80}"

# Configure Apache port dynamically at runtime
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Start Apache in foreground
exec apache2-foreground
