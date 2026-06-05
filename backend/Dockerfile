FROM serversideup/php:8.4-fpm-nginx-alpine AS production

ENV PHP_OPCACHE_ENABLE=1

USER root

RUN echo "" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf && \
    echo "user = www-data" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf && \
    echo "group = www-data" >> /usr/local/etc/php-fpm.d/docker-php-serversideup-pool.conf

RUN install-php-extensions intl imagick

COPY --chown=www-data:www-data . .

RUN chmod -R 755 storage \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 bootstrap/cache \
    && mkdir -p /var/lib/nginx/tmp \
    && chown -R www-data:www-data /var/lib/nginx \
    && chmod -R 755 /var/lib/nginx

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist

RUN mkdir -p /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer \
    && chmod -R 775 /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer \
    && chown -R www-data:www-data /var/www/html/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer

COPY docker/entrypoint.production.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
