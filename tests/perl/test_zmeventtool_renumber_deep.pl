#!/usr/bin/perl
#
# zmeventtool.pl renumber gives an event a new Id by inserting a copy of its
# row and renaming its directory to the new Id's path. The path of an event in
# Deep storage does not depend on the Id, so such an event must be left alone:
# no second row for the same directory, whose deletion would remove the files
# of both.
#
# Runs the whole script under -T with the real ZoneMinder::Event and
# ZoneMinder::Object on an SQLite database; the other ZoneMinder modules and
# the storage area are stubbed.
#
# Run as: perl tests/perl/test_zmeventtool_renumber_deep.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use DBI;

if (!eval { require DBD::SQLite; 1 }) {
  print "skipped: DBD::SQLite is not installed\n";
  exit(0);
}

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

my $root = "$FindBin::Bin/../..";
my $dir = tempdir(CLEANUP => 1);
my $db = "$dir/zm.db";
my $storage = "$dir/events";
make_path("$dir/lib/ZoneMinder", "$dir/lib/Date", "$dir/lib/Number/Bytes", $storage);

write_file("$dir/lib/ZoneMinder/Config.pm", <<'EOM');
package ZoneMinder::Config;
our %Config = (ZM_WEB_USER => 'root', ZM_DB_USER => 'u', ZM_DB_PASS => 'p');
sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
1;
EOM
write_file("$dir/lib/ZoneMinder/Base.pm", "package ZoneMinder::Base;\nour \$VERSION = '0';\nuse constant ZM_VERSION => '0';\n1;\n");
write_file("$dir/lib/ZoneMinder/Logger.pm", <<'EOM');
package ZoneMinder::Logger;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit logSetSignal Debug Info DEBUG NOLOG);
  *{$caller.'::'.$_} = sub { print STDERR "@_\n"; 1 } for qw(Warning Error Fatal);
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Database.pm", <<"EOM");
package ZoneMinder::Database;
use DBI;
our \$dbh;
sub zmDbConnect {
  \$dbh ||= DBI->connect('dbi:SQLite:dbname=$db', '', '', { PrintError => 0 });
  return \$dbh;
}
sub zmDbDisconnect { 1 }
sub zmDbDo { my \$sql = shift; return \$dbh->do(\$sql, undef, \@_); }
sub start_transaction { 0 }
sub end_transaction { 1 }
sub import {
  no strict 'refs';
  my \$caller = caller();
  *{\$caller.'::'.\$_} = \\&{\$_} for qw(zmDbConnect zmDbDisconnect zmDbDo);
}
1;
EOM
write_file("$dir/lib/ZoneMinder/Storage.pm", <<"EOM");
package ZoneMinder::Storage;
use File::Path ();
sub new { return bless { Id => 1 }, shift; }
sub Id { \$_[0]{Id} }
sub Path { '$storage' }
sub DoDelete { 1 }
sub Type { 'local' }
sub adjust_diskspace { 1 }
sub delete_path { File::Path::remove_tree('$storage/'.\$_[1]); }
1;
EOM
foreach my $module (qw(Frame Monitor Event_Tag Tag)) {
  write_file("$dir/lib/ZoneMinder/$module.pm", "package ZoneMinder::$module;\n1;\n");
}
write_file("$dir/lib/Date/Manip.pm", "package Date::Manip;\n1;\n");
write_file("$dir/lib/Number/Bytes/Human.pm", "package Number::Bytes::Human;\n1;\n");

my $source = do {
  open(my $fh, '<', "$root/scripts/zmeventtool.pl.in") or die "Can't open zmeventtool.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmeventtool.pl", $source);

# The Events columns, from ZoneMinder::Event
my $event_pm = do {
  open(my $fh, '<', "$root/scripts/ZoneMinder/lib/ZoneMinder/Event.pm") or die "Event.pm: $!";
  local $/;
  <$fh>;
};
my ($fields) = $event_pm =~ /%fields = map \{ \$_, \$_ \} qw\((.*?)\);/s;
my %seen;
my @columns = grep { $_ ne 'Id' and !$seen{$_}++ } split(' ', $fields);

my $dbh = DBI->connect("dbi:SQLite:dbname=$db", '', '', { RaiseError => 1 });
$dbh->do('CREATE TABLE Events (Id INTEGER PRIMARY KEY AUTOINCREMENT, '.join(', ', map { "`$_`" } @columns).')');
$dbh->do("CREATE TABLE $_ (EventId INTEGER)") for qw(Frames Stats Event_Data Events_Tags Snapshots_Events);

# The event, its directory and, for Deep storage, the .<Id> link to it
sub add_event {
  my ($id, $scheme, $relative_path, $link) = @_;
  $dbh->do('INSERT INTO Events (Id, MonitorId, StorageId, Name, StartDateTime, Scheme) VALUES (?, 1, 1, ?, ?, ?)',
    undef, $id, "Event $id", '2026-11-01 10:00:00', $scheme);
  $dbh->do('INSERT INTO Frames (EventId) VALUES (?)', undef, $id);
  make_path("$storage/$relative_path");
  write_file("$storage/$relative_path/video.mp4", 'video');
  symlink($link->[1], "$storage/$link->[0]") if $link;
}

sub run {
  my $out = qx($^X -T -I$dir/lib -I$root/scripts/ZoneMinder/lib $dir/zmeventtool.pl @_ 2>&1);
  return ($? >> 8, $out);
}

sub ids {
  my ($table, $column) = @_;
  return join(',', map { $$_[0] } @{$dbh->selectall_arrayref("SELECT $column FROM $table ORDER BY $column")});
}

add_event(7, 'Deep', '1/26/11/01/10/00/00', ['1/26/11/01/.7', '10/00/00']);
my ($status, $out) = run('renumber', 7);
check('renumbering a Deep event adds no Events row', ids('Events', 'Id'), '7');
check('renumbering a Deep event keeps its frames', ids('Frames', 'EventId'), '7');
check('renumbering a Deep event keeps its files', -f "$storage/1/26/11/01/10/00/00/video.mp4" ? 'kept' : 'gone', 'kept');
check('renumbering a Deep event says why it is skipped', $out =~ /Deep/ ? 'said' : "silent: $out", 'said');

# Other schemes are still renumbered: new row, frames and files moved, old row gone
add_event(20, 'Shallow', '1/20');
($status, $out) = run('renumber', 20);
my ($new_id) = $dbh->selectrow_array(q{SELECT Id FROM Events WHERE Name = 'Event 20'});
check('a Shallow event gets a new Id', ($new_id // 0) > 20 ? 'renumbered' : "not renumbered: $out", 'renumbered');
check('a Shallow event\'s frames move to the new Id',
  join(',', $dbh->selectrow_array('SELECT EventId FROM Frames WHERE EventId IN (20, ?)', undef, $new_id // 0)), $new_id // '');
check('a Shallow event\'s files move to the new Id', -f "$storage/1/$new_id/video.mp4" ? 'moved' : 'not moved', 'moved');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
