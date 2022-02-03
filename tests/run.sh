#!/bin/bash
dir=$(cd "$(dirname "$0")";pwd);

cd $dir
php ${dir}/../vendor/phpunit/phpunit/phpunit $* .

