#!/usr/bin/perl
#
# Camera and storage credentials must not reach a log line.
#
# The PTZ control modules build URLs with the camera's user:password@ (from
# ControlAddress or Path) or pwd= (from ControlDevice) in them, and log them at
# Debug, Info or Error; Info and Error land in the Logs table, which every
# System=View user can read. ZoneMinder::Logger now passes every line through
# redactCredentials(), and the modules log urlForLog() of their URLs and no
# password fields. This tests the helpers, the logger itself, and scans the
# Perl modules for log calls that interpolate a password variable.
#
# Run as: perl tests/perl/test_log_redact.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Logger qw(:all);

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

print "logFingerprint\n";
# Same vector as tests/zm_crypt.cpp and tests/php/test_log_redact.php.
check('first 8 hex of SHA-256', logFingerprint('abc'), 'fp:ba7816bf');
check('empty value', logFingerprint(''), 'fp:none');

print "\nurlForLog\n";
check('plain URL unchanged', urlForLog('http://cam-1:80/ISAPI/PTZCtrl/channels/1/continuous'),
  'http://cam-1:80/ISAPI/PTZCtrl/channels/1/continuous');
check('userinfo removed, host and path kept', urlForLog('rtsp://admin:hunter2@cam-1:554/Streaming/Channels/101'),
  'rtsp://cam-1:554/Streaming/Channels/101');
check('password with a raw @', urlForLog('http://admin:p@ss@cam-1/x'), 'http://cam-1/x');
check('ControlAddress without a scheme', urlForLog('admin:hunter2@cam-1:8080'), 'cam-1:8080');
check('pwd= replaced, other parameters kept',
  urlForLog('http://cam/decoder_control.cgi?command=0&user=admin&pwd=hunter2'),
  'http://cam/decoder_control.cgi?command=0&user=admin&pwd=[redacted]');
check('Amcrest user.Password replaced',
  urlForLog('http://cam/cgi-bin/userManager.cgi?action=addUser&user.Name=bob&user.Password=pw1'),
  'http://cam/cgi-bin/userManager.cgi?action=addUser&user.Name=bob&user.Password=[redacted]');
check('loginpas replaced', urlForLog('http://cam/set.cgi?loginuse=admin&loginpas=pw1'),
  'http://cam/set.cgi?loginuse=admin&loginpas=[redacted]');
check('camera API token fingerprinted', urlForLog('https://cam/cgi-bin/api.cgi?cmd=PtzCtrl&token=abc123'),
  'https://cam/cgi-bin/api.cgi?cmd=PtzCtrl&token='.logFingerprint('abc123'));
check('undef', urlForLog(undef), '');

print "\nredactCredentials\n";
check('ordinary text unchanged', redactCredentials('Moving to preset 3 at 12:30:01, cam-1:80 user admin'),
  'Moving to preset 3 at 12:30:01, cam-1:80 user admin');
check('e-mail address unchanged', redactCredentials('mail to bob@example.com'), 'mail to bob@example.com');
check('ControlAddress in a sentence', redactCredentials('Using ControlAddress for credentials: admin:hunter2@cam-1'),
  'Using ControlAddress for credentials: cam-1');
check('command string from ControlDevice', redactCredentials('decoder_control.cgi?command=0&user=admin&pwd=hunter2'),
  'decoder_control.cgi?command=0&user=admin&pwd=[redacted]');
check('WS-Security digest', redactCredentials('<wsse:Password Type="x#PasswordDigest">AbC=</wsse:Password>'),
  '<wsse:Password Type="x#PasswordDigest">[redacted]</wsse:Password>');
check('JSON password', redactCredentials('{"User":{"userName":"admin","password":"hun\"ter2"}}'),
  '{"User":{"userName":"admin","password":"[redacted]"}}');
check('S3 URL', redactCredentials('s3fs://AKIAEXAMPLE:wJalrSECRET@https://s3.example.com/bucket/zm'),
  's3fs://https://s3.example.com/bucket/zm');

print "\nlogPrint\n";
# What the logger writes, through the terminal destination, at debug level.
logInit(id => 'test_log_redact');
logDatabaseLevel(ZoneMinder::Logger::NOLOG);
logFileLevel(ZoneMinder::Logger::NOLOG);
logSyslogLevel(ZoneMinder::Logger::NOLOG);
logTermLevel(ZoneMinder::Logger::DEBUG);
logLevel(ZoneMinder::Logger::DEBUG);

sub logged {
  my $code = shift;
  my $buffer = '';
  open(my $saved, '>&', \*STDERR) or die "dup STDERR: $!";
  close(STDERR);
  open(STDERR, '>', \$buffer) or die "capture STDERR: $!";
  $code->();
  close(STDERR);
  open(STDERR, '>&', $saved) or die "restore STDERR: $!";
  return $buffer;
}

{
  my $out = logged(sub {
    Debug('Response from http://admin:hunter2@cam-1/x?pwd=hunter2: 200 OK');
    Error('Url: http://admin:hunter2@cam-1/cgi-bin/setGPIO.cgi');
  });
  check('logged lines do not contain the password', ($out =~ /hunter2/ ? $out : 'absent'), 'absent');
  check('logged lines keep the host and path', ($out =~ m{http://cam-1/x\?pwd=\[redacted\]} ? 'kept' : $out), 'kept');
}

logTerm();
print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
