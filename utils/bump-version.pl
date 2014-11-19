#!/usr/bin/env perl

# This script will bump the version number in any files listed in the below
# @files array.  It can only bump versions up, and does so by use of sed.

use strict;
use warnings;
use Getopt::Long;

my @files = (
	"../version",
	"../configure.ac",
	"../CMakeLists.txt"
);


my ($new, $current);

open my $file, "../version" or die $!;
chomp($current = <$file>);
close $file;

sub usage {
	print "Usage: bump-version.sh -n <new-version>\n";
	exit 1;
}

sub bump_version {
	foreach my $file (@files) {
		system("sed -i \"s/$current/$new/g\" $file");
	}
}


GetOptions ("n=s" => \$new) or usage;
usage if ! $new;
die("New version ($new) is not greater than old version ($current)!") if ( $new le $current);

bump_version;
