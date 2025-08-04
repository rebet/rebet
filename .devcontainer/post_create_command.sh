echo "============================================================"
echo " Set Up Dev Container ..."
echo "============================================================"
echo "------------------------------------------------------------"
echo " Install php-cs-fixer"
echo "------------------------------------------------------------"
composer global require "friendsofphp/php-cs-fixer" --dev
echo ">> Done."

echo ""
echo "------------------------------------------------------------"
echo " Install Psysh"
echo "------------------------------------------------------------"
composer global require psy/psysh --dev
echo "> Done."

echo ""
echo "------------------------------------------------------------"
echo " Update global tools"
echo "------------------------------------------------------------"
composer global update --dev

echo ""
echo "------------------------------------------------------------"
echo " Install Vendor Modules"
echo "------------------------------------------------------------"
composer install
echo "> Done."

echo ""
echo "------------------------------------------------------------"
echo " Initialize SQLite Database"
echo "------------------------------------------------------------"
echo ".open /tmp/sqlite/rebet.db" | sqlite3 \
  && sqlite3 /tmp/sqlite/rebet.db < /workspace/.devcontainer/docker/workspace/sqlite/initdb/create_tables.sql
echo "> Done."

echo ""
echo "============================================================"
echo " Dev Container Was Ready"
echo "============================================================"
echo "Please run \`phpunit\` command to confirm that unit test will be passed first."
echo "And then, access below"
echo ""
echo " 1) Bash Aliases  "
echo "     You can find some lazy alias commands in \`~/.bash_aliases\`."
echo " 2) Adminer  "
echo "     http://[::1]:18080/"
echo "     * System: MySQL/MariaDB, Server: mariadb, Username: rebet, Password: rebet , Database: rebet (ROOT: root/root)"
echo "     * System: MySQL/MariaDB, Server: mysql  , Username: rebet, Password: rebet , Database: rebet (ROOT: root/root)"
echo "     * System: PostgreSQL   , Server: pgsql  , Username: rebet, Password: rebet , Database: rebet (ROOT: root/root)"
echo "     * System: SQLite       , Server:        , Username:      , Password: sqlite, Database: /tmp/sqlite/rebet.db"
echo ""
echo "NOTE: You can find this information in '/workspace/.devcontainer/post_create_command.sh'."
echo ""
