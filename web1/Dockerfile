FROM php:7.4-apache

# Habilitar el módulo de MySQL para PHP
RUN docker-php-ext-install pdo pdo_mysql

# Copiar el archivo index.php
COPY index.php /var/www/html/

# Exponer el puerto 80
EXPOSE 80
