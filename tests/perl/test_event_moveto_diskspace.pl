#!/usr/bin/perl
# Event::MoveTo() when the files are already at the destination (a move that
# was copied but never recorded). The event record still has to be updated, its
# DiskSpace measured at the new location and the new storage area credited.
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use File::Temp qw(tempdir);
use File::Path qw(make_path);

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Database.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  $INC{$_} = 1 for qw(ZoneMinder/Object.pm ZoneMinder/Frame.pm ZoneMinder/Monitor.pm
    ZoneMinder/Event_Tag.pm ZoneMinder/Tag.pm Number/Bytes/Human.pm Date/Manip.pm Date/Parse.pm);
  package ZoneMinder::Object;
  sub new { my $c = shift; return bless {}, $c; }
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config = (ZM_EVENT_IMAGE_DIGITS => 5);
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Database;
  our $dbh = bless {AutoCommit => 1}, 'FakeDbh';
  sub import {}
  package FakeDbh;
  sub begin_work { $_[0]{AutoCommit} = 0 } sub commit { $_[0]{AutoCommit} = 1 } sub rollback { $_[0]{AutoCommit} = 1 }
  package ZoneMinder::Logger;
  sub INFO { 1 } sub logLevel { 0 } sub Debug { 1 } sub Info { 1 } sub Warning { 1 } sub Error { 1 } sub Fatal { 1 }
  sub import {
    no strict 'refs';
    my $c = caller();
    *{$c.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
}
use ZoneMinder::Event;

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

my $tmp = tempdir(CLEANUP => 1);
make_path("$tmp/old", "$tmp/new/1/2024-01-01/7");
open(my $fh, '>', "$tmp/new/1/2024-01-01/7/00001-capture.jpg") or die;
print $fh 'x' x 100;
close($fh);

{
  package FakeStorage;
  our @adjust;
  sub new { my ($c, %a) = @_; return bless {%a}, $c; }
  sub Id { $_[0]{Id} }
  sub Path { $_[0]{Path} }
  sub Type { 'local' }
  sub adjust_diskspace { push @adjust, [$_[0]{Id}, $_[1]]; }
}
my $old = FakeStorage->new(Id => 1, Path => "$tmp/old");
my $new = FakeStorage->new(Id => 2, Path => "$tmp/new");

{
  no warnings qw(redefine once);
  *ZoneMinder::Event::canEdit = sub { 1 };
  *ZoneMinder::Event::to_string = sub { 'event' };
  *ZoneMinder::Event::lock_and_load = sub { 1 };
  *ZoneMinder::Event::delete_files = sub { 1 };
  *ZoneMinder::Event::CopyTo = sub { die 'CopyTo must not run' };
  *ZoneMinder::Event::save = sub { $_[0]{saved} = {%{$_[0]}}; return ''; };
  *ZoneMinder::Event::Storage = sub {
    my $self = shift;
    if (@_) {
      $self->{Storage} = $_[0];
      if ($_[0]) { $self->{StorageId} = $_[0]->Id(); $self->{Path} = undef; }
    }
    $self->{Storage} = $old if !$self->{Storage} and $self->{StorageId} == 1;
    return $self->{Storage};
  };
}
my $event = bless {Id => 7, MonitorId => 1, StorageId => 1, Scheme => 'Medium',
  RelativePath => '1/2024-01-01/7', DiskSpace => 100}, 'ZoneMinder::Event';
my $error = $event->MoveTo($new);
check('no error', !$error);
check('record now points at the new storage', $event->{saved}{StorageId} == 2);
check('saved DiskSpace measured at the new location', ($event->{saved}{DiskSpace} // -1) == 100);
check('old storage debited', grep { $_->[0] == 1 and $_->[1] == -100 } @FakeStorage::adjust);
check('new storage credited', grep { $_->[0] == 2 and $_->[1] == 100 } @FakeStorage::adjust);

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
