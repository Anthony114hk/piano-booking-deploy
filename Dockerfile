# Piano Booking WordPress image
# Base: official WordPress image (Apache + PHP 8.3), ARM64 supported
FROM wordpress:7-php8.3-apache

# CACHE_BUST is passed by CI (or local build) to force COPY layers to
# re-run when source files change. Without this, GHA's persistent
# /var/lib/docker can serve stale layers even with buildx --no-cache.
ARG CACHE_BUST=unknown
LABEL org.opencontainers.image.revision=$CACHE_BUST

# Install WP-CLI for management (migrations, search-replace, etc.)
RUN curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
 && chmod +x /usr/local/bin/wp \
 && wp --info --allow-root > /dev/null

# Install persistent object-cache drop-in (uses Redis if REDIS_HOST is set)
# Keep it simple: rely on WP's transients for now. Add redis-cache later if needed.

# Copy our plugin + theme into the image
COPY wp-content/plugins/piano-booking/ /var/www/html/wp-content/plugins/piano-booking/
COPY wp-content/themes/piano-theme/    /var/www/html/wp-content/themes/piano-theme/

# Cache-bust marker: re-touch a file with CACHE_BUST content so the
# downstream RUN layer is forced to re-execute.
ARG CACHE_BUST=unknown
RUN echo "Build: $CACHE_BUST" > /var/www/html/wp-content/themes/piano-theme/.build-id && \
    chown www-data:www-data /var/www/html/wp-content/themes/piano-theme/.build-id

# Ensure correct ownership for Apache
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins/piano-booking \
                          /var/www/html/wp-content/themes/piano-theme

# Enable Apache mod_rewrite for pretty permalinks
RUN a2enmod rewrite headers expires

# Healthcheck — WP responds at /wp-login.php with 200
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD curl -fsS http://localhost/wp-login.php >/dev/null || exit 1