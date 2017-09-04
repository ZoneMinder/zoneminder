.PHONY: redhat_package
.NOTPARALLEL: redhat_package

redhat_package: redhat_bootstrap package

redhat_bootstrap:
	sudo yum install -y --nogpgcheck build/external-repo.noarch.rpm

