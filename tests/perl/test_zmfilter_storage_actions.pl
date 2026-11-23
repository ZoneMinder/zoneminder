#!/usr/bin/perl
#
# AutoMove and AutoCopy must not redo work for an event that is already in the
# target storage area, or the daemon copies every video again on each pass.
# Set ZMFILTER_IN to another zmfilter.pl.in to test it.
#
# Run as: perl tests/perl/test_zmfilter_storage_actions.pl
#
use strict;
use warnings;
use FindBin;

my ($failures, $moves, $copies, $saves) = (0, 0, 0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got == $want) { print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name (got $got, want $want)\n"; }
}

package ZoneMinder::Storage;
sub new { my ($c, $id) = @_; return bless { Id => $id }, $c; }
sub find_one { my ($c, %a) = @_; return bless { Id => $a{Id} }, $c; }
sub Path { return '/nonexistent/'.$_[0]{Id}; }
package FakeEvent;
sub save { $saves++; return ''; }
sub Storage { return ZoneMinder::Storage->new($_[0]{StorageId}); }
sub MoveTo { $moves++; return ''; }
sub CopyTo { $copies++; return ''; }
package main;

our %Config;
sub Debug {} sub Info {} sub Warning {} sub Error {}
sub zmDbExecute { $saves++; return 1; }

my $file = $ENV{ZMFILTER_IN} || "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($sub) = $src =~ /^(sub checkFilterEvent \{.*?^\}[^\n]*\n)/ms or die "checkFilterEvent not found\n";
eval "$sub; 1" or die "Can't compile: $@";

sub run {
  my ($event, %filter) = @_;
  ($moves, $copies, $saves) = (0, 0, 0);
  checkFilterEvent(\%filter, bless { Id => 1, %$event }, 'FakeEvent');
}

run({StorageId => 1}, AutoMove => 1, AutoMoveTo => 2);
check('event elsewhere is moved', $moves, 1);
run({StorageId => 2}, AutoMove => 1, AutoMoveTo => 2);
check('event already in the target is not moved again', $moves, 0);

run({StorageId => 1}, AutoCopy => 1, AutoCopyTo => 2);
check('event with no copy is copied', $copies, 1);
run({StorageId => 1, SecondaryStorageId => 3}, AutoCopy => 1, AutoCopyTo => 2);
check('event with its copy elsewhere is copied', $copies, 1);
run({StorageId => 1, SecondaryStorageId => 2}, AutoCopy => 1, AutoCopyTo => 2);
check('event already copied to the target is not copied again', $copies, 0);
run({StorageId => 2}, AutoCopy => 1, AutoCopyTo => 2);
check('event whose primary storage is the target is not copied', $copies, 0);

print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
