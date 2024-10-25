#!/bin/bash

module=$(basename $PWD)
module=${module,,}
if [ -r "$module" ]; then
  echo "The directory $module already exists"
  exit 1
else
  mkdir $module
fi
mkdir -p zip

version=$(sed -n -e '/\$this->version = .[0-9]/s/.*= .\(.*\).;$/\1/p' $module.php)
echo "Version: $version"

if [ "$#" = 1 -a "$1" != "valid" ]; then
  echo "Must specify customer and file"
  exit 1
fi
if [ "$#" -gt 1 ]; then
  version=${version}_$1
  shift
  extra=$*
fi
if [ "$1" = "valid" ]; then
  version=$1_${version}
fi

files="
composer.json
logo.png
translations/index.php
translations/da.php
vendor/autoload.php
vendor/composer
vendor/composer/autoload_classmap.php
vendor/composer/LICENSE
vendor/composer/autoload_static.php
vendor/composer/platform_check.php
vendor/composer/ClassLoader.php
vendor/composer/autoload_psr4.php
vendor/composer/autoload_real.php
vendor/composer/autoload_namespaces.php
$module.php
$extra
"
tar -cf - $files | tar -C $module -xf - && \
rm -f zip/${module}_$version.zip && \
zip -r zip/${module}_$version.zip $module && \
rm -rf $module
