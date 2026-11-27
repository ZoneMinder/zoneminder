#!/usr/bin/perl
#
# Flags and DiskSpace that zmfilter.pl's actions store must survive a later
# full save of the same event, columns zmc changes while a filter works on the
# event must not be put back, and an event deleted meanwhile must stay deleted.
#
# Run as: perl tests/perl/test_zmfilter_event_flags.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Basename;

BEGIN {
  $INC{$_} = 1 for qw(MIME/Lite.pm Net/SMTP.pm Net/FTP.pm Archive/Tar.pm);
  package MIME::Lite;
  sub new { bless {}, shift } sub attach { 1 } sub send { 1 }
  package Net::FTP;
  sub new { bless {}, shift } sub login { 1 } sub binary { 1 } sub cwd { 1 }
  sub put { 1 } sub quit { 1 } sub message { '' }
  package Archive::Tar;
  sub create_archive { 1 } sub error { '' }
}

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print "  want: $want\n";
  }
}

# The Events table, by Id, and the bytes the event's files take on disk
our %rows;
our $files_size;

package ZoneMinder::Event;
sub new { my ($class, $id, $row) = @_; return bless { %$row }, 'FakeEvent'; }

package FakeEvent;
# As ZoneMinder::Object::load: a row that is gone takes the Id with it
sub load {
  my $self = shift;
  if ($main::rows{$$self{Id}}) {
    @$self{keys %{$main::rows{$$self{Id}}}} = values %{$main::rows{$$self{Id}}};
  } else {
    delete $$self{Id};
  }
  return $self;
}
# As ZoneMinder::Object::save: writes every field; without an Id it inserts
sub save {
  my ($self, $data) = @_;
  @$self{keys %$data} = values %$data if $data;
  my %row = map { $_ => $$self{$_} } grep { $_ ne 'Id' } keys %$self;
  if (!$$self{Id}) {
    $$self{Id} = 1 + (sort { $b <=> $a } 0, keys %main::rows)[0];
    $main::rows{$$self{Id}} = { %row, Id => $$self{Id} };
  } elsif ($main::rows{$$self{Id}}) {
    @{$main::rows{$$self{Id}}}{keys %row} = values %row;
  }
  return '';
}
sub Monitor { return bless { Name => 'Cam', DefaultRate => 100, DefaultScale => 100 }, 'FakeMonitor'; }
sub Path { return '/nonexistent/1'; }
# As ZoneMinder::Event::DiskSpace: undef asks for the size on disk
sub DiskSpace {
  my $self = shift;
  $$self{DiskSpace} = shift if @_;
  $$self{DiskSpace} = $main::files_size if !defined $$self{DiskSpace};
  return $$self{DiskSpace};
}
package FakeMonitor;
sub Name { return $_[0]{Name}; }
package FakeFilter;
sub Execute { return map { { %$_ } } values %main::rows; }
package FakeSth;
sub execute { my ($self, $id) = @_; $main::rows{$id}{$$self{column}} = 1 if $main::rows{$id}; return 1; }
package FakeDbh;
sub prepare_cached { my ($self, $sql) = @_; my ($column) = $sql =~ /SET `(\w+)`/; return bless { column => $column }, 'FakeSth'; }
sub errstr { '' }
package main;

my $tmp = tempdir(CLEANUP => 1);
our %Config = (
  ZM_PATH_BIN => '/usr/bin', ZM_FFMPEG_FORMATS => 'mp4*', ZM_OPT_FFMPEG => 1,
  ZM_OPT_EMAIL => 1, ZM_OPT_MESSAGE => 1, ZM_OPT_UPLOAD => 1, ZM_FROM_EMAIL => 'zm@example.com',
  ZM_MESSAGE_ADDRESS => 'sms@example.com', ZM_MESSAGE_SUBJECT => 'subject', ZM_MESSAGE_BODY => 'body',
  ZM_NEW_MAIL_MODULES => 1, ZM_UPLOAD_HOST => 'upload.example.com', ZM_UPLOAD_LOC_DIR => $tmp,
  ZM_UPLOAD_ARCH_FORMAT => 'tar', ZM_UPLOAD_PROTOCOL => 'ftp',
);
our $dbh = bless {}, 'FakeDbh';
our $zm_terminate = 0;
our $delete_during_video = 0;
our $close_during_video = 0;
our $video_exists = 0;
our $video_fails = 0;
sub Debug {} sub Info {} sub Warning {} sub Error {} sub Fatal { die @_ }
sub logDebugging { 0 }
sub commandIsAllowed { 1 }
sub make_path {}
sub zmDbConnect { return $dbh; }
our $ev_tag = 0;
# %EV% in the body makes the event's video
sub substituteTags {
  my ($text, $filter, $Event) = @_;
  return undef if $ev_tag and $Event and !generateVideo($filter, $Event);
  return 'text';
}
sub sendTheEmail { return 1 }
sub zmDbExecute {
  my ($sql, $id) = @_;
  my ($column) = $sql =~ /SET `(\w+)`=1/;
  $rows{$id}{$column} = 1 if $rows{$id};
  return 1;
}
sub zmDbFetchOne {
  my ($sql, $id) = @_;
  my ($column) = $sql =~ /^SELECT `(\w+)` FROM `Events`/ or die "unexpected query $sql";
  return $rows{$id} ? { $column => $rows{$id}{$column} } : undef;
}

# zmvideo.pl: writes the video and records the event's new DiskSpace
BEGIN {
  *CORE::GLOBAL::readpipe = sub {
    my $command = shift;
    # An AutoExecuteCmd, run with the event's path
    if ($command =~ /^(zmtest-\w+) /) {
      delete $main::rows{1} if $1 eq 'zmtest-delete';
      $? = 0;
      return '';
    }
    die "unexpected command $command" if $command !~ /zmvideo\.pl/;
    if ($main::video_fails) { $? = 1 << 8; return ''; }
    # An existing video is left alone and DiskSpace is not stored
    if (!$main::video_exists) {
      $main::files_size = 5000;
      $main::rows{1}{DiskSpace} = 5000;
    }
    delete $main::rows{1} if $main::delete_during_video;
    # zmc closes the event while its video is made
    @{$main::rows{1}}{qw(EndDateTime Frames Length)} = ('2026-11-01 10:01:00', 600, 60) if $main::close_during_video;
    $? = 0;
    return "/nonexistent/1/video.mp4\n";
  };
}

my $file = "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
foreach my $name (qw(checkFilter checkFilterEvent generateVideo uploadArchFile sendEmail sendMessage sendSummaryEmail executeCommand)) {
  my ($sub) = $src =~ /^(sub $name \{.*?^\}[^\n]*\n)/ms;
  die "sub $name not found in $file\n" if !$sub;
  eval "$sub; 1" or die "Can't compile $name: $@";
}

sub run_filter {
  my %filter = @_;
  $delete_during_video = delete $filter{delete_during_video} // 0;
  $close_during_video = delete $filter{close_during_video} // 0;
  $video_exists = delete $filter{video_exists} // 0;
  $video_fails = delete $filter{video_fails} // 0;
  $ev_tag = delete $filter{ev_tag} // 0;
  local $Config{ZM_MESSAGE_ADDRESS} = '' if delete $filter{message_fails};
  $files_size = $video_exists ? 5000 : 10;
  %rows = (1 => { Id => 1, MonitorId => 1, DiskSpace => 1000, Archived => 0,
    Videoed => 0, Uploaded => 0, Emailed => 0, Messaged => 0, Executed => 0,
    EndDateTime => undef, Frames => 10, Length => undef });
  checkFilter(bless { Name => 'test', EmailTo => 'a@example.com', EmailFormat => 'Individual',
    EmailSubject => 'subject', EmailBody => "head\n%SUMMARY%event\n%SUMMARY%tail", %filter }, 'FakeFilter');
}

run_filter(AutoVideo => 1, AutoEmail => 1);
check('video then email: Emailed is set', $rows{1}{Emailed}, 1);
check('video then email: Videoed is still set', $rows{1}{Videoed}, 1);
check('video then email: DiskSpace includes the video', $rows{1}{DiskSpace}, 5000);

run_filter(AutoVideo => 1, AutoEmail => 1, EmailFormat => 'Summary');
check('video then summary email: Emailed is set', $rows{1}{Emailed}, 1);
check('video then summary email: Videoed is still set', $rows{1}{Videoed}, 1);
check('video then summary email: DiskSpace includes the video', $rows{1}{DiskSpace}, 5000);

run_filter(AutoMessage => 1, UpdateDiskSpace => 1);
check('message then disk space update: DiskSpace is saved', $rows{1}{DiskSpace}, 10);
check('message then disk space update: Messaged is still set', $rows{1}{Messaged}, 1);

run_filter(AutoUpload => 1, UpdateDiskSpace => 1);
check('upload then disk space update: DiskSpace is saved', $rows{1}{DiskSpace}, 10);
check('upload then disk space update: Uploaded is still set', $rows{1}{Uploaded}, 1);

run_filter(AutoVideo => 1, UpdateDiskSpace => 1, video_exists => 1);
check('video already made then disk space update: DiskSpace is saved', $rows{1}{DiskSpace}, 5000);

run_filter(AutoVideo => 1, AutoEmail => 1, EmailFormat => 'Summary', AutoDelete => 1, UpdateDiskSpace => 1, video_fails => 1);
check('summary email, delete skipped, disk space update: Emailed is still set', $rows{1}{Emailed}, 1);

run_filter(AutoArchive => 1, AutoEmail => 1, EmailFormat => 'Summary', AutoDelete => 1, AutoMessage => 1, message_fails => 1, ev_tag => 1);
check('summary email made the video, delete skipped, archived: Videoed is still set', $rows{1}{Videoed}, 1);
check('summary email made the video, delete skipped, archived: DiskSpace includes the video', $rows{1}{DiskSpace}, 5000);
check('summary email made the video, delete skipped, archived: Emailed is still set', $rows{1}{Emailed}, 1);

run_filter(AutoVideo => 1, AutoEmail => 1, delete_during_video => 1);
check('event deleted while its video is made is not written back', join(',', sort keys %rows), '');

run_filter(AutoVideo => 1, AutoEmail => 1, close_during_video => 1);
check('event closed while its video is made: Emailed is set', $rows{1}{Emailed}, 1);
check('event closed while its video is made: EndDateTime is kept', $rows{1}{EndDateTime}, '2026-11-01 10:01:00');
check('event closed while its video is made: Frames is kept', $rows{1}{Frames}, 600);

run_filter(AutoExecute => 1, AutoExecuteCmd => 'zmtest-ok', UpdateDiskSpace => 1);
check('execute then disk space update: Executed is set', $rows{1}{Executed}, 1);
check('execute then disk space update: DiskSpace is saved', $rows{1}{DiskSpace}, 10);

run_filter(AutoExecute => 1, AutoExecuteCmd => 'zmtest-delete');
check('event deleted by its command is not written back', join(',', sort keys %rows), '');

run_filter(AutoExecute => 1, AutoExecuteCmd => 'zmtest-delete', UpdateDiskSpace => 1);
check('event deleted by its command is not written back by the disk space update', join(',', sort keys %rows), '');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
