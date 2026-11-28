#!/usr/bin/perl
#
# ZoneMinder::Storage::bucket() must not die when the configured bucket is
# missing. It logs an Error naming the configured aws_bucket and returns undef
# so that callers (Storage::delete_path and Event::MoveTo) can fall back to the
# local filesystem. When the bucket exists it returns the bucket object.
#
# The sub is extracted from Storage.pm and eval'd against stubs, so no
# ZoneMinder::Config, Net::Amazon::S3 or database is needed.
#
# Run as: perl tests/perl/test_storage_bucket_missing.pl [path/to/Storage.pm]
#
use strict;
use warnings;
use FindBin;

my $storage_pm = $ARGV[0]
  || "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/Storage.pm";

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

sub abort {
  my ($why) = @_;
  print "FAIL - $why\n\n$passes passed, ", $failures + 1, " failed\n";
  exit 1;
}

open(my $fh, '<', $storage_pm) or abort("cannot read $storage_pm: $!");
my $src = do { local $/; <$fh> };
close($fh);

my ($sub_src) = $src =~ /(sub bucket\s*\{.*?^\})/ms;
abort("sub bucket not found in $storage_pm") if !defined $sub_src;

package FakeBucket;
sub name { $_[0]{name} }

package FakeS3;
our $result;
sub new { bless {}, shift }
sub bucket { $result }

package ZoneMinder::Storage;
our @errors;
sub Error { push @errors, $_[0]; }
sub s3 { FakeS3->new() }

package main;

{
  no warnings qw(redefine);
  my $ok = eval "package ZoneMinder::Storage; no warnings; $sub_src\n1;";
  abort('could not eval sub bucket: '.($@ || 'unknown error')) if !$ok;
}

# Missing bucket: no die, returns undef, Error names the configured bucket.
{
  @ZoneMinder::Storage::errors = ();
  $FakeS3::result = undef;
  my $self = bless { aws_bucket => 'zm-events' }, 'ZoneMinder::Storage';
  my $bucket;
  my $died = '';
  eval { $bucket = $self->bucket(); 1 } or $died = $@;
  check('missing bucket does not die', !$died, "died: $died");
  check('missing bucket returns undef', !defined $bucket,
    'got: '.(defined $bucket ? $bucket : 'undef'));
  check('missing bucket logs Error naming the configured aws_bucket',
    (@ZoneMinder::Storage::errors == 1
      and $ZoneMinder::Storage::errors[0] eq 'S3 bucket zm-events not found.'),
    'messages: '.join(' | ', map { defined $_ ? $_ : '(undef)' } @ZoneMinder::Storage::errors));
}

# Found bucket: returns the object and logs nothing.
{
  @ZoneMinder::Storage::errors = ();
  my $want = bless { name => 'zm-bucket' }, 'FakeBucket';
  $FakeS3::result = $want;
  my $self = bless { aws_bucket => 'zm-bucket' }, 'ZoneMinder::Storage';
  my $bucket = eval { $self->bucket() };
  check('found bucket does not die', !$@, "died: $@");
  check('found bucket returns the bucket object',
    (defined $bucket and $bucket == $want), 'got: '.(defined $bucket ? $bucket : 'undef'));
  check('found bucket logs no Error', !@ZoneMinder::Storage::errors,
    'unexpected: '.join(' | ', @ZoneMinder::Storage::errors));
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
