FROM php:8.2-cli

# Installer dépendances système et extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zlib1g-dev \
    && docker-php-ext-install zip pdo pdo_mysql intl mbstring xml

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /app

# Copier le projet
COPY . .

# Installer les dépendances Composer avec gestion des permissions
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && chown -R www-data:www-data /app/var

# Exposer le port
EXPOSE 10000

# Lancer Symfony
CMD php -S 0.0.0.0:10000 -t public
