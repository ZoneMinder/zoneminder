use strict;
use warnings;
use Test::More;
use HTTP::Response;

# sendCmd retries once. A successful retry must count as success, and a
# failed retry must not send a third request to a doubled http://http:// URL.

require_ok('ZoneMinder::Control::Instar720p');

my (@codes, @urls);
{
  package FakeUA;
  sub new { bless {}, shift }
  sub get {
    my ($self, $url) = @_;
    push @urls, $url;
    my $code = shift @codes // 500;
    return HTTP::Response->new($code, $code == 200 ? 'OK' : 'Error');
  }
}

my $self = bless {
  Monitor  => { ControlAddress => 'http://admin:pw@10.0.0.1:80', ModectDuringPTZ => 1 },
  base_url => 'http://admin:pw@10.0.0.1:80/',
  ua       => FakeUA->new,
}, 'ZoneMinder::Control::Instar720p';

@codes = (500, 200); @urls = ();
ok($self->sendCmd('ptzctrl.cgi?-step=0&-act=stop'), 'a successful retry is success');

@codes = (500, 500); @urls = ();
ok(!$self->sendCmd('ptzctrl.cgi?-step=0&-act=stop'), 'two failures are a failure');
is(scalar(grep { m{^http://http://} } @urls), 0, 'no request goes to a doubled http:// URL');

done_testing();
