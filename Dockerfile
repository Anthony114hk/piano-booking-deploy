# Piano Booking WordPress image
# Base: official WordPress image (Apache + PHP 8.3), ARM64 supported
FROM wordpress:7-php8.3-apache

# Install WP-CLI for management (migrations, search-replace, etc.)
RUN curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
 && chmod +x /usr/local/bin/wp \
 && wp --info --allow-root > /dev/null

# Install persistent object-cache drop-in (uses Redis if REDIS_HOST is set)
# Keep it simple: rely on WP's transients for now. Add redis-cache later if needed.

# Copy our plugin + theme into the image
COPY wp-content/plugins/piano-booking/ /var/www/html/wp-content/plugins/piano-booking/
COPY wp-content/themes/piano-theme/    /var/www/html/wp-content/themes/piano-theme/

# Ensure correct ownership for Apache
RUN chown -R www-data:www-data /var/www/html/wp-content/plugins/piano-booking \
                          /var/www/html/wp-content/themes/piano-theme

# Enable Apache mod_rewrite for pretty permalinks
RUN a2enmod rewrite headers expires

# Healthcheck — WP responds at /wp-login.php with 200
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
  CMD curl -fsS http://localhost/wp-login.php >/dev/null || exit 1