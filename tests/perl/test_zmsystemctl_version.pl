#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib", "$dir/bin");

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::Info'} = sub { 1 };
  *{$caller.'::Fatal'} = sub { print "Fatal: $_[0]\n"; exit(1); };
}
1;
EOM
write_file("$dir/bin/systemctl", "#!/bin/sh\necho \"systemctl \$*\"\n");
chmod(0755, "$dir/bin/systemctl");

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmsystemctl.pl.in") or die "Can't open zmsystemctl.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/^#!.*\n//;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
write_file("$dir/zmsystemctl.pl", $source);

sub run {
  local $ENV{PATH} = "$dir/bin:/usr/bin:/bin";
  my $out = qx($^X -I$dir/lib $dir/zmsystemctl.pl $_[0] 2>&1);
  chomp($out);
  return $out;
}

check('restart is passed on with the service name', run('restart'), 'systemctl restart zoneminder');
check('version asks systemctl for its version', run('version'), 'systemctl --version');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
