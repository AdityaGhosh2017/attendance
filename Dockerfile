FROM php:8.2-cli

# Copy code
COPY . /app
WORKDIR /app

# Expose port (Render uses $PORT)
EXPOSE $PORT

# Start PHP server
CMD php -S 0.0.0.0:$PORT