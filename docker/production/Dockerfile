# =============================================================================
# Bagisto Production Image
# App container only: Nginx + PHP 8.3 FPM + Supervisor
# Database is external (separate MariaDB/MySQL service)
# =============================================================================

FROM ubuntu:24.04

ARG PHP_VERSION=8.3
ARG NODE_VERSION=20

ARG ADMIN_NAME=Administrator
ARG ADMIN_EMAIL=admin@example.com
ARG ADMIN_PASSWORD=bagisto123

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# ---------------------------------------------------------------------------
# Use reliable HTTP mirror (avoids network timeouts during build)
# ---------------------------------------------------------------------------
RUN set -eux; \
    printf 'Acquire::ForceIPv4 "true";\nAcquire::Retries "10";\nAcquire::http::Timeout "30";\n' \
        > /etc/apt/apt.conf.d/99bagisto-network; \
    for f in /etc/apt/sources.list /etc/apt/sources.list.d/*.list \
              /etc/apt/sources.list.d/*.sources; do \
        [ -f "$f" ] || continue; \
        sed -i 's|https://archive.ubuntu.com/ubuntu|http://us.archive.ubuntu.com/ubuntu|g' "$f"; \
        sed -i 's|http://archive.ubuntu.com/ubuntu|http://us.archive.ubuntu.com/ubuntu|g' "$f"; \
        sed -i 's|https://security.ubuntu.com/ubuntu|http://security.ubuntu.com/ubuntu|g' "$f"; \
    done

# ---------------------------------------------------------------------------
# System packages + Nginx + Supervisor
# ---------------------------------------------------------------------------
RUN apt-get -o Acquire::Retries=5 -o Acquire::http::Timeout=30 update \
    && apt-get -o Acquire::Retries=5 -o Acquire::http::Timeout=30 install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg \
        software-properties-common \
        unzip \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get -o Acquire::Retries=5 -o Acquire::http::Timeout=30 update \
    && apt-get -o Acquire::Retries=5 -o Acquire::http::Timeout=30 install -y \
        imagemagick \
        libmagickwand-dev \
        nginx \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# PHP 8.3 extensions (from ondrej/php PPA)
# ---------------------------------------------------------------------------
RUN apt-get -o Acquire::Retries=5 -o Acquire::http::Timeout=30 update \
    && apt-get -o Acquire::Retries=5 -o Acquire::http::Timeout=30 install -y \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-calendar \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-exif \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-gmp \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-sockets \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# Node.js (for Vite/Mix assets)
# ---------------------------------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# ---------------------------------------------------------------------------
# Application source (build context = repo root)
# ---------------------------------------------------------------------------
WORKDIR /var/www/bagisto

COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
    && rm -rf /root/.composer/cache

# ---------------------------------------------------------------------------
# .env defaults (overridden at runtime by entrypoint.sh)
# ---------------------------------------------------------------------------
RUN cp .env.example .env \
    && sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env \
    && sed -i 's/^DB_HOST=.*/DB_HOST=mariadb/' .env \
    && sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env \
    && sed -i 's/^DB_DATABASE=.*/DB_DATABASE=bagisto/' .env \
    && sed -i 's/^DB_USERNAME=.*/DB_USERNAME=bagisto/' .env \
    && sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=bagisto/' .env \
    && sed -i 's|^APP_URL=.*|APP_URL=http://localhost|' .env

# ---------------------------------------------------------------------------
# PHP configuration
# ---------------------------------------------------------------------------
COPY docker/production/php.ini /etc/php/${PHP_VERSION}/fpm/conf.d/99-production.ini
COPY docker/production/php.ini /etc/php/${PHP_VERSION}/cli/conf.d/99-production.ini
COPY docker/production/php-fpm.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# ---------------------------------------------------------------------------
# Nginx
# ---------------------------------------------------------------------------
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf
COPY docker/production/nginx.conf /etc/nginx/conf.d/bagisto.conf

# ---------------------------------------------------------------------------
# Supervisor
# ---------------------------------------------------------------------------
COPY docker/production/supervisord.conf /etc/supervisor/conf.d/bagisto.conf

# ---------------------------------------------------------------------------
# Runtime directories + permissions
# ---------------------------------------------------------------------------
RUN mkdir -p /run/php /var/log/supervisor /var/log/bagisto \
    && mkdir -p storage/framework/views storage/framework/cache/data \
                storage/framework/sessions storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache /var/log/bagisto \
    && chmod -R 775 storage bootstrap/cache \
    && find storage bootstrap/cache -type d -exec chmod g+s {} +

# ---------------------------------------------------------------------------
# Entrypoint + runtime installer
# ---------------------------------------------------------------------------
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/production/runtime-install.sh /usr/local/bin/runtime-install.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/runtime-install.sh

# ---------------------------------------------------------------------------
# Cleanup
# ---------------------------------------------------------------------------
RUN apt-get autoremove -y && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=300s --retries=5 \
    CMD curl -fsS http://127.0.0.1/ || exit 1

ENTRYPOINT ["bash", "/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
