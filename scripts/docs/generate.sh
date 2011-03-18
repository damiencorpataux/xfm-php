#!/bin/sh

###############################################################################
# Configuration
#

# Directories
phpdoc_dir=PhpDocumentor
base_dir=../..
source_dir=$base_dir/lib
output_dir=$base_dir/docs/api/trunk

# Title
svn_revision=`svn info $base_dir | grep Revision | cut -b11-`
svn_date=`svn info $base_dir | grep Date | cut -b20-38`
title="xFreemwork API Documentation<br/>(trunk, revision $svn_revision, $svn_date)"

###############################################################################
# Processing
#

# Empties output directory
rm -rf $output_dir/*

# Generates HTML Documentation
php $phpdoc_dir/phpdoc -o HTML:frames:earthli -d $source_dir -t $output_dir -dn xFreemwork -s -ti "$title" > /dev/null

# Renames files (PhpDocumentor bug?)
# - *.cs -> *.css
# - *.pn -> *.png
find $output_dir/media -iname *.cs -exec mv {} {}s \;
find $output_dir/media -iname *.pn -exec mv {} {}g \;

