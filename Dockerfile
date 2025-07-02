FROM php:8.2-apache

# Installer les dépendances
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli zip

# Activer le module rewrite d'Apache
RUN a2enmod rewrite

# Configurer les permissions du répertoire
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"] 