#!/usr/bin/perl
#
# ZoneMinder::Control::Reolink with a password that contains an '@'.
#
# open() stopped the password at its first '@', so admin:p@ss@host signed the
# WS-Security digest with 'p' and posted to the host 'ss@host'.
#
# Run as: perl tests/perl/test_reolink_password_at.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use Digest::SHA;
use MIME::Base64;
use ZoneMinder::Control::Reolink;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

package FakeResponse;
sub new { return bless {}, shift; }
sub is_success { 1 }

package FakeUA;
sub new { return bless {requests => []}, shift; }
sub request { my ($self, $req) = @_; push @{$$self{requests}}, $req; return FakeResponse->new(); }

package main;

{
  no warnings qw(redefine once);
  *ZoneMinder::Control::Reolink::loadMonitor = sub { };
}

my $c = bless {Monitor => {ControlAddress => 'admin:p@ss@10.0.0.5:8000', AutoStopTimeout => 0}},
  'ZoneMinder::Control::Reolink';
$c->open();
my $ua = FakeUA->new();
$$c{ua} = $ua;
$c->moveConUp();

my $req = $$ua{requests}[0];
check('posts to the camera host and port', $req ? $req->uri : undef, 'http://10.0.0.5:8000/onvif/PTZ');

my $body = $req ? $req->content : '';
my ($user) = $body =~ m{<Username>([^<]*)</Username>};
my ($digest) = $body =~ m{<Password [^>]*>([^<]*)</Password>};
my ($nonce) = $body =~ m{<Nonce [^>]*>([^<]*)</Nonce>};
my ($created) = $body =~ m{<Created [^>]*>([^<]*)</Created>};
check('username', $user, 'admin');
my $want = encode_base64(Digest::SHA::sha1(decode_base64($nonce // '').($created // '').'p@ss'), '');
check('digest is signed with the whole password', $digest, $want);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
