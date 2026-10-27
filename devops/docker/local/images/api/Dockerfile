FROM php:8.4-cli as base

RUN apt-get update
RUN apt-get install -y curl --no-install-recommends
RUN apt-get install -y libxml2-dev --no-install-recommends
RUN apt-get install -y libpq-dev --no-install-recommends
RUN apt-get install -y libzip-dev --no-install-recommends
RUN apt-get install -y libevent-dev --no-install-recommends
RUN apt-get install -y libonig-dev --no-install-recommends
RUN apt-get install -y openssl --no-install-recommends
RUN apt-get install -y libssl-dev --no-install-recommends
RUN apt-get install -y libcurl4-openssl-dev --no-install-recommends
RUN apt-get install -y libicu-dev --no-install-recommends
RUN apt-get install -y wget --no-install-recommends
RUN apt-get install -y zip --no-install-recommends
RUN apt-get install -y unzip --no-install-recommends
RUN apt-get install -y libaio-dev --no-install-recommends


RUN docker-php-ext-install curl
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install sockets
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath

RUN rm -rf /tmp/pear && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN cd ~ && curl -sS https://getcomposer.org/installer -o composer-setup.php && \
	php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
	&& chmod +x /usr/local/bin/composer

RUN mkdir -p /code
WORKDIR /code

FROM base as dev-build
RUN echo "dev-build"
