#!/bin/bash

# We don't deploy during eslint checks, so exit immediately
if [ "${DIST}" == "eslint" ]; then
  exit 0
fi

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

if [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ] || [ "${OS}" == "raspbian" ]; then
  if [ "${RELEASE}" != "" ]; then
    IFS='.' read -r -a VERSION_PARTS <<< "$RELEASE"
    if [ "${VERSION_PARTS[0]}.${VERSION_PARTS[1]}" == "1.30" ]; then
      targetfolder="debian/release/mini-dinstall/incoming"
    else
      targetfolder="debian/release-${VERSION_PARTS[0]}.${VERSION_PARTS[1]}/mini-dinstall/incoming"
    fi
  else
    targetfolder="debian/master/mini-dinstall/incoming"
  fi
else
  targetfolder="travis"
fi

echo
echo "Target subfolder set to $targetfolder"
echo

echo "Running \$(rsync -v -e 'ssh -vvv' build/*.{rpm,deb,dsc,tar.xz,buildinfo,changes} zmrepo@zmrepo.zoneminder.com:${targetfolder}/ 2>&1)"
rsync -v --ignore-missing-args --exclude 'external-repo.noarch.rpm' -e 'ssh -vvv' build/*.{rpm,deb,dsc,tar.xz,buildinfo,changes} zmrepo@zmrepo.zoneminder.com:${targetfolder}/ 2>&1
if [ "$?" -eq 0 ]; then
  echo 
  echo "Files copied successfully."
  echo
else 
  echo
  echo "ERROR: Attempt to rsync to zmrepo.zoneminder.com failed!"
  echo "See log output for details."
  echo
  exit 99
fi
