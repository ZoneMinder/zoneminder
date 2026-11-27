#!/usr/bin/perl
# Event::delete_files() with no explicit storage walks the primary and the
# secondary storage; a storage with DoDelete off must be left alone.
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  $INC{$_} = 1 for qw(ZoneMinder/Storage.pm ZoneMinder/Object.pm ZoneMinder/Frame.pm ZoneMinder/Monitor.pm
    ZoneMinder/Event_Tag.pm ZoneMinder/Tag.pm Number/Bytes/Human.pm Date/Manip.pm Date/Parse.pm);
  package ZoneMinder::Object;
  sub new { my $c = shift; return bless {}, $c; }
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config = (ZM_EVENT_IMAGE_DIGITS => 5);
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  sub INFO { 1 } sub logLevel { 0 } sub Debug { 1 } sub Info { 1 } sub Warning { 1 } sub Error { 1 } sub Fatal { 1 }
  sub import {
    no strict 'refs';
    my $c = caller();
    *{$c.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
  package ZoneMinder::Storage;
  our @deleted;
  my %do = (1 => 1, 2 => 0);
  sub new { my ($c, $id) = @_; return bless {Id => $id}, $c; }
  sub Path { return '/s'.$_[0]{Id}; }
  sub Type { return 'local'; }
  sub DoDelete { return $do{$_[0]{Id}}; }
  sub delete_path { push @deleted, $_[0]{Id}; }
}

use ZoneMinder::Event;

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

{
  no warnings 'redefine';
  *ZoneMinder::Event::canEdit = sub { 1 };
  *ZoneMinder::Event::RelativePath = sub { '1/2024-01-01/1' };
  *ZoneMinder::Event::LinkPath = sub { '1/2024-01-01/.1' };
  *ZoneMinder::Event::Scheme = sub { 'Medium' };
}
my $event = bless {Id => 1, MonitorId => 1, StorageId => 1, SecondaryStorageId => 2, Scheme => 'Medium'}, 'ZoneMinder::Event';
$event->delete_files();
check('primary (DoDelete=1) files deleted', grep { $_ == 1 } @ZoneMinder::Storage::deleted);
check('secondary (DoDelete=0) files kept', !grep { $_ == 2 } @ZoneMinder::Storage::deleted);

@ZoneMinder::Storage::deleted = ();
$event->delete_files(ZoneMinder::Storage->new(2));
check('explicit storage argument is still honoured', grep { $_ == 2 } @ZoneMinder::Storage::deleted);

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
