#!/bin/bash

# Check to see if this script has access to all the commands it needs
for CMD in sshfs rsync find fusermount mkdir; do
  type $CMD 2>&1 > /dev/null

  if [ $? -ne 0 ]; then
    echo
    echo "ERROR: The script cannot find the required command \"${CMD}\"."
    echo
    exit 1
  fi
done

# We only want to deploy packages during cron events
# See https://docs.travis-ci.com/user/cron-jobs/
if [ "${TRAVIS_EVENT_TYPE}" == "cron" ] || [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ]; then

    if [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ]; then
        targetfolder="debian/master/mini-dinstall/incoming"
    else
        targetfolder="travis"
    fi

    echo
    echo "Target subfolder set to $targetfolder"
    echo
    if [ "${USE_SFTP}" == "yes" ]; then
      results="$(rsync build/* zmrepo@zmrepo.zoneminder.com:${targetfolder}/ 2>&1)"
      if [ -z "$results" ]; then
        echo 
        echo "Files copied successfully."
        echo
      else 
        echo
        echo "ERROR: Attempt to rsync to zmrepo.zoneminder.com failed!"
        echo "rsync gave the following error message:"
        echo \"$results\"
        echo
        exit 99
      fi
    else
      mkdir -p ./zmrepo
      ssh_mntchk="$(sshfs zmrepo@zmrepo.zoneminder.com:./ ./zmrepo -o workaround=rename,reconnect 2>&1)"

      if [ -z "$ssh_mntchk" ]; then
          echo
          echo "Remote filesystem mounted successfully."
          echo "Begin transfering files..."
          echo

          # Don't keep packages older than 5 days
          find ./zmrepo/$targetfolder/ -maxdepth 1 -type f,l -mtime +5 -delete
          rsync -vzlh --ignore-errors build/* zmrepo/$targetfolder/
          fusermount -zu zmrepo
      else
          echo
          echo "ERROR: Attempt to mount zmrepo.zoneminder.com failed!"
          echo "sshfs gave the following error message:"
          echo \"$ssh_mntchk\"
          echo
          exit 99
      fi
    fi
fi
