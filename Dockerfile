FROM php:8.2-cli

COPY . /app
WORKDIR /app

EXPOSE $PORT

CMD php -S 0.0.0.0:$PORT