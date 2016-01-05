#!/bin/bash
DATE=`date -R`
DISTRO=$1
SNAPSHOT=$2
if [ "$SNAPSHOT" == "stable" ]; then
SNAPSHOT="";
fi;

TYPE=$3
if [ "$TYPE" == "" ]; then
TYPE="source";
fi;
BRANCH=$4

if [ ! -d 'zoneminder_release' ]; then 
	git clone https://github.com/ZoneMinder/ZoneMinder.git zoneminder_release
fi;
if [ "$BRANCH" != "" ]; then
	cd zoneminder_release
	git checkout $BRANCH
	cd ../
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
ln -sf distros/ubuntu1204_cmake debian
else
ln -sf distros/ubuntu1504_cmake debian
fi;

cat <<EOF > debian/changelog
zoneminder ($VERSION-$DISTRO-$SNAPSHOT) $DISTRO; urgency=medium

  * 

 -- Isaac Connor <iconnor@connortechnology.com>  $DATE

EOF
#rm -rf .git
#rm .gitignore
#cd ../
#tar zcf zoneminder_$VERSION-$DISTRO.orig.tar.gz zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
#cd zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig
if [ $TYPE == "binary" ]; then
	debuild
else
	debuild -S -sa
fi;

cd ../
echo "about to delete zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig. Hit enter to continue."
read delete
rm -fr zoneminder_$VERSION-$DISTRO-$SNAPSHOT.orig

