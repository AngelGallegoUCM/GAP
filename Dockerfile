FROM arm64v8/php:8.2-apache
RUN docker-php-ext-install mysqli
