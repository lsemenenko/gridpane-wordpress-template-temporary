#!/usr/bin/env bash
set -x
# Server level access
# - Runs before files have been deployed
# - Runs as root user
# - Runs from within site /.gpconfig dir
env
#timeout 300 wget https://getcomposer.org/download/2.5.4/composer.phar
#timeout 300 chmod 755 ../wp-content/plugins/breakdance/plugin/vendor/bin/mozart
#export COMPOSER_ALLOW_SUPERUSER=1
#timeout 300 php composer.phar update -n --working-dir=../wp-content/plugins/breakdance/plugin/
#timeout 300 php composer.phar install -n --working-dir=../wp-content/plugins/breakdance/plugin/

timeout 300 wget https://getcomposer.org/download/2.5.4/composer.phar
timeout 300 chmod 755 $GP_GIT_RELEASE_PATH/wp-content/plugins/breakdance/plugin/vendor/bin/mozart
export COMPOSER_ALLOW_SUPERUSER=1
timeout 300 php composer.phar update -n --working-dir=$GP_GIT_RELEASE_PATH/wp-content/plugins/breakdance/plugin/
timeout 300 php composer.phar install -n --working-dir=$GP_GIT_RELEASE_PATH/wp-content/plugins/breakdance/plugin/