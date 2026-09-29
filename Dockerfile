FROM php:8.2-apache

# MySQL እና አስፈላጊ extensionዎችን ለመጫን
RUN docker-php-ext-install pdo pdo_mysql mysqli

# ፕሮጀክትህን ወደ Apache ፎልደር መቅዳት
COPY . /var/www/html/

# Apacheን በፖርት 80 ማስነሳት
EXPOSE 80
