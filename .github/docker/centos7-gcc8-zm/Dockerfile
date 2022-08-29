FROM centos:7

LABEL	name="centos7-gcc8-zm" \
	version="1"

RUN	yum -y install https://mirrors.rpmfusion.org/free/el/rpmfusion-free-release-7.noarch.rpm https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm && \
	yum -y install https://repo.ius.io/ius-release-el7.rpm && yum -y install git236 && \
	yum -y update && yum -y install make cmake3 gcc-c++ mariadb-devel ffmpeg-devel libcurl-devel vlc-devel libvncserver-devel libjpeg-turbo-devel "perl(Date::Manip)" "perl(DBD::mysql)" "perl(ExtUtils::MakeMaker)" "perl(Sys::Mmap)" "perl(Sys::Syslog)" "perl(LWP::UserAgent)" polkit-devel libjwt-devel && \
	yum -y install centos-release-scl-rh && \
	INSTALL_PKGS="devtoolset-8-gcc devtoolset-8-gcc-c++" && \
	yum -y install --setopt=tsflags=nodocs $INSTALL_PKGS
