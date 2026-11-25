#!/usr/bin/perl
# Event::CopyTo() to an S3 storage area uploads every file of the event, also
# when the path of the storage area it is on has a space in it.
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

{
  package FakeStorage;
  sub new { my ($c, %a) = @_; return bless {%a}, $c; }
  sub Id { $_[0]{Id} }
  sub Path { $_[0]{Path} }
  sub Type { $_[0]{Type} }
  sub s3 { 1 }
  sub bucket { bless {}, 'FakeBucket' }
  sub aws_subpath { 'zm/' }
  package FakeBucket;
  our %uploaded;
  sub add_key_filename { my ($self, $key, $file) = @_; return 0 if !-f $file; $uploaded{$key} = $file; return 1; }
}

{
  no warnings qw(redefine once);
  *ZoneMinder::Event::canEdit = sub { 1 };
  *Number::Bytes::Human::format_bytes = sub { $_[0] };
  *ZoneMinder::Event::Storage = sub { $_[0]{Storage} };
  *ZoneMinder::Event::Path = sub { $_[0]{Storage}->Path().'/'.$_[0]{RelativePath} };
}

my $tmp = tempdir(CLEANUP => 1);
my $new = FakeStorage->new(Id => 2, Path => '/s3', Type => 's3fs');
for my $name ('events', 'zm events') {
  make_path("$tmp/$name/1/2024-01-01/7");
  for my $file ('00001-capture.jpg', '7-video.h264.mp4') {
    open(my $fh, '>', "$tmp/$name/1/2024-01-01/7/$file") or die;
    print $fh 'x' x 100;
    close($fh);
  }
  %FakeBucket::uploaded = ();
  my $event = bless {Id => 7, MonitorId => 1, StorageId => 1, Scheme => 'Medium',
    EndDateTime => '2024-01-01 00:01:00', RelativePath => '1/2024-01-01/7',
    Storage => FakeStorage->new(Id => 1, Path => "$tmp/$name", Type => 'local')}, 'ZoneMinder::Event';
  my $error = $event->CopyTo($new);
  check("'$name': no error", !$error);
  check("'$name': every file is uploaded",
    join(',', sort keys %FakeBucket::uploaded) eq 'zm/1/2024-01-01/7/00001-capture.jpg,zm/1/2024-01-01/7/7-video.h264.mp4');
}

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
