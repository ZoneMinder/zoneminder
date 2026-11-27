#!/usr/bin/perl
# ZM_AUDIT_CHECK_INTERVAL and ZM_FILTER_RELOAD_DELAY accept 0 on the Options
# page, which made zmaudit and zmfilter loop without pausing. Each script must
# read the option through a helper that falls back to the default below 1.
use strict;
use warnings;
use FindBin;

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

my @warnings;
{
  no strict 'refs';
  *{'main::Warning'} = sub { push @warnings, join('', @_) };
}

sub slurp {
  my $file = shift;
  open(my $fh, '<', $file) or die "Can't read $file: $!";
  local $/;
  my $s = <$fh>;
  close($fh);
  return $s;
}

foreach my $case (
  ['zmaudit.pl.in',  'audit_interval',       'ZM_AUDIT_CHECK_INTERVAL', 900],
  ['zmfilter.pl.in', 'filter_reload_delay', 'ZM_FILTER_RELOAD_DELAY',  300],
) {
  my ($script, $sub, $option, $default) = @$case;
  my $src = slurp("$FindBin::Bin/../../scripts/$script");

  my ($code) = $src =~ /^(sub \Q$sub\E\b.*?^\})/ms;
  if (!$code) {
    check("$script defines sub $sub", 0);
  } else {
    check("$script defines sub $sub", 1);
    eval "package Case_$sub; no strict 'vars'; sub Warning { main::Warning(\@_) } $code; 1" or die $@;
    my $f = \&{"Case_${sub}::$sub"};

    @warnings = ();
    check("$sub(0) is $default", $f->(0) == $default);
    check("$sub(0) warns naming $option and $default",
      (@warnings == 1 && $warnings[0] =~ /\Q$option\E/ && $warnings[0] =~ /\b$default\b/));
    foreach my $v (1, 3600) {
      @warnings = ();
      check("$sub($v) unchanged, no warning", ($f->($v) == $v && !@warnings));
    }
    foreach my $v (undef, '') {
      @warnings = ();
      check("$sub(".(defined($v) ? "''" : 'undef').") is $default", $f->($v) == $default);
    }
  }

  check("$script does not sleep on $option directly",
    $src !~ /sleep\s*\(?\s*\$Config\{$option\}/);
  my @uses = grep { /\$Config\{$option\}/ } split /\n/, $src;
  check("$script reads \$Config{$option} only once (".scalar(@uses).')', @uses == 1);
}

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
