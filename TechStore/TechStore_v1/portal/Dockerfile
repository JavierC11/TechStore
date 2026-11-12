FROM php:8.2-apache

# Instalar extensiones PDO MySQL y mysqli
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite para URLs amigables (.htaccess)
RUN a2enmod rewrite headers

# Permitir AllowOverride All en /var/www/html para que .htaccess funcione
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Configurar límites de carga para fotografías de productos
RUN { \
    echo "upload_max_filesize = 16M"; \
    echo "post_max_size = 20M"; \
    echo "memory_limit = 256M"; \
    echo "date.timezone = America/Guatemala"; \
} > /usr/local/etc/php/conf.d/pulso-custom.ini

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/productos \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80

