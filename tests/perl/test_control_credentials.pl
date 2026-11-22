#!/usr/bin/perl
#
# ZoneMinder::Control parse_ControlAddress and parse_Path with the address
# shapes the control modules are given: with and without a scheme, port,
# user or password; @, /, :, #, ? and % in the password, raw and %-encoded;
# non-ASCII credentials, raw, %-encoded and as the database returns them;
# IPv6 and IDN hosts; a path or query after the host, with or without an @.
#
# For each it checks the host, port, username and password the drivers get,
# the base url, the credentials given to LWP, and the login LWP takes from
# the base url, which has to be the same UTF-8 bytes. It also checks the
# realm retry in get_realm and the logins of Reolink_HTTP and Dahua_RPC.
#
# Run as: perl tests/perl/test_control_credentials.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use URI;
use URI::Escape;
use HTTP::Response;
use ZoneMinder::Control;
use ZoneMinder::Control::Reolink_HTTP;
use ZoneMinder::Control::Dahua_RPC;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

package FakeUA;
sub new { return bless {creds => []}, shift; }
sub credentials { my $self = shift; push @{$self->{creds}}, [@_]; }

# Answers the login posts of the JSON drivers and keeps what they sent
package FakeLoginUA;
sub new { my ($class, @answers) = @_; return bless {answers => [@answers], sent => []}, $class; }
sub credentials {}
sub agent {}
sub timeout {}
sub post {
  my ($self, $url, %args) = @_;
  push @{$self->{sent}}, $args{Content};
  my $res = HTTP::Response->new(200);
  $res->content(shift(@{$self->{answers}}) // '{}');
  return $res;
}
sub request { my ($self, $req) = @_; return $self->post($req->uri, Content => $req->content); }

package main;

# Printable form, so the expected values below stay ASCII.
sub show {
  my $v = shift;
  return '(undef)' if !defined $v;
  $v =~ s/([^\x20-\x7e])/sprintf('\\x{%x}', ord($1))/ge;
  return $v;
}

sub summary {
  my $c = shift;
  my $cred = $c->{ua}{creds}[0];
  return join(' | ',
    'host='.show($c->{host}),
    'port='.show($c->{port}),
    'user='.show($c->{username}),
    'pass='.show($c->{password}),
    'base='.show(defined $c->{BaseURL} ? "$c->{BaseURL}" : undef),
    'cred='.($cred ? join(',', map { show($_) } @$cred[0, 2, 3]) : '-'),
  );
}

# The credentials sent are the user and password unless the row gives the
# bytes separately.
sub want {
  my ($host, $port, $user, $pass, $base, @sent) = @_;
  @sent = ($user, $pass) if !@sent;
  my $hp = ($host =~ /:/ ? "[$host]" : $host).':'.$port;
  return join(' | ',
    'host='.show($host), 'port='.show($port), 'user='.show($user),
    'pass='.show($pass), 'base='.show($base),
    'cred='.join(',', show($hp), show($sent[0]), show($sent[1])),
  );
}

# LWP::Protocol::http sends the user info of a request URL as Basic auth: it
# takes the authority up to the first @, splits it on the first : and
# url-decodes both halves. The rest of the authority becomes the Host header.
sub wire {
  my $c = shift;
  my $authority = URI->new("$c->{BaseURL}")->authority;
  return 'no userinfo' if $authority !~ s/^([^\@]*)\@//;
  my ($user, $pass) = map { URI::Escape::uri_unescape($_) } split(':', $1, 2);
  return join(' | ', 'user='.show($user), 'pass='.show($pass // ''), 'host='.$authority);
}

sub wire_want {
  my (undef, undef, $user, $pass, $base, @sent) = @_;
  ($user, $pass) = @sent if @sent;
  (my $host = URI->new($base)->authority) =~ s/^.*\@//;
  return join(' | ', 'user='.show($user), 'pass='.show($pass // ''), 'host='.$host);
}

# A string as DBD::mysql with mysql_enable_utf8mb4 or DBD::MariaDB returns it
sub from_db {
  my $s = shift;
  utf8::upgrade($s);
  return $s;
}

sub control_with {
  return bless {ua => FakeUA->new, Monitor => {@_}}, 'ZoneMinder::Control';
}

# [ControlAddress, host, port, user, password, base url, bytes sent for the user and password]
my @control_address = (
  ['cam', 'cam', 80, undef, undef, 'http://cam/'],
  ['cam:8080', 'cam', 8080, undef, undef, 'http://cam:8080/'],
  ['http://cam', 'cam', 80, undef, undef, 'http://cam/'],
  ['HTTP://cam', 'cam', 80, undef, undef, 'http://cam/'],
  ['HTTP://admin:pw@cam:8080/x', 'cam', 8080, 'admin', 'pw', 'http://admin:pw@cam:8080/x'],
  ['https://cam', 'cam', 443, undef, undef, 'https://cam/'],
  ['https://cam:8443', 'cam', 8443, undef, undef, 'https://cam:8443/'],
  ['rtsp://cam', 'cam', 554, undef, undef, 'rtsp://cam/'],
  ['admin:pw@cam', 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/'],
  ['admin:pw@cam:8080', 'cam', 8080, 'admin', 'pw', 'http://admin:pw@cam:8080/'],
  ['https://admin:pw@cam', 'cam', 443, 'admin', 'pw', 'https://admin:pw@cam/'],
  ['admin@cam', 'cam', 80, 'admin', '', 'http://admin:@cam/'],
  ['admin:@cam', 'cam', 80, 'admin', '', 'http://admin:@cam/'],
  [':pw@cam', 'cam', 80, '', 'pw', 'http://:pw@cam/'],
  ['@cam', 'cam', 80, undef, undef, 'http://cam/'],
  [':@cam', 'cam', 80, undef, undef, 'http://cam/'],
  ['admin:pa@ss@cam', 'cam', 80, 'admin', 'pa@ss', 'http://admin:pa%40ss@cam/'],
  ['admin:pa/ss@cam', 'cam', 80, 'admin', 'pa/ss', 'http://admin:pa%2Fss@cam/'],
  ['admin:pa:ss@cam', 'cam', 80, 'admin', 'pa:ss', 'http://admin:pa%3Ass@cam/'],
  ['admin:pa#ss@cam', 'cam', 80, 'admin', 'pa#ss', 'http://admin:pa%23ss@cam/'],
  ['admin:pa?ss@cam', 'cam', 80, 'admin', 'pa?ss', 'http://admin:pa%3Fss@cam/'],
  ['admin:2024?Pass@192.168.1.5', '192.168.1.5', 80, 'admin', '2024?Pass', 'http://admin:2024%3FPass@192.168.1.5/'],
  ['admin:1234?5@cam', 'cam', 80, 'admin', '1234?5', 'http://admin:1234%3F5@cam/'],
  ['admin:123?Abc@cam:8080', 'cam', 8080, 'admin', '123?Abc', 'http://admin:123%3FAbc@cam:8080/'],
  ['admin:100%@cam', 'cam', 80, 'admin', '100%', 'http://admin:100%25@cam/'],
  ['admin:a%zz@cam', 'cam', 80, 'admin', 'a%zz', 'http://admin:a%25zz@cam/'],
  ['admin:pa ss@cam', 'cam', 80, 'admin', 'pa ss', 'http://admin:pa%20ss@cam/'],
  ['admin:p/a@ss@cam:8080/onvif', 'cam', 8080, 'admin', 'p/a@ss', 'http://admin:p%2Fa%40ss@cam:8080/onvif'],
  ['admin:pa%40ss@cam', 'cam', 80, 'admin', 'pa@ss', 'http://admin:pa%40ss@cam/'],
  ['admin:pa%2Fss@cam', 'cam', 80, 'admin', 'pa/ss', 'http://admin:pa%2Fss@cam/'],
  ['admin:pa%3Ass@cam', 'cam', 80, 'admin', 'pa:ss', 'http://admin:pa%3Ass@cam/'],
  ['admin:pa%23ss@cam', 'cam', 80, 'admin', 'pa#ss', 'http://admin:pa%23ss@cam/'],
  ['admin:pa%3Fss@cam', 'cam', 80, 'admin', 'pa?ss', 'http://admin:pa%3Fss@cam/'],
  ['admin:100%25@cam', 'cam', 80, 'admin', '100%', 'http://admin:100%25@cam/'],
  ['ad#min:pw@cam', 'cam', 80, 'ad#min', 'pw', 'http://ad%23min:pw@cam/'],
  ['me@example.com:pw@cam', 'cam', 80, 'me@example.com', 'pw', 'http://me%40example.com:pw@cam/'],
  ['me%40example.com:pw@cam:8080', 'cam', 8080, 'me@example.com', 'pw', 'http://me%40example.com:pw@cam:8080/'],
  ["admin:p\x{e4}ss\@cam", 'cam', 80, 'admin', "p\x{e4}ss", 'http://admin:p%C3%A4ss@cam/', 'admin', "p\xc3\xa4ss"],
  ['admin:p%C3%A4ss@cam', 'cam', 80, 'admin', "p\xc3\xa4ss", 'http://admin:p%C3%A4ss@cam/'],
  ['admin:p%E4ss@cam', 'cam', 80, 'admin', "p\xe4ss", 'http://admin:p%E4ss@cam/'],
  ["admin:\x{43f}\x{430}\x{440}\@cam", 'cam', 80, 'admin', "\x{43f}\x{430}\x{440}", 'http://admin:%D0%BF%D0%B0%D1%80@cam/', 'admin', "\xd0\xbf\xd0\xb0\xd1\x80"],
  ['admin:%D0%BF%D0%B0%D1%80@cam', 'cam', 80, 'admin', "\xd0\xbf\xd0\xb0\xd1\x80", 'http://admin:%D0%BF%D0%B0%D1%80@cam/'],
  ["\x{e4}dmin:pw\@cam", 'cam', 80, "\x{e4}dmin", 'pw', 'http://%C3%A4dmin:pw@cam/', "\xc3\xa4dmin", 'pw'],
  [from_db("\x{e4}dmin:pw\@cam"), 'cam', 80, "\xc3\xa4dmin", 'pw', 'http://%C3%A4dmin:pw@cam/'],
  [from_db("\x{43f}\x{430}:pw\@cam"), 'cam', 80, "\xd0\xbf\xd0\xb0", 'pw', 'http://%D0%BF%D0%B0:pw@cam/'],
  [from_db("admin:p\x{e4}ss\@cam"), 'cam', 80, 'admin', "p\x{e4}ss", 'http://admin:p%C3%A4ss@cam/', 'admin', "p\xc3\xa4ss"],
  [from_db("admin:pw\@k\x{e4}m:8080"), 'xn--km-via', 8080, 'admin', 'pw', 'http://admin:pw@xn--km-via:8080/'],
  ["admin:pw\@k\x{e4}m:8080/a\@b", 'xn--km-via', 8080, 'admin', 'pw', 'http://admin:pw@xn--km-via:8080/a@b'],
  [from_db("admin:pw\@k\x{e4}m:8080/a\@b"), 'xn--km-via', 8080, 'admin', 'pw', 'http://admin:pw@xn--km-via:8080/a@b'],
  ["k\x{e4}m/cgi-bin/ptz.cgi?email=a\@b", 'xn--km-via', 80, undef, undef, 'http://xn--km-via/cgi-bin/ptz.cgi?email=a@b'],
  [from_db("k\x{e4}m/cgi-bin/ptz.cgi?email=a\@b"), 'xn--km-via', 80, undef, undef, 'http://xn--km-via/cgi-bin/ptz.cgi?email=a@b'],
  ['[::1]:8080', '::1', 8080, undef, undef, 'http://[::1]:8080/'],
  ['admin:pw@[::1]:8080', '::1', 8080, 'admin', 'pw', 'http://admin:pw@[::1]:8080/'],
  ['http://[fe80::1]', 'fe80::1', 80, undef, undef, 'http://[fe80::1]/'],
  ['admin:pw@cam:8080/onvif/device_service', 'cam', 8080, 'admin', 'pw', 'http://admin:pw@cam:8080/onvif/device_service'],
  ['cam/cgi-bin/x?a=1', 'cam', 80, undef, undef, 'http://cam/cgi-bin/x?a=1'],
  ['cam:8080/a#b', 'cam', 8080, undef, undef, 'http://cam:8080/a#b'],
  ['admin:pw@cam/a@b', 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/a@b'],
  ['admin:pw@cam:8080/cgi-bin/ptz.cgi?email=a@b.c', 'cam', 8080, 'admin', 'pw', 'http://admin:pw@cam:8080/cgi-bin/ptz.cgi?email=a@b.c'],
  ['cam/x@y', 'cam', 80, undef, undef, 'http://cam/x@y'],
  ['cam:8080/a@b', 'cam', 8080, undef, undef, 'http://cam:8080/a@b'],
  ['cam?q=a@b', 'cam', 80, undef, undef, 'http://cam?q=a@b'],
  ['cam/@x', 'cam', 80, undef, undef, 'http://cam/@x'],
  ['admin:pw@cam?q=a@b', 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam?q=a@b'],
  ['admin:pw@cam#a@b', 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/#a@b'],
  ['  admin:pw@cam  ', 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/'],
  ["admin:pw\@cam\n", 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/'],
);

foreach my $row (@control_address) {
  my ($address, @want) = @$row;
  my $c = control_with(ControlAddress => $address);
  my $ret = eval { $c->parse_ControlAddress() };
  my $name = 'ControlAddress '.show($address);
  $name .= ' from the database' if utf8::is_utf8($address) and $address !~ /[^\x00-\xff]/;
  check("$name parses", $ret, 1);
  check($name, summary($c), want(@want));
  check("$name sends the same login in the url", wire($c), wire_want(@want)) if defined $want[2];
}

# A : in the user stays raw in the url login; LWP sends the same Basic header
foreach my $row ([ControlAddress => 'a%3Ab:c@cam'], [Path => 'rtsp://cam/s', User => 'a:b', Pass => 'c']) {
  my $c = control_with(@$row);
  $row->[0] eq 'Path' ? $c->parse_Path() : $c->parse_ControlAddress();
  check(join(' ', map { show($_) } @$row), summary($c), want('cam', 80, 'a:b', 'c', 'http://a:b:c@cam/'));
  check(join(' ', map { show($_) } @$row).' sends Basic a:b:c', wire($c), 'user=a | pass=b:c | host=cam');
}

# An @ after a host[:port]/ is in the path; host#x@y is read as user info
foreach my $row (
  ['admin:pw@[fe80::1%25eth0]/a@b', 'fe80::1%eth0', 'admin', 'pw', 'http://admin:pw@[fe80::1%25eth0]/a@b'],
  ['cam:/x@y', 'cam:', undef, undef, 'http://cam/x@y'],
  ['cam#a@b', 'b', 'cam#a', '', 'http://cam%23a:@b/'],
) {
  my ($address, @want) = @$row;
  my $c = control_with(ControlAddress => $address);
  $c->parse_ControlAddress();
  check('ControlAddress '.$address,
    join(' | ', map { show($_) } @$c{'host', 'username', 'password'}, "$c->{BaseURL}"),
    join(' | ', map { show($_) } @want));
}

foreach my $address ('', '   ', '0', ' 0 ', 'user:pass@ip', 'user:port@ip') {
  my $c = control_with(ControlAddress => $address);
  check('ControlAddress '.show($address).' is not used', $c->parse_ControlAddress(), 0);
}

# [Path, monitor User, monitor Pass, host, port, user, password, base url, bytes sent for the user and password]
my @path = (
  ['rtsp://cam/s', undef, undef, 'cam', 80, undef, undef, 'http://cam/'],
  ['rtsp://cam:8554/s', undef, undef, 'cam', 80, undef, undef, 'http://cam/'],
  ['http://cam:8080/video', undef, undef, 'cam', 8080, undef, undef, 'http://cam:8080/'],
  ['https://cam/x', undef, undef, 'cam', 443, undef, undef, 'https://cam/'],
  ['  rtsp://admin:pw@cam/s  ', undef, undef, 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/'],
  ['rtsp://[::1]:554/s', 'U', 'P', '::1', 80, 'U', 'P', 'http://U:P@[::1]/'],
  ["rtsp://admin:pw\@k\x{e4}m/s", undef, undef, 'xn--km-via', 80, 'admin', 'pw', 'http://admin:pw@xn--km-via/'],
  ['rtsp://admin:pw@cam/s', 'U', 'P', 'cam', 80, 'admin', 'pw', 'http://admin:pw@cam/'],
  ['rtsp://admin@cam/s', undef, undef, 'cam', 80, 'admin', '', 'http://admin@cam/'],
  ['rtsp://:pw@cam/s', undef, undef, 'cam', 80, '', 'pw', 'http://:pw@cam/'],
  ['rtsp://@cam/s', 'U', 'P', 'cam', 80, 'U', 'P', 'http://U:P@cam/'],
  ['rtsp://admin:pa@ss@cam/s', undef, undef, 'cam', 80, 'admin', 'pa@ss', 'http://admin:pa%40ss@cam/'],
  ['rtsp://admin:pa:ss@cam/s', undef, undef, 'cam', 80, 'admin', 'pa:ss', 'http://admin:pa%3Ass@cam/'],
  ['rtsp://admin:pa%40ss@cam/s', undef, undef, 'cam', 80, 'admin', 'pa@ss', 'http://admin:pa%40ss@cam/'],
  ['rtsp://admin:pa%2Fss@cam/s', undef, undef, 'cam', 80, 'admin', 'pa/ss', 'http://admin:pa%2Fss@cam/'],
  ['rtsp://admin:pa%23ss@cam/s', undef, undef, 'cam', 80, 'admin', 'pa#ss', 'http://admin:pa%23ss@cam/'],
  ['rtsp://admin:pa%3Fss@cam/s', undef, undef, 'cam', 80, 'admin', 'pa?ss', 'http://admin:pa%3Fss@cam/'],
  ['rtsp://admin:100%25@cam/s', undef, undef, 'cam', 80, 'admin', '100%', 'http://admin:100%25@cam/'],
  ["rtsp://admin:p\x{e4}ss\@cam/s", undef, undef, 'cam', 80, 'admin', "p\x{e4}ss", 'http://admin:p%C3%A4ss@cam/', 'admin', "p\xc3\xa4ss"],
  ['rtsp://admin:p%C3%A4ss@cam/s', undef, undef, 'cam', 80, 'admin', "p\xc3\xa4ss", 'http://admin:p%C3%A4ss@cam/'],
  ["rtsp://admin:\x{43f}\x{430}\x{440}\@cam/s", undef, undef, 'cam', 80, 'admin', "\xd0\xbf\xd0\xb0\xd1\x80", 'http://admin:%D0%BF%D0%B0%D1%80@cam/'],
  ['rtsp://cam/s', 'U', 'P@s', 'cam', 80, 'U', 'P@s', 'http://U:P%40s@cam/'],
  ['rtsp://cam/s', 'U', 'a/b', 'cam', 80, 'U', 'a/b', 'http://U:a%2Fb@cam/'],
  ['rtsp://cam/s', 'U', 'a?b', 'cam', 80, 'U', 'a?b', 'http://U:a%3Fb@cam/'],
  ['rtsp://cam/s', 'U', 'a#b', 'cam', 80, 'U', 'a#b', 'http://U:a%23b@cam/'],
  ['rtsp://cam/s', 'U', 'a:b', 'cam', 80, 'U', 'a:b', 'http://U:a%3Ab@cam/'],
  ['rtsp://cam/s', 'U', 'a%41b', 'cam', 80, 'U', 'a%41b', 'http://U:a%2541b@cam/'],
  ['rtsp://cam/s', 'U', '', 'cam', 80, 'U', '', 'http://U:@cam/'],
  ['rtsp://cam/s', 'U', undef, 'cam', 80, 'U', undef, 'http://U:@cam/'],
  ['rtsp://cam/s', "\x{e4}", 'p', 'cam', 80, "\x{e4}", 'p', 'http://%C3%A4:p@cam/', "\xc3\xa4", 'p'],
  ['rtsp://cam/s', 'U', "\x{43f}\x{430}\x{440}", 'cam', 80, 'U', "\x{43f}\x{430}\x{440}", 'http://U:%D0%BF%D0%B0%D1%80@cam/', 'U', "\xd0\xbf\xd0\xb0\xd1\x80"],
);

foreach my $row (@path) {
  my ($path, $user, $pass, @want) = @$row;
  my $c = control_with(Path => $path, User => $user, Pass => $pass);
  my $ret = eval { $c->parse_Path() };
  my $name = 'Path '.show($path).' User '.show($user).' Pass '.show($pass);
  check("$name parses", $ret, 1);
  check($name, summary($c), want(@want));
  check("$name sends the same login in the url", wire($c), wire_want(@want)) if defined $want[2] and length $want[2];
}

my $c = control_with(Path => 'rtsp://cam/s', User => '', Pass => 'P');
$c->parse_Path();
check('an empty monitor User leaves the base url without a login', "$c->{BaseURL}", 'http://cam/');

$c = control_with(Path => 'cam/s');
check('a Path without a scheme is not used', $c->parse_Path(), 0);

# The JSON logins of two drivers that take the credentials from the helpers.
# They pin what the drivers get from the helpers.
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Reolink_HTTP::loadMonitor = sub {};
  *ZoneMinder::Control::Dahua_RPC::loadMonitor = sub {};
}
my @login_answers;
{
  no warnings qw(redefine once);
  *LWP::UserAgent::new = sub { return FakeLoginUA->new(@login_answers); };
}
@login_answers = ('[{"code":0,"value":{"Token":{"name":"t","leaseTime":3600}}}]');
my $reolink = bless {Monitor => {ControlAddress => "admin:p\x{e4}ss\@cam"}}, 'ZoneMinder::Control::Reolink_HTTP';
$reolink->open();
my ($sent) = ($reolink->{ua}{sent}[0] // '') =~ /"password":"([^"]*)"/;
check('Reolink_HTTP login with a raw non-ASCII password sends it as UTF-8', show($sent), show("p\xc3\xa4ss"));

# The Dahua login hash is taken over the password bytes, UTF-8 when %-encoded
@login_answers = ('{"params":{"realm":"r","random":"1"},"session":"s"}', '{"result":true}');
my $dahua = bless {Monitor => {ControlAddress => 'admin:p%C3%A4ss@cam'}}, 'ZoneMinder::Control::Dahua_RPC';
$dahua->open();
($sent) = ($dahua->{ua}{sent}[1] // '') =~ /"password":"([^"]*)"/;
check('Dahua_RPC login hash for a %-encoded UTF-8 password',
  $sent, ZoneMinder::Control::Dahua_RPC::compute_login_hash('admin', 'r', '1', "p\xc3\xa4ss"));

# get_realm sends the same UTF-8 as the helpers when it retries with the realm
package RealmUA;
sub new { return bless {creds => [], gets => 0}, shift; }
sub credentials { my $self = shift; push @{$self->{creds}}, [@_]; }
sub get {
  my $self = shift;
  my $res = HTTP::Response->new(++$self->{gets} == 1 ? 401 : 200);
  $res->header('WWW-Authenticate' => 'Digest realm="cam1", nonce="abc"') if $res->code == 401;
  return $res;
}

package main;
foreach my $row (
  ["admin:p\x{e4}ss\@cam", "p\xc3\xa4ss"],
  ["admin:\x{43f}\x{430}\x{440}\@cam", "\xd0\xbf\xd0\xb0\xd1\x80"],
  ['admin:p%C3%A4ss@cam', "p\xc3\xa4ss"],
) {
  my ($address, $bytes) = @$row;
  my $c = bless {ua => RealmUA->new, Monitor => {ControlAddress => $address}}, 'ZoneMinder::Control';
  eval { $c->parse_ControlAddress(); $c->get_realm('/x'); };
  my $cred = $c->{ua}{creds}[-1] // [];
  check('get_realm for '.show($address).' sends the password as UTF-8', show($$cred[1]).' '.show($$cred[3]), 'cam1 '.show($bytes));
}

$c = bless {ua => RealmUA->new, Monitor => {ControlAddress => "admin:p\x{e4}ss\@cam"}}, 'ZoneMinder::Control';
$c->parse_ControlAddress();
$c->{password} = 'other';
$c->get_realm('/x');
check('get_realm uses a password a driver set after parsing', $c->{ua}{creds}[-1][3], 'other');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
