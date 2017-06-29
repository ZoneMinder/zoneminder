#!/bin/bash

if [ "$1" == "clean" ]; then

read -p "Do you really want to delete existing packages? [y/N]"
[[ $REPLY == [yY] ]] && { rm -fr zoneminder*.build zoneminder*.changes zoneminder*.deb; echo "Existing package files deleted";  } || { echo "Packages have NOT been deleted"; }
exit;

fi

for i in "$@"
do
case $i in
    -b=*|--branch=*)
    BRANCH="${i#*=}"
    shift # past argument=value
    ;;
    -d=*|--distro=*)
    DISTRO="${i#*=}"
    shift # past argument=value
    ;;
    -i=*|--interactive=*)
    INTERACTIVE="${i#*=}"
    shift # past argument=value
    ;;
    -r=*|--release=*)
    RELEASE="${i#*=}"
    shift
    ;;
    -s=*|--snapshot=*)
    SNAPSHOT="${i#*=}"
    shift # past argument=value
    ;;
    -t=*|--type=*)
    TYPE="${i#*=}"
    shift # past argument=value
    ;;
    -u=*|--urgency=*)
    URGENCY="${i#*=}"
    shift # past argument=value
    ;;
    -f=*|--fork=*)
    GITHUB_FORK="${i#*=}"
    shift # past argument=value
    ;;
    -v=*|--version=*)
    PACKAGE_VERSION="${i#*=}"
    shift
    ;;
    --default)
    DEFAULT=YES
    shift # past argument with no value
    ;;
    *)
    # unknown option
    read -p "Unknown option $i, continue? (Y|n)"
    [[ $REPLY == [yY] ]] && { echo "continuing..."; } || exit 1;
    ;;
esac
done

DATE=`date -R`
if [ "$TYPE" == "" ]; then
  echo "Defaulting to source build"
  TYPE="source";
else 
  echo "Doing $TYPE build"
fi;

if [ "$DISTRO" == "" ]; then
  DISTRO=`lsb_release -a 2>/dev/null | grep Codename | awk '{print $2}'`;
  echo "Defaulting to $DISTRO for distribution";
else
  echo "Building for $DISTRO";
fi;

# Release is a special mode...  it uploads to the release ppa and cannot have a snapshot
if [ "$RELEASE" != "" ]; then
  if [ "$SNAPSHOT" != "" ]; then
    echo "Releases cannot have a snapshot.... exiting."
    exit 0;
  fi
  if [ "$GITHUB_FORK" != "" ] && [ "$GITHUB_FORK" != "ZoneMinder" ]; then
    echo "Releases cannot have a fork ($GITHUB_FORK).... exiting."
    exit 0;
  else
    GITHUB_FORK="ZoneMinder";
  fi
  BRANCH="release-$RELEASE"
else
  if [ "$GITHUB_FORK" == "" ]; then
    echo "Defaulting to ZoneMinder upstream git"
    GITHUB_FORK="ZoneMinder"
  fi;
  if [ "$SNAPSHOT" == "stable" ]; then
    if [ "$BRANCH" == "" ]; then
      BRANCH=$(git describe --tags $(git rev-list --tags --max-count=1));
      echo "Latest stable branch is $BRANCH";
    fi;
  else
    if [ "$BRANCH" == "" ]; then
      echo "Defaulting to master branch";
      BRANCH="master";
    fi;
    if [ "$SNAPSHOT" == "NOW" ]; then
      SNAPSHOT=`date +%Y%m%d%H%M%S`;
    fi;
  fi;
fi


# Instead of cloning from github each time, if we have a fork lying around, update it and pull from there instead.
if [ ! -d "${GITHUB_FORK}_zoneminder_release" ]; then 
  if [ -d "${GITHUB_FORK}_ZoneMinder.git" ]; then
    echo "Using local clone ${GITHUB_FORK}_ZoneMinder.git to pull from."
    cd "${GITHUB_FORK}_ZoneMinder.git"
    echo "git pull..."
    git pull
    echo "git checkout $BRANCH"
    git checkout $BRANCH
    echo "git pull..."
    git pull
    cd ../
    echo "git clone ${GITHUB_FORK}_ZoneMinder.git ${GITHUB_FORK}_zoneminder_release"
    git clone "${GITHUB_FORK}_ZoneMinder.git" "${GITHUB_FORK}_zoneminder_release"
  else
    echo "git clone https://github.com/$GITHUB_FORK/ZoneMinder.git ${GITHUB_FORK}_zoneminder_release"
    git clone "https://github.com/$GITHUB_FORK/ZoneMinder.git" "${GITHUB_FORK}_zoneminder_release"
  fi
else
  echo "release dir already exists. Please remove it."
  exit 0;
fi;

cd "${GITHUB_FORK}_zoneminder_release"
  git checkout $BRANCH
cd ../

VERSION=`cat ${GITHUB_FORK}_zoneminder_release/version`

if [ $VERSION == "" ]; then
  exit 1;
fi;
if [ "$SNAPSHOT" != "stable" ] && [ "$SNAPSHOT" != "" ]; then
  VERSION="$VERSION~$SNAPSHOT";
fi;

DIRECTORY="zoneminder_$VERSION";
echo "Doing $TYPE release $DIRECTORY";
mv "${GITHUB_FORK}_zoneminder_release" "$DIRECTORY.orig";
if [ $? -ne 0 ]; then
  echo "Error status code is: $?"
  echo "Setting up build dir failed.";
  exit $?;
fi;
cd "$DIRECTORY.orig";

git submodule init
git submodule update --init --recursive
if [ "$DISTRO" == "trusty" ] || [ "$DISTRO" == "precise" ]; then 
  mv distros/ubuntu1204 debian
else 
  if [ "$DISTRO" == "wheezy" ]; then 
    mv distros/debian debian
  else 
    mv distros/ubuntu1604 debian
  fi;
fi;

if [ "$DEBEMAIL" != "" ] && [ "$DEBFULLNAME" != "" ]; then
    AUTHOR="$DEBFULLNAME <$DEBEMAIL>"
else
  if [ -z `hostname -d` ] ; then
      AUTHOR="`getent passwd $USER | cut -d ':' -f 5 | cut -d ',' -f 1` <`whoami`@`hostname`.local>"
  else
      AUTHOR="`getent passwd $USER | cut -d ':' -f 5 | cut -d ',' -f 1` <`whoami`@`hostname`>"
  fi
fi

if [ "$URGENCY" = "" ]; then
  URGENCY="medium"
fi;

if [ "$SNAPSHOT" == "stable" ]; then
cat <<EOF > debian/changelog
zoneminder ($VERSION-$DISTRO${PACKAGE_VERSION}) $DISTRO; urgency=$URGENCY

  * Release $VERSION

 -- $AUTHOR  $DATE

EOF
cat <<EOF > debian/NEWS
zoneminder ($VERSION-$DISTRO${PACKAGE_VERSION}) $DISTRO; urgency=$URGENCY

  * Release $VERSION

 -- $AUTHOR  $DATE
EOF
else
cat <<EOF > debian/changelog
zoneminder ($VERSION-$DISTRO${PACKAGE_VERSION}) $DISTRO; urgency=$URGENCY

  * 

 -- $AUTHOR  $DATE
EOF
cat <<EOF > debian/changelog
zoneminder ($VERSION-$DISTRO${PACKAGE_VERSION}) $DISTRO; urgency=$URGENCY

  * 

 -- $AUTHOR  $DATE
EOF
fi;

rm -rf .git
rm .gitignore
cd ../
tar zcf $DIRECTORY.orig.tar.gz $DIRECTORY.orig
cd $DIRECTORY.orig

if [ $TYPE == "binary" ]; then
  # Auto-install all ZoneMinder's depedencies using the Debian control file
  sudo apt-get install devscripts equivs
  sudo mk-build-deps -ir ./debian/control
  echo "Status: $?"
  DEBUILD=debuild
else
  if [ $TYPE == "local" ]; then
    # Auto-install all ZoneMinder's depedencies using the Debian control file
    sudo apt-get install devscripts equivs
    sudo mk-build-deps -ir ./debian/control
    echo "Status: $?"
    DEBUILD="debuild -i -us -uc -b"
  else 
    # Source build, don't need build depends.
    DEBUILD="debuild -S -sa"
  fi;
fi;
if [ "$DEBSIGN_KEYID" != "" ]; then
  DEBUILD="$DEBUILD -k$DEBSIGN_KEYID"
fi
$DEBUILD
if [ $? -ne 0 ]; then
echo "Error status code is: $?"
  echo "Build failed.";
  exit $?;
fi;

cd ../
if [ "$INTERACTIVE" != "no" ]; then
  read -p "Do you want to keep the checked out version of Zoneminder (incase you want to modify it later) [y/N]"
  [[ $REPLY == [yY] ]] && { mv "$DIRECTORY.orig" zoneminder_release; echo "The checked out copy is preserved in zoneminder_release"; } || { rm -fr "$DIRECTORY.orig"; echo "The checked out copy has been deleted"; }
  echo "Done!"
else 
  rm -fr "$DIRECTORY.orig"; echo "The checked out copy has been deleted";
fi

if [ $TYPE == "binary" ]; then
  if [ "$INTERACTIVE" != "no" ]; then
    read -p "Not doing dput since it's a binary release. Do you want to install it? (Y/N)"
    if [[ $REPLY == [yY] ]]; then
        sudo dpkg -i $DIRECTORY*.deb
    else 
	echo $REPLY;
    fi;
    if [ "$DISTRO" == "jessie" ]; then
      read -p "Do you want to upload this binary to zmrepo? (y/N)"
      if [[ $REPLY == [yY] ]]; then
        if [ "$RELEASE" != "" ]; then
          scp "zoneminder_${VERSION}-${DISTRO}"* "zoneminder-doc_${VERSION}-${DISTRO}"* "zoneminder-dbg_${VERSION}-${DISTRO}"* "zoneminder_${VERSION}.orig.tar.gz" "zmrepo@zmrepo.connortechnology.com:debian/stable/mini-dinstall/incoming/"
        else
          if [ "$BRANCH" == "" ]; then
            scp "zoneminder_${VERSION}-${DISTRO}"* "zoneminder-doc_${VERSION}-${DISTRO}"* "zoneminder-dbg_${VERSION}-${DISTRO}"* "zoneminder_${VERSION}.orig.tar.gz" "zmrepo@zmrepo.connortechnology.com:debian/master/mini-dinstall/incoming/"
          else
            scp "$DIRECTORY-${DISTRO}"* "zoneminder-doc_${VERSION}-${DISTRO}"* "zoneminder-dbg_${VERSION}-${DISTRO}"* "zoneminder_${VERSION}.orig.tar.gz" "zmrepo@zmrepo.connortechnology.com:debian/${BRANCH}/mini-dinstall/incoming/"
          fi;
        fi;
      fi;
    fi;
  fi;
else
  SC="zoneminder_${VERSION}-${DISTRO}${PACKAGE_VERSION}_source.changes";
  PPA="";
  if [ "$RELEASE" != "" ]; then
      PPA="ppa:iconnor/zoneminder";
  else
    if [ "$BRANCH" == "" ]; then
      PPA="ppa:iconnor/zoneminder-master";
    else 
      PPA="ppa:iconnor/zoneminder-$BRANCH";
    fi;
  fi;

  dput="Y";
  if [ "$INTERACTIVE" != "no" ]; then
    read -p "Ready to dput $SC to $PPA ? Y/N...";
    if [[ "$REPLY" == [yY] ]]; then
      dput $PPA $SC
    fi;
  fi;
fi;


