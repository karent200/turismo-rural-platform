FROM php:8.2-apache

# Security: Non-root user
ARG PUID=1000
ARG PGID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libmariadb-dev \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo pdo_mysql zip \
    && docker-php-ext-enable pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Security: Hide server version info
RUN sed -i 's/^ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-available/security.conf \
    && sed -i 's/^ServerSignature On/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
    && sed -i 's/^TraceEnable On/TraceEnable Off/' /etc/apache2/conf-available/security.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY ./public /var/www/html/
COPY ./src /var/www/html/src/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80

# Run as non-root for security
USER www-data

CMD ["apache2-foreground"]
