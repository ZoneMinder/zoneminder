#!/bin/bash
DATE=`date -R`
DISTRO=$1
SNAPSHOT=$2
TYPE=$3
if [ "$TYPE" == "" ]; then
TYPE="source";
fi;

if [ ! -d 'zoneminder_release' ]; then 
git clone https://github.com/ZoneMinder/ZoneMinder.git zoneminder_release
fi;
VERSION=`cat zoneminder_release/version`
if [ $VERSION == "" ]; then
exit 1;
fi;
echo "Doing $TYPE release zoneminder_$VERSION-$DISTRO-$SNAPSHOT";
mv zoneminder_release zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
cd zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
git submodule init
git submodule update --init --recursive
if [ $DISTRO == "trusty" ]; then 
cp -r distros/ubuntu1204_cmake debian
else
cp -r distros/ubuntu1504_cmake debian
fi;

cat <<EOF > debian/changelog
zoneminder ($VERSION-$DISTRO-$SNAPSHOT) $DISTRO; urgency=medium

  * 

 -- Isaac Connor <iconnor@connortechnology.com>  $DATE

EOF
rm -rf .git
rm .gitignore
cd ../
tar zcf zoneminder_$VERSION-$DISTRO.orig.tar.gz zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
cd zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
if [ $TYPE == "binary" ]; then
debuild -k52C7377E
else
debuild -S -sa -k52C7377E
fi;
cd ../
rm -fr zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
