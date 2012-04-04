#!/bin/sh

CURRENT_YEAR=`date +%Y`
LAST_YEAR=$((CURRENT_YEAR-1))

SEARCH="(c) 2010"
REPLACE="(c) $CURRENT_YEAR"

rgrep -l -i "$SEARCH" * | grep -v "svn" | grep -v "bump_copyright_year.sh"
rgrep -l -i "$SEARCH" * | grep -v "svn" | grep -v "bump_copyright_year.sh" | xargs sed -i s/"$SEARCH"/"$REPLACE"/