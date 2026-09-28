#{%-- if $session == 'native' -%}
echo ""
echo "------------------------------------------------------------"
echo " Clean old session files..."
echo "------------------------------------------------------------"
find /workspace/.devcontainer/docker/php/var/session -maxdepth 1 -type f -mtime +1 -exec rm -f {} \;
echo ">> Done."
#{%-- endif -%}
