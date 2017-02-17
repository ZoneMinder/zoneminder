#!/bin/bash
# packpack setup file for the ZoneMinder project
# Written by Andrew Bauer

# Check to see if this script has access to all the commands it needs
for CMD in set echo curl repoquery git ln mkdir patch rmdir; do
  type $CMD 2>&1 > /dev/null

  if [ $? -ne 0 ]; then
    echo
    echo "ERROR: The script cannot find the required command \"${CMD}\"."
    echo
    exit $?
  fi
done

# Verify OS & DIST environment variables have been set before calling this script
if [ -z "${OS}" ] || [ -z "${DIST}" ]; then
    echo "ERROR: both OS and DIST environment variables must be set"
    exit 1
fi

# Steps common to all builds
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
        exit $?
    fi
fi

# Steps common to Redhat distros
if [ "${OS}" == "el" ] || [ "${OS}" == "fedora" ]; then
    echo "Begin Redhat build..."

    # %autosetup support has been merged upstream. No need to patch
    #patch -p1 < utils/packpack/autosetup.patch
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
    result=`repoquery --repofrompath=zmpackpack,https://zmrepo.zoneminder.com/${zmrepodistro}/${DIST}/x86_64/ --repoid=zmpackpack --qf="%{location}" zmrepo 2> /dev/null`

    if [ -n "$result" ] && [ $? -eq 0  ]; then
        echo "Retrieving ZMREPO rpm..."
        curl $result > build/zmrepo.noarch.rpm
    else
        echo "ERROR: Failed to retrieve zmrepo rpm..."
        if [ $? -ne 0  ]; then
            echo $?
        else
            echo 1
        fi
    fi

    echo "Starting packpack..."
    packpack/packpack -f utils/packpack/redhat_package.mk redhat_package

# Steps common the Debian based distros
elif [ "${OS}" == "debian" ] || [ "${OS}" == "ubuntu" ]; then
    echo "Begin Debian build..."

    # patch packpack to remove "debian" from the source tarball filename
    patch --dry-run --silent -f -p1 < utils/packpack/deb.mk.patch 2>/dev/null
    if [ $? -eq 0 ]; then
        patch -p1 < utils/packpack/deb.mk.patch
    fi

    # Uncompress the Crud tarball and move it into place
    if [ -e "web/api/app/Plugin/Crud/LICENSE.txt" ]; then
        echo "Crud plugin already installed..."
    else     
        echo "Unpacking Crud plugin..."
        tar -xzf build/crud-${CRUDVER}.tar.gz
        rmdir web/api/app/Plugin/Crud
        mv -f crud-${CRUDVER} web/api/app/Plugin/Crud
    fi

    if [ ${DIST} == "trusty" ] || [ ${DIST} == "precise" ]; then
        ln -sf distros/ubuntu1204 debian
    elif [ ${DIST} == "wheezy" ]; then 
        ln -sf distros/debian debian
    else 
        ln -sf distros/ubuntu1604 debian
    fi

    echo "Starting packpack..."
    packpack/packpack
fi


