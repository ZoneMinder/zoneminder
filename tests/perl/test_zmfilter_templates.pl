#!/usr/bin/perl
#
# zmfilter.pl.in: tag substitution and the summary email.
# The subs are cut out of the template and run against stubs.
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Basename;

my $file = "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

my $failures = 0;
sub check {
  my ($name, $ok, $detail) = @_;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  print "  $detail\n" if !$ok and defined $detail;
  $failures++ if !$ok;
}

package ZF;
use strict;
use File::Basename;
our (%Config, @sent, @frames);
our $logs = 0;
sub Debug {} sub Info {} sub Warning {} sub Error {} sub logDebugging { 0 }
sub zmDbExecute {
  if ($_[0] =~ /SET `Emailed`=1/) { push @ZF::saved, $_[1]; return 1; }
  my @rows = @frames;
  return (bless({rows=>\@rows}, 'ZF::Sth'), 1);
}
sub ZF::Sth::fetchrow_hashref { my $s = shift; return shift @{$s->{rows}}; }
sub sendTheEmail { my ($filter, $subject, $body, @attachments) = @_; push @sent, {subject=>$subject, body=>$body, attachments=>\@attachments}; return 1; }
sub generateVideo { return; }
sub generateImage { return; }

# Event and Monitor stand-ins
package ZF::Event;
sub new { my ($class, $id, $row) = @_; return bless { %{$row||{}}, Id=>$id }, $class; }
sub Id { $_[0]{Id} }
sub Path { $_[0]{Path} }
sub Monitor { return bless {Name=>'Front', Summary=>bless({TotalEvents=>1, HourEvents=>2, DayEvents=>3, WeekEvents=>4, MonthEvents=>5, ArchivedEvents=>6}, 'ZF::Summary')}, 'ZF::Monitor'; }
sub save { my ($s, $h) = @_; $s->{$_} = $h->{$_} for keys %$h; push @ZF::saved, $s->{Id}; return ''; }
sub tags { return ('a', 'b'); }
package ZF::Monitor;
sub Event_Summary { $_[0]{Summary} }
package ZF;
our @saved;

package main;

foreach my $name (qw(is_in_attachments substituteTags sendSummaryEmail)) {
  $src =~ /^(sub \Q$name\E \{.*?^\})/ms or die "Cannot find sub $name";
  eval "package ZF; no warnings; $1; 1" or die $@;
}
{ no warnings 'once'; *ZF::fileparse = \&File::Basename::fileparse; *ZF::basename = \&File::Basename::basename; }
{ no warnings 'once'; @ZoneMinder::Event::ISA = ('ZF::Event'); }
$ZF::Config{ZM_URL} = 'http://zm/index.php';
$ZF::Config{ZM_FROM_EMAIL} = 'zm@example.com';
$INC{'URI/Encode.pm'} = 1;
{ no warnings 'once'; *URI::Encode::uri_encode = sub { return $_[0] }; }

my $filter = { Id=>7, Name=>'my filter', EmailTo=>'a@example.com', EmailSubject=>'S %FN%' };
my $dir = File::Temp::tempdir(CLEANUP=>1);

sub event {
  my ($id, %extra) = @_;
  mkdir "$dir/$id";
  return ZF::Event->new($id, { MonitorId=>1, Path=>"$dir/$id", %extra });
}

# %MEM% alone needs the event summary
{
  my $text = ZF::substituteTags('month=%MEM%', $filter, event(1));
  check('%MEM% alone is substituted', $text eq 'month=5', $text);
  $text = ZF::substituteTags('month=%MEM% day=%MED%', $filter, event(1));
  check('%MEM% with %MED% is substituted', $text eq 'month=5 day=3', $text);
}

# Frame links without any image tag
{
  @ZF::frames = ({FrameId=>3, Score=>10}, {FrameId=>4, Score=>50});
  my $text = ZF::substituteTags('first=%EPF1% max=%EPFM%', $filter, event(1));
  check('%EPF1% and %EPFM% link to the alarm frames',
    $text eq 'first=http://zm/index.php?view=frame&mid=1&eid=1&fid=3 max=http://zm/index.php?view=frame&mid=1&eid=1&fid=4', $text);
}

# %FP% opens the filter that matched; the filter view only takes an Id
{
  my $text = ZF::substituteTags('%FP%', $filter);
  check('%FP% links to the filter by its Id', $text eq 'http://zm/index.php?view=filter&Id=7', $text);
}

# Summary email
{
  my $body_tpl = "<html>head %FN%\n%SUMMARY%<p>%EN% %EI%</p>\n%SUMMARY%tail %FN% %FP%</html>";
  $filter->{EmailBody} = $body_tpl;
  my @rows = (
    { Id=>1, MonitorId=>1, Name=>'one', Emailed=>0 },
    { Id=>2, MonitorId=>1, Name=>'two', Emailed=>1 },
    { Id=>3, MonitorId=>1, Name=>'three', Emailed=>0 },
  );
  @ZF::sent = (); @ZF::saved = ();
  my $res = ZF::sendSummaryEmail($filter, @rows);
  check('summary email sent', $res && @ZF::sent == 1);
  my $body = $ZF::sent[0]{body} // '';
  check('events already emailed are left out', ($body !~ /two/ && $body =~ /one/ && $body =~ /three/) ? 1 : 0, $body);
  check('events already emailed are not saved again', "@ZF::saved" eq '1 3', "@ZF::saved");
  check('text after the summary part gets its tags', scalar($body =~ /tail my filter http:\/\/zm\/index\.php\?view=filter&Id=7/), $body);
  check('no tag is left in the body', scalar($body !~ /%[A-Z0-9]+%/), $body);

  @ZF::sent = ();
  $res = ZF::sendSummaryEmail($filter, { Id=>2, MonitorId=>1, Name=>'two', Emailed=>1 });
  check('nothing is sent when every event was emailed', !$res && !@ZF::sent);
}

# Images of different events must not share a Content-ID
{
  @ZF::frames = ({FrameId=>1, Score=>10});
  my %paths;
  no warnings 'redefine';
  local *ZF::generateImage = sub {
    my ($Event, $frame) = @_;
    my $p = $Event->Path().'/00001-capture.jpg';
    open(my $f, '>', $p) or die; print $f 'x'; close $f;
    return $p;
  };
  my @attachments;
  my $one = ZF::substituteTags('<html>%EI1%</html>', $filter, event(11), \@attachments, {html=>1});
  my $two = ZF::substituteTags('<html>%EI1%</html>', $filter, event(12), \@attachments, {html=>1});
  my ($c1) = $one =~ /cid:([^"]+)/;
  my ($c2) = $two =~ /cid:([^"]+)/;
  check('two attachments', @attachments == 2);
  check('Content-ID differs between events', defined $c1 && defined $c2 && $c1 ne $c2, "$c1 / $c2");
}

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
