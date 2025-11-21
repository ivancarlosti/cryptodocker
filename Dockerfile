FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libgmp-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install gmp bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first to leverage cache
COPY composer.json ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application files
COPY index.php ./

# Enable Apache mod_rewrite if needed (not strictly needed for this single file app but good practice)
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80
