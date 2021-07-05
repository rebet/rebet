#!/bin/bash

cd /tmp/sqlite
echo ".open ${SQLITE_DATABASE}" | sqlite3
sqlite3 ${SQLITE_DATABASE} < /tmp/${SQLITE_CREATE_TABLES}

exec "$@"
