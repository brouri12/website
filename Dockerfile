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

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts \
    && composer dump-autoload --optimize --no-dev --classmap-authoritative

# Switch back to root for Apache
USER root

# Set final permissions
RUN chown -R www-data:www-data var/ \
    && chmod -R 777 var/

# Apache runs on port 80
EXPOSE 80

CMD ["apache2-foreground"]
