# --- Stage 1: 基礎環境 (Base) ---
# 使用 Alpine Linux 縮小體積並提高安全性
FROM php:8.3-fpm-alpine AS base

# 設定工作目錄
WORKDIR /var/www/html

# 安裝運行時(Runtime)必須的系統套件 (這些不會被刪除)
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    mariadb-client \
    icu-libs

# 安裝 PHP 擴充功能 (使用虛擬套件管理，編譯完即刪除編譯器)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        bcmath \
        gd \
        intl \
        opcache \
    && apk del .build-deps

# 從官方鏡像複製最新版 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Stage 2: 開發環境 (Development) ---
FROM base AS development

# 安裝並啟用 Xdebug 以支援除錯
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers\
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

# 建立與 WSL 2 / Mac 使用者一致的非 root 帳號
RUN adduser -D -u 1000 appuser
USER 1000:1000

# --- Stage 3: 正式環境 (Production) ---
FROM base AS production

# 複製專案原始碼
COPY . .

# 執行正式環境 Composer 優化
RUN composer install --no-dev --optimize-autoloader --no-scripts

# 設定目錄權限，確保 Laravel 能寫入 Logs 和 Cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 正式環境以 www-data 執行
USER www-data
