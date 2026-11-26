#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use Data::Dumper;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

# Fake storage trees live beside the checkout, never in the real event store.
my $tmp_root = -w "$FindBin::Bin/../../.." ? "$FindBin::Bin/../../.." : File::Spec->tmpdir();

# Runs the generated zmaudit.pl against a stubbed database and a fake storage
# tree. Returns ($output, $exit_code, $log, $dir).
#   setup    => sub { my $dir = shift; ... build the fake tree ... }
#   data     => { storages, monitors, events, event_rows, find_one, monitor_find_one }
#   args     => [ command line options ]
sub run_zmaudit {
  my (%a) = @_;
  my $dir = tempdir('zmaudit-XXXXXX', DIR => $tmp_root, CLEANUP => 1);
  make_path("$dir/lib/ZoneMinder", "$dir/run", "$dir/storage", "$dir/events", "$dir/swap");
  $a{setup}->($dir) if $a{setup};

  my %data = %{ $a{data} || {} };
  $data{storages} ||= [ { Id => 1, Name => 'one', Path => "$dir/storage", ServerId => 0, DoDelete => 1 } ];
  $data{monitors} ||= [ { Id => 1, EventPrefix => 'Event-' } ];
  local $Data::Dumper::Terse = 1;
  my $dumped = Dumper(\%data);
  $dumped =~ s/\@DIR\@/$dir/g;
  my $log = "$dir/log";

  write_file("$dir/lib/ZoneMinder.pm", <<"EOM");
package ZoneMinder::Base; use constant ZM_VERSION => '0'; \$INC{'ZoneMinder/Base.pm'} = 1;
package ZoneMinder::Logger; use constant AUDIT => 5; sub logHupHandler { 1 }
package ZoneMinder::Object; sub init_cache { 1 }
package ZoneMinder::Database; sub zmDbDo { return 1; }
package ZMAData; our \$D = $dumped;
package FakeDBH;
sub ping { 1 } sub err { 0 } sub errstr { '' } sub do { 1 }
sub prepare_cached { my (\$s, \$sql) = \@_; ZMALog::l("SQL \$sql"); return bless({sql=>\$sql, rows=>[]}, 'FakeSth'); }
sub prepare { shift->prepare_cached(\@_) }
sub selectall_arrayref { [] }
sub selectall_hashref {
  my (\$s, \$sql, \$key, \$attr, \@ids) = \@_;
  my \%r = map { \$_ => \$ZMAData::D->{event_rows}{\$_} } grep { \$ZMAData::D->{event_rows}{\$_} } \@ids;
  return \\\%r;
}
package FakeSth;
sub execute {
  my (\$s, \@args) = \@_;
  if ( \$s->{sql} =~ /FROM `Monitors`/ ) {
    \$s->{rows} = [ map { {\%\$_} } \@{\$ZMAData::D->{monitors}} ];
  } elsif ( \$s->{sql} =~ /FROM `Events` WHERE `MonitorId` = \\?/ ) {
    \$s->{rows} = [ map { {\%\$_} } \@{ \$ZMAData::D->{events}{\$args[0]} || [] } ];
  }
  return 1;
}
sub fetchrow_hashref { shift \@{\$_[0]{rows}} }
sub rows { 0 } sub errstr { '' }
package ZMALog;
sub l { open(my \$f, '>>', '$log') or return; print \$f "\$_[0]\\n"; close \$f; }
package ZoneMinder;
our \%Config = (
  ZM_AUDIT_MIN_AGE => 3600, ZM_AUDIT_CHECK_INTERVAL => 1, ZM_DIR_EVENTS => '$dir/events',
  ZM_PATH_SWAP => '$dir/swap', ZM_PATH_RM => '/bin/rm', ZM_SERVER_ID => 0,
  ZM_LOG_DATABASE_LIMIT => 0, ZM_LOG_AUDIT_DATABASE_LIMIT => 0,
);
sub import {
  no strict 'refs';
  my \$caller = caller();
  for my \$n ( qw(Debug Info Warning Error) ) { *{\$caller.'::'.\$n} = sub { ZMALog::l(uc(\$n).' '.\$_[0]); }; }
  *{\$caller.'::Fatal'} = sub { ZMALog::l("FATAL \$_[0]"); exit 99; };
  *{\$caller.'::logInit'} = sub { 1 };
  *{\$caller.'::ZM_PID'} = sub () { '/nonexistent/zm.pid' };
  *{\$caller.'::zmDbConnect'} = sub { return bless({}, 'FakeDBH'); };
  *{\$caller.'::executeShellCommand'} = sub { ZMALog::l("SHELL \$_[0]"); exit 7 if ++\$ZMAData::shells > 3; system(\$_[0]); };
  *{\$caller.'::Config'} = \\\%Config;
}
1;
EOM
  write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
  write_file("$dir/lib/ZoneMinder/Storage.pm", <<'EOM');
package ZoneMinder::Storage;
sub find {
  shift; my %a = @_;
  my @s = map { bless({%$_}, __PACKAGE__) } @{$ZMAData::D->{storages}};
  @s = grep { $_->{ServerId} == $a{ServerId} } @s if defined $a{ServerId};
  return @s;
}
sub Id { $_[0]{Id} } sub Name { $_[0]{Name} } sub Path { $_[0]{Path} }
sub DoDelete { $_[0]{DoDelete} } sub ServerId { $_[0]{ServerId} }
sub Server { bless({Name => 'srv'}, 'ZoneMinder::Server') }
package ZoneMinder::Server; sub Name { $_[0]{Name} }
1;
EOM
  write_file("$dir/lib/ZoneMinder/Monitor.pm", <<'EOM');
package ZoneMinder::Monitor;
sub find_one { shift; my %a = @_; my $m = ($ZMAData::D->{monitor_find_one} || {})->{$a{Id}}; return $m ? bless({%$m}, __PACKAGE__) : undef; }
sub find { return; }
1;
EOM
  write_file("$dir/lib/ZoneMinder/Event.pm", <<'EOM');
package ZoneMinder::Event;
sub new { my ($c, $id, $row) = @_; my $s = bless({}, $c); %$s = %$row if $row; $s->{Id} = $id if defined $id; return $s; }
sub find_one {
  shift; my %a = @_;
  my $row = $a{Id} ? $ZMAData::D->{find_one}{$a{Id}} : undef;
  return $row ? __PACKAGE__->new($a{Id}, $row) : undef;
}
for my $f ( qw(MonitorId DiskSpace SaveJPEGs Archived StartDateTime EndDateTime Scheme RelativePath LinkPath Time) ) {
  no strict 'refs';
  *$f = sub { $_[0]{$f} = $_[1] if @_ > 1; return $_[0]{$f}; };
}
sub StorageId { if ( @_ > 1 ) { $_[0]{StorageId} = $_[1]; delete $_[0]{Path}; } return $_[0]{StorageId}; }
sub Storage {
  my ($s) = @_;
  my ($st) = grep { $_->{Id} == ($s->{StorageId} // 0) } @{$ZMAData::D->{storages}};
  return bless({ $st ? %$st : (Id => 0) }, 'ZoneMinder::Storage');
}
sub Path {
  my $s = shift;
  if ( @_ ) { $s->{Path} = $_[0]; }
  if ( !$s->{Path} ) {
    my $st = $s->Storage();
    $s->{Path} = $st->Path().'/'.($s->{RelativePath} // ($s->{MonitorId}.'/'.$s->{Id}));
  }
  return $s->{Path};
}
sub age {
  if ( !$_[0]{age} and -e $_[0]->Path() ) { $_[0]{age} = time() - (stat($_[0]->Path()))[9]; }
  return $_[0]{age};
}
sub files { my $s = shift; opendir(my $d, $s->Path()) or return; my @f = readdir($d); closedir($d); return @f; }
sub has_capture_jpegs { scalar grep { /^\d+-capture\.jpg$/ } $_[0]->files() }
sub has_analyse_jpegs { scalar grep { /^\d+-analyse\.jpg$/ } $_[0]->files() }
sub check_for_in_filesystem { my $p = $_[0]->Path(); return ($p and -d $p and grep { !/^\./ } $_[0]->files()) ? 1 : 0; }
sub save { ZMALog::l("SAVE $_[0]{Id}"); 1 }
sub delete { ZMALog::l("DELETE $_[0]{Id}"); 1 }
sub delete_files {
  ZMALog::l("DELETE_FILES $_[0]{Id}");
  exit 7 if ++$ZMAData::calls > 3;
  return if !$_[0]->Storage()->DoDelete();
  system('/bin/rm', '-rf', $_[0]->Path());
}
sub to_string { "event $_[0]{Id}" }
1;
EOM

  my $source = do {
    open(my $fh, '<', "$FindBin::Bin/../../scripts/zmaudit.pl.in") or die "Can't open zmaudit.pl.in: $!";
    local $/;
    <$fh>;
  };
  $source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
  $source =~ s/\@EXTRA_PERL_LIB\@//g;
  $source =~ s/\@ZM_RUNDIR\@/$dir\/run/g;
  $source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
  write_file("$dir/zmaudit.pl", $source);

  my $args = join(' ', @{ $a{args} || [] });
  my $out = qx(cd $dir && timeout 60 $^X -T -I$dir/lib $dir/zmaudit.pl $args 2>&1 </dev/null);
  my $exit = $? >> 8;
  my $logtxt = '';
  if ( open(my $f, '<', $log) ) { local $/; $logtxt = <$f>; close $f; }
  return ($out, $exit, $logtxt, $dir);
}

sub finish {
  print "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}

# -r is documented as "Just report don't actually do anything". It must not
# remove empty directories or write corrections to the Events table.
my ($out, $exit, $log, $dir) = run_zmaudit(
  setup => sub {
    my $d = shift;
    make_path("$d/storage/1/2026-02-02", "$d/storage/1/26/01/01/10/00/00");
    open(my $f, '>', "$d/storage/1/26/01/01/10/00/00/5-video.mp4"); close $f;
    symlink('10/00/00', "$d/storage/1/26/01/01/.5");
  },
  data => {
    events => { 1 => [ { Id => 5, Age => 10 } ] },
    find_one => { 5 => { Id => 5, MonitorId => 1, StorageId => 0, SaveJPEGs => 1, Scheme => 'Deep' } },
  },
  args => ['-r'],
);
check('report mode keeps empty directories', -d "$dir/storage/1/2026-02-02" ? 'kept' : 'removed', 'kept');
check('report mode does not save the corrected event', ($log =~ /^SAVE 5$/m ? 'saved' : 'not saved'), 'not saved');

# A normal run still removes the empty directory and corrects the event.
($out, $exit, $log, $dir) = run_zmaudit(
  setup => sub {
    my $d = shift;
    make_path("$d/storage/1/2026-02-02", "$d/storage/1/26/01/01/10/00/00");
    open(my $f, '>', "$d/storage/1/26/01/01/10/00/00/5-video.mp4"); close $f;
    symlink('10/00/00', "$d/storage/1/26/01/01/.5");
  },
  data => {
    events => { 1 => [ { Id => 5, Age => 10 } ] },
    find_one => { 5 => { Id => 5, MonitorId => 1, StorageId => 0, SaveJPEGs => 1, Scheme => 'Deep' } },
  },
  args => [],
);
check('normal run removes empty directories', -d "$dir/storage/1/2026-02-02" ? 'kept' : 'removed', 'removed');
check('normal run saves the corrected event', ($log =~ /^SAVE 5$/m ? 'saved' : 'not saved'), 'saved');
finish();
