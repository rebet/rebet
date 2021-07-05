#!/bin/bash
sleep 20s

WORK_DIR=$(cd $(dirname $0) && pwd)
cd ${WORK_DIR}/initdb.d
for file in `ls *.sql`;
do
    /opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P ${MSSQL_SA_PASSWORD} -i ${file}
done

for file in `ls *.csv`;
do
    table_name=`basename ${file} .csv`
    /opt/mssql-tools/bin/bcp ${table_name} in ${file} -c -t',' -S localhost -U sa -P ${MSSQL_SA_PASSWORD}
done
