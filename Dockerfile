FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libicu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli zip intl
RUN docker-php-ext-configure intl

# Enable Apache modules
# RUN a2enmod rewrite headers
RUN a2enmod rewrite

# Set document root to public folder
# ENV APACHE_DOCUMENT_ROOT /var/www/html/platform_gmao/public

# Update Apache configuration
# RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
# RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Add Alias for platform_gmao to point to the public directory
# RUN echo 'Alias /platform_gmao ${APACHE_DOCUMENT_ROOT}\n\
# <Directory ${APACHE_DOCUMENT_ROOT}>\n\
#     Options Indexes FollowSymLinks\n\
#     AllowOverride All\n\
#     Require all granted\n\
# </Directory>' > /etc/apache2/conf-available/gmao-alias.conf
# RUN a2enconf gmao-alias

# Set working directory
WORKDIR /var/www/html/platform_gmao

# Configure permissions
# RUN chown -R www-data:www-data /var/www/html
# RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
# CMD ["apache2-foreground"] 