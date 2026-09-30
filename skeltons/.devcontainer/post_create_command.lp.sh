echo "============================================================"
echo " Set Up Dev Container ..."
echo "============================================================"

echo ""
echo "------------------------------------------------------------"
echo " Install Composer Dependencies"
echo "------------------------------------------------------------"
echo "> composer install..."
composer install -d /workspace/app
echo ">> Done."

echo ""
echo "============================================================"
echo " Dev Container Was Ready"
echo "============================================================"
#{%-- if $is_localhost_domain -%}
echo "'{! $site_domain !}' usually resolves to 127.0.0.1 without editing your hosts file."
echo "If needed (ex. your browser/OS can not resolve it), write '127.0.0.1 {! $site_domain !}' in your hosts file."
#{%-- else -%}
echo "Please write '127.0.0.1 {! $site_domain !}' in your hosts file."
#{%-- endif -%}
echo "And then, access below"
echo ""
echo " - Site Top  "
echo "     * https://{! $site_domain !}/"
#{%-- if $use_db && $database != 'sqlite' -%}
echo " - Adminer  "
echo "     * https://{! $site_domain !}/adminer/"
#{%-- endif -%}
echo " - Mailpit  "
echo "     * https://{! $site_domain !}/mailpit/"
echo " - Traefik Dashboard (shared by all Rebet applications)  "
echo "     * https://traefik.localhost/"
echo " - SSL Add Trusted Certificate (only once, shared by all Rebet applications)  "
echo "     1) Get root CA pem on your host  "
echo "        * ~/.rebet/traefik/certs/rootCA.pem"
echo "     2) Add trusted CA  "
echo "        * macOS  : sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ~/.rebet/traefik/certs/rootCA.pem"
echo "        * Windows: certutil -addstore Root rootCA.pem (with administrator privileges)"
echo "        * Linux  : sudo cp ~/.rebet/traefik/certs/rootCA.pem /usr/local/share/ca-certificates/rebet-rootCA.crt && sudo update-ca-certificates"
echo ""
echo "NOTE: You can find this information in '/workspace/.devcontainer/post_create_command.sh'."
echo ""
