#!/usr/bin/perl
#
# The path after the bucket in an s3fs Url is a prefix inside the bucket. The
# keys ZoneMinder builds from it are <prefix>/<event path>/<file>, with no
# leading slash, so the Url from the documentation,
#   s3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket-name/events
# must give events/1/2026-11-11/123/..., not /events1/2026-11-11/123/...
#
# Net::Amazon::S3 and the ZoneMinder modules Storage.pm loads are stubbed, so
# neither a database nor S3 is needed.
#
# Run as: perl tests/perl/test_storage_s3_subpath.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{$_} = 1 for qw(ZoneMinder/Base.pm ZoneMinder/Object.pm ZoneMinder/Server.pm
    ZoneMinder/General.pm ZoneMinder/Config.pm ZoneMinder/Logger.pm ZoneMinder/Database.pm
    Net/Amazon/S3.pm Net/Amazon/S3/Vendor/Generic.pm);
  package ZoneMinder::Object; sub new { bless {}, shift }
  package ZoneMinder::Config; our %Config = (); sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Database; sub import {}
  package ZoneMinder::Logger;
  sub import {
    no strict 'refs';
    my $c = caller();
    *{$c.'::'.$_} = sub { 1 } for qw(Debug Info Warning Error Fatal urlForLog);
  }
  package Net::Amazon::S3; sub new { bless {}, shift }
  package Net::Amazon::S3::Vendor::Generic; sub new { bless {}, shift }
}
use ZoneMinder::Storage;

my $passes = 0;
my $failures = 0;
sub check {
  my ($name, $ok, $detail) = @_;
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print "  $detail\n" if defined $detail;
  }
}

sub subpath_for {
  my $Storage = bless { Type => 's3fs', Url => shift }, 'ZoneMinder::Storage';
  $Storage->s3();
  return $Storage->aws_subpath();
}

for my $case (
  [ 's3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket-name/events', 'events/' ],
  [ 's3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket-name/events/', 'events/' ],
  [ 's3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket-name/a/b', 'a/b/' ],
  [ 's3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket-name', '' ],
) {
  my ($url, $want) = @$case;
  my $got = subpath_for($url);
  check("prefix of $url is '$want'", $got eq $want, "got '$got'");
}

# The keys built from it, as Event::CopyTo and Storage::delete_path do.
check('an event key has one slash between the prefix and the event path',
  subpath_for('s3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket-name/events').'1/2026-11-11/123'
    eq 'events/1/2026-11-11/123');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
