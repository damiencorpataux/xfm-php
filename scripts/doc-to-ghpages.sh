#!/bin/sh

# Configuration
TMP_ROOT=/tmp/xfm-php-doc-generator
DOC_ROOT=generated

# Adds ssh passphrase
eval "$(ssh-agent)" && ssh-add

# Setup working directory
rm -rf $TMP_ROOT
mkdir -p $TMP_ROOT

# Generates API doc
cd $TMP_ROOT
git clone https://github.com/damiencorpataux/xfm-php.git
cd xfm-php
mkdir -p $TMP_ROOT/$DOC_ROOT/doc/api/master
phpdoc --directory="." --sourcecode --visibility="public,protected" --title="xfm API Documentation (`git rev-parse --abbrev-ref HEAD`, `date +%d-%b-%Y`)" --target="$TMP_ROOT/$DOC_ROOT/doc/api/master"

# Push documentation on gh-pages
cd $TMP_ROOT
git clone --branch gh-pages git@github.com:damiencorpataux/xfm-php.git gh-pages
cd gh-pages
for path in "doc/api/master"
do
    git rm -rf $path
    mkdir -p $path
    cp -r $TMP_ROOT/$DOC_ROOT/$path/. $path/.
done
git add . && git commit -m"Generated documentation automatic update"
git push

# Cleans working directory
rm -rf $TMP_ROOT
