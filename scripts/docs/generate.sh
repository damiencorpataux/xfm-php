#!/bin/sh

phpdoc_dir=PhpDocumentor
source_dir=../../lib
output_dir=../../docs/api/trunk

# Empties output directory
rm -rf $output_dir/*

# Generates HTML Documentation
php $phpdoc_dir/phpdoc -o HTML:frames:earthli -d $source_dir -t $output_dir

# Renames files (PhpDocumentor bug?)
# - *.cs -> *.css
# - *.pn -> *.png
find ../../docs/api/trunk/media -iname *.cs -exec mv {} {}s \;
find ../../docs/api/trunk/media -iname *.pn -exec mv {} {}g \;

