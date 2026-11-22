use strict;
use warnings;
use Test::More tests => 9;

# Config, Logger and Database are generated or need a database; stand in for
# them so that only Server.pm and Object.pm are exercised.
BEGIN {
  foreach my $m (qw(Base Object_Type Config Logger Database)) {
    $INC{"ZoneMinder/$m.pm"} = 1;
    no strict 'refs';
    *{"ZoneMinder::${m}::import"} = sub { };
  }
}

require_ok('ZoneMinder::Server');

# Servers.Port is a nullable INTEGER, so a row can legitimately have no port.
sub server { return bless {Id => 2, Protocol => 'http', Hostname => 'cam.example', @_}, 'ZoneMinder::Server'; }

my @warnings;
local $SIG{__WARN__} = sub { push @warnings, @_ };

is(server(Port => undef)->Url(), 'http://cam.example', 'NULL port adds no colon');
is(server(Port => '')->Url(), 'http://cam.example', 'empty port adds no colon');
is(server(Port => 0)->Url(), 'http://cam.example', 'zero port adds no colon');
is(server(Port => 8080)->Url(), 'http://cam.example:8080', 'explicit port is kept');
is(server(Protocol => 'https', Port => 443)->Url(), 'https://cam.example', 'default https port is dropped');
is(server(Port => undef)->UrlToApi(), 'http://cam.example/zm/api', 'api url has no dangling colon');

# Url() must not remember the port of the previous server it was called on.
is(server(Hostname => 'a.example', Port => 8080)->Url(), 'http://a.example:8080', 'first server uses its own port');
is(server(Hostname => 'b.example', Port => 8081)->Url(), 'http://b.example:8081', 'second server uses its own port, not the first one\'s');
