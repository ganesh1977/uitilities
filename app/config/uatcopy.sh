#!/bin/bash
# Albert Lombarte
# Docs: http://www.harecoded.com/copycloneduplicate-mysql-database-script-2184438

# Read Password
echo -n Password: 
read -s password
echo

PRODUCTION_DB=sym_utils
# The following database will be DELETED first:
COPY_DB=sym_uat_utils
USER=utils
PASS=$password
ERROR=/tmp/duplicate_mysql_error.log
echo "Droping '$COPY_DB' and generating it from '$PRODUCTION_DB' dump"
ssh -C anders@10.101.82.234 "mysql -u$USER -p$PASS -e 'drop database $COPY_DB;' --force && mysql -u$USER -p$PASS -e 'create database $COPY_DB;' && mysqldump --force --log-error=$ERROR -u$USER -p$PASS $PRODUCTION_DB" | ssh -C anders@10.101.82.234 "mysql -u$USER -p$PASS $COPY_DB"