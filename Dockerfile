# PHP公式の軽量イメージをベースに
FROM php:8.4-fpm-alpine AS php

# redis拡張モジュールをインストール
RUN apk add --no-cache autoconf build-base \
    && yes '' | pecl install redis \
    && docker-php-ext-enable redis

# MySQL接続用PDOをインストール
RUN docker-php-ext-install pdo_mysql

# curlライブラリを使う場合（API通信など）
RUN apk add -U --no-cache curl-dev \
    && docker-php-ext-install curl

# アップロード用ディレクトリを作成（www-dataユーザで所有）
RUN install -o www-data -g www-data -d /var/www/upload/image/

# ホスト側の php.ini をコンテナにコピー
COPY ./php.ini ${PHP_INI_DIR}/php.ini

# PHPのアップロードサイズ制限などを直接上書きする追加設定
RUN echo "upload_max_filesize=5M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size=6M" >> /usr/local/etc/php/conf.d/uploads.ini

# 権限設定（アップロードフォルダ用）
RUN mkdir -p /var/www/upload && \
    chown -R www-data:www-data /var/www/upload && \
    chmod 755 /var/www/upload

# 作業ディレクトリを /var/www/public に設定
WORKDIR /var/www/public

