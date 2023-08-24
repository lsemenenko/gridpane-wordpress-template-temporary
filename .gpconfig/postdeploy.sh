#!/bin/bash

# Site level access
# - Runs after files have been deployed
# - Runs as site's system user
# - Runs from within site /.gpconfig dir

#!/bin/bash
# Example gridpane MT site post/pre deploy script

if [[ -n $GP_GIT_SITE_PREVIOUS_PREDEPLOY_FAILURE ]]; then
  echo "This will fire only when the site has failed pre-deployment on the last run."
fi

if [[ -n $GP_GIT_SITE_PREVIOUS_POSTDEPLOY_FAILURE ]]; then
  echo "This will fire only when the site has failed post-deployment on the last run."
fi

if [[ -n $GIT_SITE_BUILD ]]; then
  echo "This will fire only when a new site is being added."
fi

if [[ -z $GIT_SITE_BUILD && $GP_GIT_SITE == "example.com" ]]; then
  echo "This will only fire for the specific site example.com."
fi

if [[ -z $GIT_SITE_BUILD && $GP_GIT_SITE == *"example.com" ]]; then
  echo "This will fire for example.com and all example.com subdomains."
fi
