use strict;
use warnings;
use Test::More;
use HTTP::Response;

# zmcontrol treats a false open() as "Failed to open control" (fatal in
# --protocol mode), so open() must return true when the camera answered
# and false when it didn't.

my @classes = qw(ZoneMinder::Control::Instar720p ZoneMinder::Control::PSIA);
require_ok($_) for @classes;

my @replies;
{
  no warnings qw(redefine once);
  *LWP::UserAgent::request = sub { my $r = shift @replies; return $r };
  *LWP::UserAgent::get = sub { my $r = shift @replies; return $r };
  *ZoneMinder::Control::loadMonitor = sub { 1 };
}

sub reply {
  my ($code, %h) = @_;
  my $r = HTTP::Response->new($code, $code == 200 ? 'OK' : $code == 401 ? 'Unauthorized' : 'Error');
  $r->header(%h) if %h;
  return $r;
}

for my $class (@classes) {
  my $new = sub {
    bless { Monitor => { Id => 1, ControlAddress => 'user:pass@camera', ControlDevice => 'cam' } }, $class;
  };

  @replies = (reply(200));
  ok($new->()->open(), "$class open() is true when the camera answers");

  @replies = (reply(500), reply(500));
  ok(!$new->()->open(), "$class open() is false when the camera fails");

  @replies = (reply(401, 'WWW-Authenticate' => 'Digest realm="other"'), reply(200));
  ok($new->()->open(), "$class open() is true after switching realm");

  @replies = (reply(401, 'WWW-Authenticate' => 'Digest realm="other"'), reply(401));
  ok(!$new->()->open(), "$class open() is false when the new realm also fails");
}

done_testing();
