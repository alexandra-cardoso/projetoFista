# syntax=docker/dockerfile:1

# Use the official PHP-Apache image as a base
FROM php:8.2-apache

# Update package list and install necessary dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql

# Enable Apache modules (optional, for example mod_rewrite)
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy application code into the container (optional)
COPY ./src /var/www/html

# Expose port 80 (default for Apache)
EXPOSE 80

# Start Apache (this is already handled by the base image's CMD)

USER www-data