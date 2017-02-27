#!/bin/bash

# Required, so that Travis marks the build as failed if any of the steps below fail
set -ev

# Check to see if this script has access to all the commands it needs
for CMD in sshfs rsync find fusermount mkdir; do
  type $CMD 2>&1 > /dev/null

  if [ $? -ne 0 ]; then
    echo
    echo "ERROR: The script cannot find the required command \"${CMD}\"."
    echo
    exit $?
  fi
done

mkdir -p ./zmrepo
ssh_mntchk="$(sshfs zmrepo@zmrepo.zoneminder.com:./ ./zmrepo -o workaround=rename,reconnect)"

if [ -z "$ssh_mntchk" ]; then
    # Don't keep packages older than 5 days
    find ./zmrepo -maxdepth 1 -type f -mtime +5 -delete
    rsync --ignore-errors ./build/ ./zmrepo/
    fusermount -zu zmrepo
else
    echo
    echo "ERROR: Attempt to mount zmrepo.zoneminder.com failed!"
    echo "sshfs gave the following error message:"
    echo \"$ssh_mntchk\"
    echo
fi

