# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/html

# Install PHP dependencies (exclude dev dependencies for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies and build assets
RUN npm ci && npm run build

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create directories for Passport keys and ensure proper permissions
RUN mkdir -p /var/www/html/app/secrets/oauth \
    && chown -R www-data:www-data /var/www/html/app/secrets \
    && chmod -R 755 /var/www/html/app/secrets

# Create Apache configuration
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    ServerName localhost\n\
    \n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Create basic .env file for container (without APP_KEY - will be generated at runtime)
RUN echo 'APP_NAME=AttendPro\n\
APP_ENV=production\n\
APP_DEBUG=false\n\
APP_URL=http://localhost:8000\n\
\n\
DB_CONNECTION=mysql\n\
DB_HOST=mysql\n\
DB_PORT=3306\n\
DB_DATABASE=employee_attendance\n\
DB_USERNAME=attendance_user\n\
DB_PASSWORD=password123\n\
\n\
CACHE_DRIVER=database\n\
SESSION_DRIVER=database\n\
QUEUE_CONNECTION=database\n\
MAIL_MAILER=log' > /var/www/html/.env

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Generate APP_KEY manually using PHP\n\
echo "Generating application key manually..."\n\
APP_KEY_VALUE=$(php -r "echo '\''base64:'\'' . base64_encode(random_bytes(32));")\n\
echo "Generated key: $APP_KEY_VALUE"\n\
\n\
# Update .env file with the generated key\n\
if grep -q "^APP_KEY=" /var/www/html/.env; then\n\
    sed -i "s/^APP_KEY=.*/APP_KEY=$APP_KEY_VALUE/" /var/www/html/.env\n\
else\n\
    echo "APP_KEY=$APP_KEY_VALUE" >> /var/www/html/.env\n\
fi\n\
\n\
echo "APP_KEY set in .env:"\n\
grep "^APP_KEY=" /var/www/html/.env\n\
\n\
# Wait for database to be ready\n\
echo "Waiting for database..."\n\
until php artisan migrate:status >/dev/null 2>&1; do\n\
    echo "Database not ready, waiting..."\n\
    sleep 2\n\
done\n\
\n\
# Run fresh migrations with seeding\n\
php artisan migrate:fresh --seed --force\n\
\n\
# Generate Passport keys and ensure proper permissions\n\
echo "Checking and setting up Passport keys..."\n\
php artisan passport:keys --force\n\
\n\
# Ensure the personal access client exists\n\
if ! php artisan passport:client --list | grep -q "Personal Access Client"; then\n\
    echo "Creating personal access client..."\n\
    php artisan passport:client --personal --name="Personal Access Client" --no-interaction\n\
fi\n\
\n\
# Always set correct permissions for OAuth keys (600 for private, 660 for public)\n\
if [ -f "/var/www/html/app/secrets/oauth/oauth-private.key" ]; then\n\
    echo "Setting correct permissions for OAuth keys..."\n\
    chmod 600 /var/www/html/app/secrets/oauth/oauth-private.key\n\
    chmod 660 /var/www/html/app/secrets/oauth/oauth-public.key\n\
    chown www-data:www-data /var/www/html/app/secrets/oauth/oauth-*.key\n\
fi\n\
\n\
\n\
# Clear and cache config\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
echo "Starting Apache server..."\n\
# Start Apache\n\
apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Start with custom script
CMD ["/usr/local/bin/start.sh"]
