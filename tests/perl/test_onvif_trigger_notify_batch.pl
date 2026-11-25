#!/usr/bin/perl
#
# zmonvif-trigger's Notify handler must pass on every NotificationMessage of a
# Notify. Cameras batch messages; the handler read the first Topic and merged
# the Source and Data items of all messages, so an IsMotion=true followed by
# IsMotion=false in one Notify only turned the alarm off, and a rule's state
# was reported under another message's rule.
#
# Needs SOAP::Lite to build the SOAP::SOM; skips without it.
#
# Run as: perl tests/perl/test_onvif_trigger_notify_batch.pl
#
use strict;
use warnings;
use FindBin;

if (!eval { require SOAP::Lite; 1 }) {
  print "1..0 # SKIP SOAP::Lite does not load here\n";
  exit 0;
}

my $file = "$FindBin::Bin/../../scripts/zmonvif-trigger.pl.in";
open(my $fh, '<', $file) or die "Can't open $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
$src =~ /^(  sub Notify \{.*?^  \})/ms or die 'sub Notify not found';
my $notify_sub = $1;

my @calls;
sub handle_notification {
  my ($zm, $id, $topic, $time, $source, $data) = @_;
  push @calls, join('|', $id, $topic, $time,
    join(',', map { "$_=$source->{$_}" } sort keys %$source),
    join(',', map { "$_=$data->{$_}" } sort keys %$data));
}

{
  package _Consumer;
  my $zm = 'zm';
  sub Debug {}
  sub Warning {}
  eval $notify_sub . "\n1;" or die $@;
}

package FakeContext;
sub new { return bless { path => $_[1] }, $_[0]; }
sub request { return $_[0]; }
sub uri { return $_[0]; }
sub path { return $_[0]->{path}; }
package main;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++; print "ok - $name\n";
  } else {
    $failures++; print "NOT OK - $name\n  got:  $got\n  want: $want\n";
  }
}

sub message {
  my ($topic, $time, $rule, $name, $value) = @_;
  return qq{<wsnt:NotificationMessage><wsnt:Topic Dialect="http://www.onvif.org/ver10/tev/topicExpression/ConcreteSet">$topic</wsnt:Topic>}
    .qq{<wsnt:Message><tt:Message UtcTime="$time"><tt:Source><tt:SimpleItem Name="Rule" Value="$rule"/></tt:Source>}
    .qq{<tt:Data><tt:SimpleItem Name="$name" Value="$value"/></tt:Data></tt:Message></wsnt:Message></wsnt:NotificationMessage>};
}

sub notify {
  my @messages = @_;
  my $xml = '<?xml version="1.0"?><s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"'
    .' xmlns:wsnt="http://docs.oasis-open.org/wsn/b-2" xmlns:tt="http://www.onvif.org/ver10/schema"'
    .' xmlns:wsa="http://www.w3.org/2005/08/addressing">'
    .'<s:Header><wsa:Action>http://docs.oasis-open.org/wsn/bw-2/NotificationConsumer/Notify</wsa:Action></s:Header>'
    ."<s:Body><wsnt:Notify>\n  ".join("\n  ", @messages)."\n</wsnt:Notify></s:Body></s:Envelope>";
  my $som = SOAP::Deserializer->deserialize($xml);
  $som->context(FakeContext->new('/ref_4/'));
  @calls = ();
  _Consumer->Notify(undef, $som);
  return join("\n", @calls);
}

check('one message',
  notify(message('tns1:RuleEngine/CellMotionDetector/Motion', 'T1', 'MyMotion', 'IsMotion', 'true')),
  '4|tns1:RuleEngine/CellMotionDetector/Motion|T1|Rule=MyMotion|IsMotion=true');

check('motion start and end in one Notify are both handled, in order',
  notify(
    message('tns1:RuleEngine/CellMotionDetector/Motion', 'T1', 'MyMotion', 'IsMotion', 'true'),
    message('tns1:RuleEngine/CellMotionDetector/Motion', 'T2', 'MyMotion', 'IsMotion', 'false')),
  "4|tns1:RuleEngine/CellMotionDetector/Motion|T1|Rule=MyMotion|IsMotion=true\n"
  ."4|tns1:RuleEngine/CellMotionDetector/Motion|T2|Rule=MyMotion|IsMotion=false");

check('each message keeps its own topic, rule and data',
  notify(
    message('tns1:RuleEngine/CellMotionDetector/Motion', 'T1', 'MyMotion', 'IsMotion', 'true'),
    message('tns1:RuleEngine/TamperDetector/Tamper', 'T2', 'MyTamper', 'IsTamper', 'false')),
  "4|tns1:RuleEngine/CellMotionDetector/Motion|T1|Rule=MyMotion|IsMotion=true\n"
  ."4|tns1:RuleEngine/TamperDetector/Tamper|T2|Rule=MyTamper|IsTamper=false");

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
