# Dockerfile - For Render.com PHP Deployment (PDO MySQL Enabled)
FROM php:8.3-cli

# Install PDO MySQL driver (fixes "could not find driver")
RUN docker-php-ext-install pdo_mysql

# Optional: For extra compatibility
RUN docker-php-ext-install mysqli

# Copy all app files
COPY . /app
WORKDIR /app

# Expose Render's dynamic port
EXPOSE $PORT

# Start PHP built-in server
CMD php -S 0.0.0.0:$PORT