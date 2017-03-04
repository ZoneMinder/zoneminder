#!/bin/bash
# packpack setup file for the ZoneMinder project
# Written by Andrew Bauer

# Required, so that Travis marks the build as failed if any of the steps below fail
set -ev

###############
# SUBROUTINES #
###############

# General sanity checks
checksanity () {
    # Check to see if this script has access to all the commands it needs
    for CMD in set echo curl repoquery git ln mkdir patch rmdir; do
      type $CMD 2>&1 > /dev/null

      if [ $? -ne 0 ]; then
        echo
        echo "ERROR: The script cannot find the required command \"${CMD}\"."
        echo
        exit 1
      fi
    done

    # Verify OS & DIST environment variables have been set before calling this script
    if [ -z "${OS}" ] || [ -z ""${DIST}"" ]; then
        echo "ERROR: both OS and DIST environment variables must be set"
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
    # Install and test the zoneminder package (only) for Ubuntu Trusty
    sudo gdebi --non-interactive build/zoneminder_*amd64.deb
    sudo chmod 644 /etc/zm/zm.conf 
    sudo chgrp www-data /etc/zm/zm.conf
    mysql -uzmuser -pzmpass zm < db/test.monitor.sql
    sudo /usr/bin/zmpkg.pl start
    sudo /usr/bin/zmfilter.pl -f purgewhenfull
}

################
# MAIN PROGRAM #
################

checksanity

# We don't want to build packages for all supported distros after every commit
# Only build all packages when executed via cron
# See https://docs.travis-ci.com/user/cron-jobs/
if [ "${TRAVIS_EVENT_TYPE}" == "cron" ]; then
    commonprep

    # Steps common to Redhat distros
    if [ "${OS}" == "el" ] || [ "${OS}" == "fedora" ]; then
        echo "Begin Redhat build..."

        # fix %autosetup support - fixed upstream
        #patch --dry-run --silent -f -p1 < utils/packpack/fixautosetup.patch 2>/dev/null
        #if [ $? -eq 0 ]; then
        #    patch -p1 < utils/packpack/fixautosetup.patch
        #fi

        ln -sf distros/redhat rpm

        # The rpm specfile requires the Crud submodule folder to be empty
        if [ -e "web/api/app/Plugin/Crud/LICENSE.txt" ]; then
            rm -rf web/api/app/Plugin/Crud
            mkdir web/api/app/Plugin/Crud
        fi

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
            echo 1
        fi

        echo "Starting packpack..."
        packpack/packpack -f utils/packpack/redhat_package.mk redhat_package

    # Steps common to Debian based distros
    elif [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ]; then
        echo "Begin Debian build..."

        # patch packpack to remove "debian" from the source tarball filename
        # fixed upstream
        # patch --dry-run --silent -f -p1 < utils/packpack/deb.mk.patch 2>/dev/null
        #if [ $? -eq 0 ]; then
        #    patch -p1 < utils/packpack/deb.mk.patch
        #fi

        movecrud

        if [ "${DIST}" == "trusty" ] || [ "${DIST}" == "precise" ]; then
            ln -sf distros/ubuntu1204 debian
        elif [ "${DIST}" == "wheezy" ]; then 
            ln -sf distros/debian debian
        else 
            ln -sf distros/ubuntu1604 debian
        fi

        echo "Starting packpack..."
        packpack/packpack

        if [ "${OS}" == "ubuntu" ] && [ "${DIST}" == "trusty" ]; then
            installtrusty
        fi
    fi

# We were not triggered via cron so just build and test trusty
elif [ "${OS}" == "ubuntu" ] && [ "${DIST}" == "trusty" ]; then
    commonprep
    movecrud

    ln -sf distros/ubuntu1204 debian

    echo "Starting packpack..."
    packpack/packpack

    installtrusty
fi

