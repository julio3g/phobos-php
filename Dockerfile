FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# DocumentRoot = public/ (src, views, .env ficam fora da raiz web)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's#/var/www/html#${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's#/var/www/#${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/phobos.conf \
 && a2enconf phobos

# uploads maiores que o padrão de 2MB do PHP (limite da app: 5MB)
RUN printf 'upload_max_filesize=6M\npost_max_size=8M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/phobos.ini

WORKDIR /var/www/html
COPY . .
RUN chmod +x docker/entrypoint.sh \
 && mkdir -p public/uploads && chown -R www-data:www-data public/uploads

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
