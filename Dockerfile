FROM php:7.2-apache

# Install dependencies needed for gd extension
RUN apt-get update -y && apt-get install -y libpng-dev libfreetype6-dev

# Configure and install gd extension needed for image funcitons
RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/freetype2
RUN docker-php-ext-install gd

# Copy source code and assets
COPY . /var/www/html/