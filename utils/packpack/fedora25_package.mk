.PHONY: fedora25_package
.NOTPARALLEL: fedora25_package

fedora25_package: fedora25_bootstrap package

fedora25_bootstrap:
	sudo dnf install -y curl
	sudo dnf install -y --nogpgcheck build/zmrepo-25-1.fc25.noarch.rpm

