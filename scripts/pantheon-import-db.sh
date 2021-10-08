#!/bin/bash

echo "Enter Pantheon site name (e.g., utexas-its):"
read SITE
echo "Checking validity..."
ID=$(terminus site:info "$SITE" --field=ID)
if [ ! $ID ];
then
  echo "No Pantheon site.env found matching $SITE"
  return
fi
echo "Enter the destination environment (dev, test, live):"
read ENV
echo "Ensuring environment is woke..."
terminus env:wake $SITE.$ENV
echo "Enter the SQL filename located in this directory (e.g., filename.sql)"
read FILENAME
if [ ! -f $FILENAME ];
then
  echo "No file found called $FILENAME"
  return
fi
# Get the MySQL CLI command
echo "Importing. This may take some time..."
PANTHEON_SQL_CMD=`terminus connection:info $SITE.$ENV --field=mysql_command`
$PANTHEON_SQL_CMD < $FILENAME
say "Database import complete"
