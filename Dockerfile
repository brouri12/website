FROM php:8.2-apache

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set document root and configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure ServerName globally
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure Apache for proper binding and timeouts
RUN echo "Listen 0.0.0.0:80" > /etc/apache2/ports.conf \
    && echo "Timeout 120" >> /etc/apache2/apache2.conf \
    && echo "KeepAlive On" >> /etc/apache2/apache2.conf \
    && echo "KeepAliveTimeout 120" >> /etc/apache2/apache2.conf \
    && echo "MaxKeepAliveRequests 100" >> /etc/apache2/apache2.conf

# Configure Apache error handling and timeouts
RUN echo "ErrorLog /dev/stderr" >> /etc/apache2/apache2.conf \
    && echo "CustomLog /dev/stdout combined" >> /etc/apache2/apache2.conf \
    && echo "TimeOut 300" >> /etc/apache2/apache2.conf \
    && echo "GracefulShutdownTimeout 300" >> /etc/apache2/apache2.conf

# Set environment variables
ENV PORT=80
ENV HOST=0.0.0.0

# Create non-root user
RUN useradd -ms /bin/bash symfony

# Create symfony directory structure
RUN mkdir -p var/cache var/log

# Copy application files
COPY --chown=symfony:symfony . /var/www/html/
WORKDIR /var/www/html/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Switch to non-root user
USER symfony

# Set Composer and Symfony environment
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies
RUN composer install --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative

# Switch back to root for Apache
USER root

# Install additional dependencies
RUN apt-get update && apt-get install -y \
    curl \
    libfcgi0ldbl

# Configure Apache MPM prefork
RUN a2dismod mpm_event && \
    a2enmod mpm_prefork && \
    { \
        echo '<IfModule mpm_prefork_module>'; \
        echo '    StartServers             5'; \
        echo '    MinSpareServers          5'; \
        echo '    MaxSpareServers         10'; \
        echo '    MaxRequestWorkers      150'; \
        echo '    MaxConnectionsPerChild   0'; \
        echo '</IfModule>'; \
    } > /etc/apache2/mods-available/mpm_prefork.conf

# Set proper permissions and cleanup
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && find /var/www/html/var -type d -exec chmod 777 {} \;

# Apache runs on port 80
EXPOSE 80

# Improved healthcheck
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:${PORT}/health.php || exit 1

# Start Apache with proper error handling
CMD apache2ctl -D FOREGROUND
