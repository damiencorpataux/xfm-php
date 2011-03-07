#!/bin/sh

phpdoc_dir=PhpDocumentor
source_dir=../../lib
output_dir=../../docs/api/trunk

# Empties output directory
rm -rf $output_dir/*

# Generates HTML Documentation
php $phpdoc_dir/phpdoc -o HTML:frames:earthli -d $source_dir -t $output_dir -dn xFreemwork -s -ti "xFreemwork API Documentation (trunk)" > /dev/null

# Renames files (PhpDocumentor bug?)
# - *.cs -> *.css
# - *.pn -> *.png
find $output_dir/media -iname *.cs -exec mv {} {}s \;
find $output_dir/media -iname *.pn -exec mv {} {}g \;

