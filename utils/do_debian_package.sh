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
    ;;
esac
done

DATE=`date -R`
if [ "$TYPE" == "" ]; then
  echo "Defaulting to source build"
  TYPE="source";
fi;
if [ "$GITHUB_FORK" == "" ]; then
  echo "Defaulting to ZoneMinder upstream git"
  GITHUB_FORK="ZoneMinder"
fi;

# Release is a special mode...  it uploads to the release ppa and cannot have a snapshot
if [ "$RELEASE" != "" ]; then
  if [ "$SNAPSHOT" != "" ]; then
    echo "Releases cannot have a snapshot.... exiting."
  fi
  if [ "$GITHUB_FORK" != "" ]; then
    echo "Releases cannot have a fork.... exiting."
  fi
  BRANCH=$RELEASE
else
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
if [ $RELEASE ]; then
  git checkout $RELEASE
else
  git checkout $BRANCH
fi;
cd ../

VERSION=`cat ${GITHUB_FORK}_zoneminder_release/version`

if [ $VERSION == "" ]; then
	exit 1;
fi;
if [ "$SNAPSHOT" != "stable" ] && [ "$SNAPSHOT" != "" ]; then
  VERSION="$VERSION~$SNAPSHOT";
fi;

DIRECTORY="zoneminder_$VERSION-$DISTRO${PACKAGE_VERSION}";
echo "Doing $TYPE release $DIRECTORY";
mv "${GITHUB_FORK}_zoneminder_release" "$DIRECTORY.orig";
cd "$DIRECTORY.orig";

git submodule init
git submodule update --init --recursive
if [ "$DISTRO" == "trusty" ] || [ "$DISTRO" == "precise" ]; then 
	ln -sf distros/ubuntu1204 debian
else 
  if [ "$DISTRO" == "wheezy" ]; then 
    ln -sf distros/debian debian
  else 
    ln -sf distros/ubuntu1604 debian
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
else
cat <<EOF > debian/changelog
zoneminder ($VERSION-$DISTRO${PACKAGE_VERSION}) $DISTRO; urgency=$URGENCY

  * 

 -- $AUTHOR  $DATE

EOF
fi;

# Auto-install all ZoneMinder's depedencies using the Debian control file
sudo apt-get install devscripts equivs
sudo mk-build-deps -ir ./debian/control
echo "Status: $?"

#rm -rf .git
#rm .gitignore
#cd ../
#tar zcf zoneminder_$VERSION-$DISTRO.orig.tar.gz zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
#cd zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
if [ $TYPE == "binary" ]; then
	DEBUILD=debuild
else
  if [ $TYPE == "local" ]; then
    DEBUILD="debuild -i -us -uc -b"
  else 
    DEBUILD="debuild -S -sa"
  fi;
fi;
if [ "$DEBSIGN_KEYID" != "" ]; then
  DEBUILD="$DEBUILD -k$DEBSIGN_KEYID"
fi
$DEBUILD
echo "Status: $?"

cd ../
if [ "$INTERACTIVE" != "no" ]; then
  read -p "Do you want to keep the checked out version of Zoneminder (incase you want to modify it later) [y/N]"
  [[ $REPLY == [yY] ]] && { mv $DIRECTORY zoneminder_release; echo "The checked out copy is preserved in zoneminder_release"; } || { rm -fr $DIRECTORY; echo "The checked out copy has been deleted"; }
  echo "Done!"
else 
  rm -fr $DIRECTORY; echo "The checked out copy has been deleted";
fi

if [ $TYPE == "binary" ]; then
  if [ "$INTERACTIVE" != "no" ]; then
    echo "Not doing dput since it's a binary release. Do you want to install it? (Y/N)"
    read install
    if [ "$install" == "Y" ]; then
        sudo dpkg -i $DIRECTORY*.deb
    fi;
    if [ "$DISTRO" == "jessie" ]; then
        echo "Do you want to upload this binary to zmrepo? (y/N)"
        read install
        if [ "$install" == "Y" ]; then
          scp "zoneminder_*-${VERSION}-${DISTRO}*" "zmrepo@zmrepo.connortechnology.com:debian/${BRANCH}/mini-dinstall/incoming/"
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
    echo "Ready to dput $SC to $PPA ? Y/N...";
    read dput
  fi
	if [ "$dput" == "Y" -o "$dput" == "y" ]; then
		dput $PPA $SC
	fi;
fi;


