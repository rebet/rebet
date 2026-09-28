echo "============================================================"
echo " Initialize For Dev Container ..."
echo "============================================================"
SHARED_NETWORK_NAME="rebet.local"
echo "> Create shared network '${SHARED_NETWORK_NAME}' if not exists..."
if [ -z "`docker network ls | grep ${SHARED_NETWORK_NAME}`" ]; then
    docker network create ${SHARED_NETWORK_NAME} --subnet=172.1.0.0/16 --gateway=172.1.0.1 --ipv6 --subnet=fd01::/64;
fi
echo ">> Done."
