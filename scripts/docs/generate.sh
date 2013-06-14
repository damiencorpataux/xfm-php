#!/bin/sh

# FIXME: update this script for doc-autogeneration using phpdoc2:
# 0. check that phpdoc2 is installed, if not: output an error message with link to officially recommended install instructions
# 1. clone xfm in the directory given as agrumant (default: /tmp)
# 2. switch to the branch given as argument (default: master)
# 2. generate documentation in the directory given as argument (default: /tmp/xfm-api-dpc-{branch})
# 3. wipe the xfm clone

# TODO: create another script (using the one above) to automatically update http://damiencorpataux.github.io/xfm-php/doc/api/master