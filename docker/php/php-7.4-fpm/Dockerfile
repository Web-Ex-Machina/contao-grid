ARG image
################################
# Minimal image
################################
FROM $image AS php_base
ARG workdir_bundle
ARG workdir_bundle_end_slash

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
        zip \
        unzip \
        libzip-dev \
        libxml2-dev \
        libgmp-dev \
        re2c \
        libmhash-dev \
        file \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pdo \
    && docker-php-ext-install pdo \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-install pdo_mysql\
    && docker-php-ext-configure gmp \
    && docker-php-ext-install gmp \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip
    # Use the default development configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    # Install Composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && mkdir -p $workdir_bundle \
    && cd $workdir_bundle

WORKDIR $workdir_bundle_end_slash

# ENTRYPOINT ["/docker-entrypoint.sh"]
################################
# Full image base for test & dev
################################
FROM php_base AS php_base_full
ARG app_secret
ARG workdir_contao
ARG workdir_simlink_base
ARG workdir_bundle
ARG workdir_init

# Here we can have additional work to be done specifically for the test container
RUN apt-get update && apt-get install -y \
    git \
    npm \
    && mkdir -p ~/.ssh \
    && mkdir -p /var/www/.npm \
    && ssh-keyscan github.com >> ~/.ssh/known_host \
    # Install contao
    cd / \
    && mkdir -p $workdir_contao \
    # We give the www-data the same user UUID as ours
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data \
    && chown -R www-data:www-data $workdir_contao \
    && chown -R www-data:www-data /usr/local/bin/composer \
    && chown -R www-data:www-data /var/www/.npm \
    # get contao into a fake dir and then move it to the real install-dir
    && mkdir -p /fake \
    && cd /fake \
    && rm -r $workdir_contao/* \
    && cp -ar /fake/. $workdir_contao \
    && cd $workdir_contao \
    # Also clean up cache
    && rm -fr /var/cache/*

COPY --chown=www-data:www-data ./.env $workdir_contao/.env

RUN sed -i "s~{{APP_SECRET}}~$app_secret~g" $workdir_contao/.env \
    && chown -R www-data:www-data $workdir_contao

WORKDIR $workdir_contao

CMD php-fpm -F
################################
# Image for test
################################
FROM php_base_full AS php_test

WORKDIR $workdir_contao

CMD php-fpm -F

################################
# Image for dev
################################
FROM php_base_full AS php_dev

RUN apt-get update && apt-get install -y nano

WORKDIR $workdir_contao

CMD php-fpm -F

################################
# Image for unit tests
################################
FROM php_base AS php_test_unit

WORKDIR $workdir_end_slash

# ENTRYPOINT ["/docker-entrypoint.sh"]
