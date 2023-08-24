#!/bin/bash

# Server wide access
# - Runs after files have been deployed
# - Runs as root
# - Runs from within site /.gpconfig dir

if [[ -z $GIT_SITE_BUILD ]]; then
  echo "This will fire only when a new site is NOT being added."
  # This will disable maintenance mode for all sites on the server.
  gpgit server -maintenance disable
fi