#{%-- if $session == 'native' -%}
echo ""
echo "------------------------------------------------------------"
echo " Prepare session directory and clean old session files..."
echo "------------------------------------------------------------"
SESSION_DIR=/workspace/.devcontainer/docker/php-fpm/var/session
mkdir -p ${SESSION_DIR}
# PHP-FPM runs as 'www-data', so the session directory must be writable by any users.
chmod 0777 ${SESSION_DIR}
find ${SESSION_DIR} -maxdepth 1 -type f -mtime +1 -exec rm -f {} \;
echo ">> Done."
#{%-- endif -%}
