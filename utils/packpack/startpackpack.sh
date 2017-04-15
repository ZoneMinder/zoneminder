#!/bin/bash
# packpack setup file for the ZoneMinder project
# Written by Andrew Bauer

###############
# SUBROUTINES #
###############

# General sanity checks
checksanity () {
    # Check to see if this script has access to all the commands it needs
    for CMD in set echo curl repoquery git ln mkdir rmdir; do
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

    if [[ "${ARCH}" != "x86_64" && "${ARCH}" != "i386" && "${ARCH}" != "armhf" ]]; then
        echo
        echo "ERROR: Unsupported architecture specified \"${ARCH}\"."
        echo
        exit 1
    fi

}

# Steps common to all builds
commonprep () {
    mkdir -p build
    if [ -e "packpack/Makefile" ]; then
        echo "Checking packpack github repo for changes..."
        git -C packpack pull origin master
    else
        echo "Cloning pakcpack github repo..."
        git clone https://github.com/packpack/packpack.git packpack
    fi

    # The rpm specfile requires we download the tarball and manually move it into place
    # Might as well do this for Debian as well, rather than git submodule init
    CRUDVER="3.0.10"
    if [ -e "build/crud-${CRUDVER}.tar.gz" ]; then
        echo "Found existing Crud ${CRUDVER} tarball..."
    else
        echo "Retrieving Crud ${CRUDVER} submodule..."
        curl -L https://github.com/FriendsOfCake/crud/archive/v${CRUDVER}.tar.gz > build/crud-${CRUDVER}.tar.gz
        if [ $? -ne 0 ]; then
            echo "ERROR: Crud tarball retreival failed..."
            exit 1
        fi
    fi
}

# Uncompress the Crud tarball and move it into place
movecrud () {
    if [ -e "web/api/app/Plugin/Crud/LICENSE.txt" ]; then
        echo "Crud plugin already installed..."
    else     
        echo "Unpacking Crud plugin..."
        tar -xzf build/crud-${CRUDVER}.tar.gz
        rmdir web/api/app/Plugin/Crud
        mv -f crud-${CRUDVER} web/api/app/Plugin/Crud
    fi
}

# previsouly part of installzm.sh
# install the trusty deb and test zoneminder
installtrusty () {

    # Check we've got gdebi installed
    type gdebi 2>&1 > /dev/null

    if [ $? -ne 0 ]; then
      echo
      echo "ERROR: The script cannot find the required command \"gdebi\"."
      echo
      exit 1
    fi

    # Install and test the zoneminder package (only) for Ubuntu Trusty
    pkgname="build/zoneminder_${VERSION}-${RELEASE}_amd64.deb"

    if [ -e $pkgname ]; then
        sudo gdebi --non-interactive $pkgname
        mysql -uzmuser -pzmpass zm < db/test.monitor.sql
        sudo /usr/bin/zmpkg.pl start
        sudo /usr/bin/zmfilter.pl -f purgewhenfull
    else
      echo
      echo "ERROR: The script cannot find the package $pkgname"
      echo "Check the Travis log for a build failure."
      echo
      exit 99
    fi
}

# This sets the naming convention for the deb packages
setdebpkgver () {

    # Set VERSION to x.xx.x+x e.g. 1.30.2+15
    # the last x is number of commits since release
    # Creates zoneminder packages in the format: zoneminder-{version}-{release}
    zmver=$(git describe --long --always | sed -n 's/^\([0-9\.]*\)-\([0-9]*\)-\([a-z0-9]*\)/\1/p')
    commitnum=$(git describe --long --always | sed -n 's/^\([0-9\.]*\)-\([0-9]*\)-\([a-z0-9]*\)/\2/p')
    export VERSION="$zmver+$commitnum"
    export RELEASE="${DIST}"

    echo
    echo "Packpack VERSION has been set to: ${VERSION}"
    echo "Packpack RELEASE has been set to: ${RELEASE}"
    echo

}

################
# MAIN PROGRAM #
################

checksanity

# We don't want to build packages for all supported distros after every commit
# Only build all packages when executed via cron
# See https://docs.travis-ci.com/user/cron-jobs/
if [ "${TRAVIS_EVENT_TYPE}" == "cron" ] || [ "${TRAVIS}" != "true"  ]; then
    commonprep

    # Steps common to Redhat distros
    if [ "${OS}" == "el" ] || [ "${OS}" == "fedora" ]; then
        echo "Begin Redhat build..."

        # Set VERSION to x.xx.x e.g. 1.30.2
        # Set RELEASE to x where x is number of commits since release
        # Creates zoneminder packages in the format: zoneminder-{version}-{release}
        export VERSION=$(git describe --long --always | sed -n 's/^\([0-9\.]*\)-\([0-9]*\)-\([a-z0-9]*\)/\1/p')
        export RELEASE=$(git describe --long --always | sed -n 's/^\([0-9\.]*\)-\([0-9]*\)-\([a-z0-9]*\)/\2/p')

        echo
        echo "Packpack VERSION has been set to: ${VERSION}"
        echo "Packpack RELEASE has been set to: ${RELEASE}"
        echo

        ln -sfT distros/redhat rpm

        # The rpm specfile requires the Crud submodule folder to be empty
        rm -rf web/api/app/Plugin/Crud
        mkdir web/api/app/Plugin/Crud

        if [ "${OS}" == "el" ]; then
            zmrepodistro=${OS}
        else
            zmrepodistro="f"
        fi

        # Let repoquery determine the full url and filename of the zmrepo rpm we are interested in
        result=`repoquery --repofrompath=zmpackpack,https://zmrepo.zoneminder.com/${zmrepodistro}/"${DIST}"/x86_64/ --repoid=zmpackpack --qf="%{location}" zmrepo 2> /dev/null`

        if [ -n "$result" ] && [ $? -eq 0  ]; then
            echo "Retrieving ZMREPO rpm..."
            curl $result > build/zmrepo.noarch.rpm
        else
            echo "ERROR: Failed to retrieve zmrepo rpm..."
            exit 1
        fi

        echo "Starting packpack..."
        packpack/packpack -f utils/packpack/redhat_package.mk redhat_package

    # Steps common to Debian based distros
    elif [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ]; then
        echo "Begin ${OS} ${DIST} build..."

        setdebpkgver
        movecrud

        if [ "${DIST}" == "trusty" ] || [ "${DIST}" == "precise" ]; then
            ln -sfT distros/ubuntu1204 debian
        elif [ "${DIST}" == "wheezy" ]; then 
            ln -sfT distros/debian debian
        else 
            ln -sfT distros/ubuntu1604 debian
        fi

        echo "Starting packpack..."
        packpack/packpack

        if [ "${OS}" == "ubuntu" ] && [ "${DIST}" == "trusty" ] && [ "${ARCH}" == "x86_64" ] && [ "${TRAVIS}" == "true" ]; then
            installtrusty
        fi
    fi

# We were not triggered via cron so just build and test trusty
elif [ "${OS}" == "ubuntu" ] && [ "${DIST}" == "trusty" ] && [ "${ARCH}" == "x86_64" ]; then
    echo "Begin Ubuntu Trusty build..."

    commonprep
    setdebpkgver
    movecrud

    ln -sfT distros/ubuntu1204 debian

    echo "Starting packpack..."
    packpack/packpack

    # If we are running inside Travis then attempt to install the deb we just built
    if [ "${TRAVIS}" == "true" ]; then
        installtrusty
    fi
fi

exit 0

