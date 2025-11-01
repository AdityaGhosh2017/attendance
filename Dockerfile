# Use official PHP image with Apache
FROM php:8.2-apache

# Copy all project files into the web root
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
