#!/bin/bash
# packpack setup file for the ZoneMinder project
# Written by Andrew Bauer

###############
# SUBROUTINES #
###############

# General sanity checks
checksanity () {
    # Check to see if this script has access to all the commands it needs
    for CMD in set echo curl git ln mkdir rmdir cat patch sed; do
      type $CMD 2>&1 > /dev/null

      if [ $? -ne 0 ]; then
        echo
        echo "ERROR: The script cannot find the required command \"${CMD}\"."
        echo
        exit 1
      fi
    done

    # Verify OS & DIST environment variables have been set before calling this script
    if [ -z "${OS}" ] || [ -z "${DIST}" ]; then
        echo "ERROR: both OS and DIST environment variables must be set"
        exit 1
    fi

    if [ -z "${ARCH}" ]; then
        ARCH="x86_64"
    fi

    if [[ "${ARCH}" != "x86_64" && "${ARCH}" != "i386" && "${ARCH}" != "armhf" && "${ARCH}" != "aarch64" ]]; then
        echo
        echo "ERROR: Unsupported architecture specified \"${ARCH}\"."
        echo
        exit 1
    fi
}

# Create key variables used to assemble the package name
createvars () {
    # We need today's date in year/month/day format
    thedate=$(date +%Y%m%d)

    # We need the (short) commit hash of the latest commit (rpm packaging only)
    shorthash=$(git describe --long --always | awk -F - '{print $3}')

    # Grab the ZoneMinder version from the contents of the version file
    versionfile=$(cat version)

    # git the latest (short) commit hash of the version file
    versionhash=$(git log -n1 --pretty=format:%h version)

    # Number of commits since the version file was last changed
    numcommits=$(git rev-list ${versionhash}..HEAD --count)
}

# Check key variables before calling packpack
checkvars () {

    for var in $thedate $shorthash $versionfile $versionhash $numcommits; do
        if [ -z ${var} ]; then
            echo
            echo "FATAL: This script was unable to determine one or more key variables. Cannot continue."
            echo
            echo "VARIABLE DUMP"
            echo "-------------"
            echo
            echo "thedate: ${thedate}"
            echo "shorthash: ${shorthash}"
            echo "versionfile: ${versionfile}"
            echo "versionhash: ${versionhash}"
            echo "numcommits: ${numcommits}"
            echo
            exit 98
        fi
    done
}

# Steps common to all builds
commonprep () {
    mkdir -p build
    if [ -e "packpack/Makefile" ]; then
        echo "Checking packpack github repo for changes..."
        git -C packpack pull origin master
    else
        echo "Cloning packpack github repo..."
        git clone https://github.com/zoneminder/packpack.git packpack
    fi

    # The rpm specfile requires we download each submodule as a tarball then manually move it into place
    # Might as well do this for Debian as well, rather than git submodule init
    CRUDVER="3.1.0-zm"
    if [ -e "build/crud-${CRUDVER}.tar.gz" ]; then
        echo "Found existing Crud ${CRUDVER} tarball..."
    else
        echo "Retrieving Crud ${CRUDVER} submodule..."
        curl -L https://github.com/ZoneMinder/crud/archive/v${CRUDVER}.tar.gz > build/crud-${CRUDVER}.tar.gz
        if [ $? -ne 0 ]; then
            echo "ERROR: Crud tarball retreival failed..."
            exit 1
        fi
    fi

    CEBVER="1.0-zm"
    if [ -e "build/cakephp-enum-behavior-${CEBVER}.tar.gz" ]; then
        echo "Found existing CakePHP-Enum-Behavior ${CEBVER} tarball..."
    else
        echo "Retrieving CakePHP-Enum-Behavior ${CEBVER} submodule..."
        curl -L https://github.com/ZoneMinder/CakePHP-Enum-Behavior/archive/${CEBVER}.tar.gz > build/cakephp-enum-behavior-${CEBVER}.tar.gz
        if [ $? -ne 0 ]; then
            echo "ERROR: CakePHP-Enum-Behavior tarball retreival failed..."
            exit 1
        fi
    fi
}

# Uncompress the submodule tarballs and move them into place
movecrud () {
    if [ -e "web/api/app/Plugin/Crud/LICENSE.txt" ]; then
        echo "Crud plugin already installed..."
    else
        echo "Unpacking Crud plugin..."
        tar -xzf build/crud-${CRUDVER}.tar.gz
        rmdir web/api/app/Plugin/Crud
        mv -f crud-${CRUDVER} web/api/app/Plugin/Crud
    fi
    if [ -e "web/api/app/Plugin/CakePHP-Enum-Behavior/readme.md" ]; then
        echo "CakePHP-Enum-Behavior plugin already installed..."
    else
        echo "Unpacking CakePHP-Enum-Behavior plugin..."
        tar -xzf build/cakephp-enum-behavior-${CEBVER}.tar.gz
        rmdir web/api/app/Plugin/CakePHP-Enum-Behavior
        mv -f CakePHP-Enum-Behavior-${CEBVER} web/api/app/Plugin/CakePHP-Enum-Behavior
    fi
}

# previsouly part of installzm.sh
# install the xenial deb and test zoneminder
install_deb () {

    # Check we've got gdebi installed
    type gdebi 2>&1 > /dev/null

    if [ $? -ne 0 ]; then
      echo
      echo "ERROR: The script cannot find the required command \"gdebi\"."
      echo
      exit 1
    fi

    # Install and test the zoneminder package (only) for Ubuntu Xenial
    pkgname="build/zoneminder_${VERSION}-${RELEASE}_amd64.deb"

    if [ -e $pkgname ]; then
        sudo gdebi --quiet --non-interactive $pkgname
        echo "Return code from installing $?"
        mysql -uzmuser -pzmpass zm < db/test.monitor.sql
        echo "Return code from adding test monitor $?"
        sudo /usr/bin/zmpkg.pl start
        echo "Return code from starting $?"
        sudo /usr/bin/zmfilter.pl --filter=purgewhenfull
        echo "Return code from running purgewhenfull $?"
    else
      echo
      echo "ERROR: The script cannot find the package $pkgname"
      echo "Check the Travis log for a build failure."
      echo
      exit 99
    fi
}

# This sets the naming convention for the rpm packages
setrpmpkgname () {

    createvars

    # Set VERSION to the contents of the version file e.g. 1.31.0
    # Set RELEASE to 1.{number of commits}.{today's date}git{short hash of HEAD} e.g. 1.82.20170605gitg7ae0b4a
    export VERSION="$versionfile"
    export RELEASE="1.${numcommits}.${thedate}git${shorthash}"

    checkvars

    echo
    echo "Packpack VERSION has been set to: ${VERSION}"
    echo "Packpack RELEASE has been set to: ${RELEASE}"
    echo

}

# This sets the naming convention for the deb packages
setdebpkgname () {

    createvars

    # Set VERSION to {zm version}~{today's date}.{number of commits} e.g. 1.31.0~20170605.82
    # Set RELEASE to the packpack DIST variable e.g. Trusty
    export VERSION="${versionfile}~${thedate}.${numcommits}"
    export RELEASE="${DIST}"

    checkvars

    echo
    echo "Packpack VERSION has been set to: ${VERSION}"
    echo "Packpack RELEASE has been set to: ${RELEASE}"
    echo

}

# This adds an entry to the rpm specfile changelog
setrpmchangelog () {

    export CHANGELOG_NAME="Andrew Bauer"
    export CHANGELOG_EMAIL="zonexpertconsulting@outlook.com"
    export CHANGELOG_TEXT="Automated, development snapshot of git ${shorthash}"

}


# This adds an entry to the debian changelog
setdebchangelog () {
DATE=`date -R`
cat <<EOF > debian/changelog
zoneminder ($VERSION-${DIST}) ${DIST}; urgency=low
  *
 -- Isaac Connor <isaac@zoneminder.com>  $DATE
EOF
}

# start packpack, filter the output if we are running in travis
execpackpack () {

    if [ "${OS}" == "el" ] || [ "${OS}" == "fedora" ]; then
        parms="-f utils/packpack/redhat_package.mk redhat_package"
    else
        parms=""
    fi

    if [ "${TRAVIS}" == "true"  ]; then
        # Travis will fail the build if the output gets too long
        # To mitigate that, use grep to filter out some of the noise
        if [ "${ARCH}" != "armhf" ]; then
            packpack/packpack $parms | grep -Ev '^(-- Installing:|-- Up-to-date:|Skip blib|Manifying|Installing /build|cp lib|writing output...|copying images...|reading sources...|[Working])'
        else
            # Travis never ceases to amaze. For the case of arm emulation, Travis fails the build due to too little output over a 10 minute period. Facepalm.
            packpack/packpack $parms | grep -Ev '^(-- Installing:|Skip blib|Manifying|Installing /build|cp lib|writing output...|copying images...|reading sources...|[Working])'
        fi
    else
        packpack/packpack $parms
    fi
}

# Check for connectivity with the deploy target host
checkdeploytarget () {
    echo
    echo "Checking Internet connectivity with the deploy host ${DEPLOYTARGET}"
    echo

    ping -c 1 ${DEPLOYTARGET}

    if [  $? -ne 0 ]; then
        echo
        echo "*** WARNING: THERE WAS A PROBLEM CONNECTING TO THE DEPLOY HOST ***"
        echo
        echo "Printing additional diagnostic information..."

        echo
        echo "*** NSLOOKUP ***"
        echo
        nslookup ${DEPLOYTARGET}

        echo
        echo "*** TRACEROUTE ***"
        echo
        traceroute -w 2 -m 15 ${DEPLOYTARGET}

        exit 97
    fi
}

################
# MAIN PROGRAM #
################

# Set the hostname we will deploy packages to
DEPLOYTARGET="zmrepo.zoneminder.com"

# If we are running inside Travis then verify we can connect to the target host machine
if [ "${TRAVIS}" == "true" ]; then
    checkdeploytarget
fi
checksanity

# Steps common to Redhat distros
if [ "${OS}" == "el" ] || [ "${OS}" == "fedora" ]; then
  commonprep
  echo "Begin Redhat build..."

  # Newer Redhat distros use dnf package manager rather than yum
  if [ "${DIST}" -gt "7" ]; then
    sed -i 's\yum\dnf\' utils/packpack/redhat_package.mk
  fi

  setrpmpkgname

  ln -sfT distros/redhat rpm

  # The rpm specfile requires the Crud submodule folder to be empty
  rm -rf web/api/app/Plugin/Crud
  mkdir web/api/app/Plugin/Crud

  reporpm="rpmfusion-free-release"
  dlurl="https://download1.rpmfusion.org/free/${OS}/${reporpm}-${DIST}.noarch.rpm"

  # Give our downloaded repo rpm a common name so redhat_package.mk can find it
  if [ -n "$dlurl" ] && [ $? -eq 0  ]; then
    echo "Retrieving ${reporpm} repo rpm..."
    curl $dlurl > build/external-repo.noarch.rpm
  else
    echo "ERROR: Failed to retrieve ${reporpm} repo rpm..."
    echo "Download url was: $dlurl"
    exit 1
  fi

  setrpmchangelog

  echo "Starting packpack..."
  execpackpack

# Steps common to Debian based distros
elif [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ] || [ "${OS}" == "raspbian" ]; then
  commonprep
  echo "Begin ${OS} ${DIST} build..."

  setdebpkgname
  movecrud

  if [ "${DIST}" == "trusty" ] || [ "${DIST}" == "precise" ]; then
    ln -sfT distros/ubuntu1204 debian
  elif [ "${DIST}" == "wheezy" ]; then
    ln -sfT distros/debian debian
  else
    ln -sfT distros/ubuntu1604 debian
  fi

  setdebchangelog

  echo "Starting packpack..."
  execpackpack

  # Try to install and run the newly built zoneminder package
  if [ "${OS}" == "ubuntu" ] && [ "${DIST}" == "xenial" ] && [ "${ARCH}" == "x86_64" ] && [ "${TRAVIS}" == "true" ]; then
      echo "Begin Deb package installation..."
      install_deb
  fi

# Steps common to eslint checks
elif [ "${OS}" == "eslint" ] || [ "${DIST}" == "eslint" ]; then

    # Check we've got npm installed
    type npm 2>&1 > /dev/null

    if [ $? -ne 0 ]; then
      echo
      echo "ERROR: The script cannot find the required command \"npm\"."
      echo
      exit 1
    fi

    npm install -g eslint@5.12.0 eslint-config-google@0.11.0 eslint-plugin-html@5.0.0 eslint-plugin-php-markup@0.2.5
    echo "Begin eslint checks..."
    eslint --ext .php,.js .
fi

