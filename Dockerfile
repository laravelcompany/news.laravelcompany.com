# Main application image
FROM php:8.3

# Install required system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    procps \
    gnupg \
    nodejs \
    npm \
    gosu \
    curl \
    ca-certificates \
    protobuf-compiler \
    zip \
    unzip \
    git \
    supervisor \
    sqlite3 \
    libcap2-bin \
    libpng-dev \
    dnsutils \
    librsvg2-bin \
    fswatch \
    nano \
    cargo \
    ffmpeg \
    poppler-utils \
    libzip-dev \
    libonig-dev \
    libjson-c-dev \
    build-essential \
    autoconf \
    zlib1g-dev \
    pkg-config \
    wget \
    libicu-dev \
    redis \
    golang \
    python3 \
    python3-pip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*



# Install PHP extensions
RUN docker-php-ext-install bcmath gd exif ftp zip pdo_mysql pcntl sockets intl && \
    docker-php-ext-enable bcmath gd exif ftp zip pcntl sockets
RUN mkdir -p /usr/src/php/ext/redis && \
    curl -fsSL https://pecl.php.net/get/redis --ipv4 | tar xvz -C "/usr/src/php/ext/redis" --strip 1 && \
    docker-php-ext-install redis

RUN curl -sSL https://getcomposer.org/download/latest-stable/composer.phar -o /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer

# Set up application
WORKDIR /var/www/

COPY . .

RUN cp .env.example .env

# Install PHP dependencies
RUN composer install --no-interaction --no-suggest --ignore-platform-req=ext-gd --ignore-platform-req=ext-exif --ignore-platform-req=ext-ftp

# Install and build Node.js assets
RUN npm install --legacy-peer-deps

RUN npm run build

RUN php artisan key:generate
# Configure Supervisor
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory back to application root
WORKDIR /var/www/
COPY . .

# Install Python packages
WORKDIR /var/www/backend/
RUN pip install --no-cache-dir --upgrade -r /var/www/backend/requirements.txt --break-system-packages
RUN pip install lxml[html_clean] --break-system-packages
RUN pip install lxml_html_clean --break-system-packages
RUN pip install spacy trendspy justext text2emotion pymupdf4llm python-multipart sqlalchemy yake fastapi_versioning tls_client uvicorn gnews --break-system-packages
RUN python3 -m spacy download en_core_web_md --break-system-packages
RUN python3 -m textblob.download_corpora --break-system-packages
WORKDIR /var/www/

# Expose ports
EXPOSE 1600 1601 5173 443

# Start Supervisor
ENTRYPOINT ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
