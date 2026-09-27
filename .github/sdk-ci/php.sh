#!/bin/sh
set -eu
mkdir -p /results/sdk
cp /sdk/composer.json /results/sdk/
cp -R /sdk/lib /results/sdk/
composer install --working-dir=/results/sdk --no-dev --no-scripts --no-plugins --prefer-dist --no-interaction
SDK_DIRECTORY=/results/sdk php /suite/recording.php
SDK_DIRECTORY=/results/sdk REACON_TEST_URL="$REACON_STREAM_TEST_URL" php /suite/stream.php
