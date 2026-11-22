#!/usr/bin/perl
#
# ZoneMinder::Control::post with a hash of form fields.
#
# post() built the form body by setting the query of $$self{uri}. When the
# ControlAddress has a path (admin:pw@cam/) URI::canonical hands back that same
# object as BaseURL, so the form fields stayed in the base url and every later
# request of the zmcontrol process went to the wrong url. Trendnet's presetSet
# and presetClear post a hash.
#
# Run as: perl tests/perl/test_control_post_form.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use HTTP::Response;
use ZoneMinder::Control::Trendnet;

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
sub new { return bless {urls => [], bodies => []}, shift; }
sub credentials {}
sub get {
  my ($self, $url) = @_;
  push @{$self->{urls}}, "$url";
  return HTTP::Response->new(200);
}
sub request {
  my ($self, $req) = @_;
  push @{$self->{urls}}, $req->uri().'';
  push @{$self->{bodies}}, $req->content();
  return HTTP::Response->new(200);
}

package main;

foreach my $address ('admin:pw@192.0.2.1/', 'admin:pw@192.0.2.1:8080') {
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => {ControlAddress => $address, ModectDuringPTZ => 1},
    state => 'open',
  }, 'ZoneMinder::Control::Trendnet';
  $control->guess_credentials();
  (my $base = 'http://'.$address) =~ s{/?$}{/};

  $control->presetSet({preset => 2});
  my %form = map { split(/=/, $_, 2) } split(/&/, $ua->{bodies}[0]);
  check("$address: presetSet posts the form", join(',', map { "$_=$form{$_}" } sort keys %form),
    'Submit=Add,command=add,presetName=2');
  check("$address: presetSet goes to cam_control.cgi", $ua->{urls}[0], $base.'eng/admin/cam_control.cgi');

  $control->presetGoto({preset => 2});
  check("$address: the next command goes to its own url", $ua->{urls}[1],
    $base.'cgi/ptdc.cgi?command=goto_preset_position&index=1');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
