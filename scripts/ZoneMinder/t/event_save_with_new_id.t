use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";

# When zmc cannot insert an event's row it records the event under a time-based
# fallback id (EventFallbackId in src/zm_event.h). zmrecover.pl then saves it
# with save_with_new_id(), which inserts the row under a normal auto increment
# id and moves the event's directory, id marker, video, m3u8 and any rows zmc
# queued under the old id.
#
# The filesystem is real; save() and zmDbDo are recorded instead of run.

my $old_id = 1795517294000001;  # EventFallbackId(1795517294000, 1)
my $new_id = 1234;

my (@saves, @sql, $save_error);
{
  no warnings qw(redefine once);
  *ZoneMinder::Event::save = sub {
    my ($self, $data, $force_insert) = @_;
    push @saves, { id => $$self{Id}, force_insert => $force_insert, name => $$self{Name} };
    return $save_error if $save_error;
    $$self{Id} = $new_id if !$$self{Id};
    return '';
  };
  *ZoneMinder::Event::zmDbDo = sub { push @sql, [@_]; return 1 };
  *ZoneMinder::Event::Info = sub {};
  *ZoneMinder::Event::Warning = sub {};
  *ZoneMinder::Event::Error = sub {};
}

sub slurp { open(my $fh, '<', $_[0]) or return undef; local $/; return <$fh> }
sub spew { open(my $fh, '>', $_[0]) or die "$_[0]: $!"; print $fh $_[1]; close($fh) }

sub new_event {
  my %args = @_;
  @saves = ();
  @sql = ();
  $save_error = undef;
  my $event = ZoneMinder::Event->new();
  $$event{Id} = $old_id;
  $$event{Name} = "Event $old_id recovered";
  $$event{MonitorId} = 1;
  $$event{$_} = $args{$_} foreach keys %args;
  return $event;
}

# A video and an m3u8 as zmc leaves them once the event has closed
sub fill_event_dir {
  my $dir = shift;
  spew("$dir/$old_id-video.h264.mp4", 'video');
  spew("$dir/00001-capture.jpg", 'jpeg');
  spew("$dir/index.m3u8", "#EXTM3U\n#EXT-X-MAP:URI=\"index.php?view=view_video&eid=$old_id&file=$old_id-video.h264.mp4\"\n");
}

sub check_moved {
  my ($label, $event, $dir) = @_;
  is($$event{Id}, $new_id, "$label: event has the new id");
  is($$event{Path}, $dir, "$label: path follows the new id");
  ok(-e "$dir/$new_id-video.h264.mp4" && !-e "$dir/$old_id-video.h264.mp4", "$label: video renamed");
  ok(-e "$dir/00001-capture.jpg", "$label: other files untouched");
  my $m3u8 = slurp("$dir/index.m3u8") // '';
  like($m3u8, qr/eid=$new_id&file=$new_id-video\.h264\.mp4/, "$label: m3u8 points at the new id and video");
  unlike($m3u8, qr/$old_id/, "$label: m3u8 no longer mentions the old id");
  is($$event{DefaultVideo}, "$new_id-video.h264.mp4", "$label: DefaultVideo renamed");
  is($$event{Name}, "Event $new_id recovered", "$label: name follows the new id");
  ok($saves[0]{force_insert} && !defined $saves[0]{id}, "$label: row inserted without an id");
  ok(@saves == 2 && $saves[1]{id} == $new_id, "$label: row saved again with the new name and video");
  my %moved = map { $$_[0] =~ /UPDATE `(\w+)` SET EventId=\? WHERE EventId=\?/ && $$_[1] == $new_id && $$_[2] == $old_id ? ($1 => 1) : () } @sql;
  ok((grep { $moved{$_} } qw(Frames Stats Event_Data Events_Tags Snapshots_Events Events_Hour Events_Day Events_Week Events_Month Events_Archived)) == 10,
    "$label: rows queued under the old id move to the new one");
}

ok(ZoneMinder::Event::is_fallback_id($old_id), 'a time-based fallback id is recognised');
ok(!ZoneMinder::Event::is_fallback_id(123456), 'an auto increment id is not');
ok(!ZoneMinder::Event::is_fallback_id(undef) && !ZoneMinder::Event::is_fallback_id('') && !ZoneMinder::Event::is_fallback_id('1795517294000001x'),
  'nothing that is not a plain number is');

{
  my $root = tempdir(CLEANUP => 1);
  make_path("$root/1/$old_id");
  fill_event_dir("$root/1/$old_id");
  spew("$root/1/$old_id/.$old_id", '');
  my $event = new_event(Scheme => 'Shallow', Path => "$root/1/$old_id", DefaultVideo => "$old_id-video.h264.mp4");
  is($event->save_with_new_id(), '', 'shallow: no error');
  check_moved('shallow', $event, "$root/1/$new_id");
  ok(!-e "$root/1/$old_id", 'shallow: old directory gone');
  ok(-e "$root/1/$new_id/.$new_id" && !-e "$root/1/$new_id/.$old_id", 'shallow: id file renamed');
}

{
  my $root = tempdir(CLEANUP => 1);
  make_path("$root/1/2026-11-26/$old_id");
  fill_event_dir("$root/1/2026-11-26/$old_id");
  my $event = new_event(Scheme => 'Medium', Path => "$root/1/2026-11-26/$old_id",
    RelativePath => "1/2026-11-26/$old_id", DefaultVideo => "$old_id-video.h264.mp4");
  is($event->save_with_new_id(), '', 'medium: no error');
  check_moved('medium', $event, "$root/1/2026-11-26/$new_id");
  is($$event{RelativePath}, "1/2026-11-26/$new_id", 'medium: relative path follows the new id');
}

{
  my $root = tempdir(CLEANUP => 1);
  make_path("$root/1/26/11/26/10/00/00");
  fill_event_dir("$root/1/26/11/26/10/00/00");
  symlink('10/00/00', "$root/1/26/11/26/.$old_id") or die "symlink: $!";
  my $event = new_event(Scheme => 'Deep', Path => "$root/1/26/11/26/10/00/00",
    RelativePath => '1/26/11/26/10/00/00', DefaultVideo => "$old_id-video.h264.mp4");
  is($event->save_with_new_id(), '', 'deep: no error');
  check_moved('deep', $event, "$root/1/26/11/26/10/00/00");
  ok(!-l "$root/1/26/11/26/.$old_id", 'deep: old id link gone');
  is(readlink("$root/1/26/11/26/.$new_id"), '10/00/00', 'deep: new id link points at the same time directory');
}

{
  # The directory cannot be moved: drop the new row and keep the old id, so a
  # later run can try again and zmaudit still recognises the event.
  my $root = tempdir(CLEANUP => 1);
  my $event = new_event(Scheme => 'Shallow', Path => "$root/1/$old_id");
  like($event->save_with_new_id(), qr/Failed renaming/, 'rename failure: reported');
  is($$event{Id}, $old_id, 'rename failure: event keeps its fallback id');
  ok((grep { $$_[0] eq 'DELETE FROM Events WHERE Id=?' && $$_[1] == $new_id } @sql), 'rename failure: new row removed');
  ok(!(grep { $$_[0] =~ /^UPDATE/ } @sql), 'rename failure: no rows moved');
}

{
  my $root = tempdir(CLEANUP => 1);
  make_path("$root/1/$old_id");
  my $event = new_event(Scheme => 'Shallow', Path => "$root/1/$old_id");
  $save_error = 'mock insert failure';
  is($event->save_with_new_id(), 'mock insert failure', 'insert failure: reported');
  is($$event{Id}, $old_id, 'insert failure: event keeps its fallback id');
  ok(-d "$root/1/$old_id", 'insert failure: directory left alone');
  ok(!@sql, 'insert failure: nothing else touched');
}

done_testing();
