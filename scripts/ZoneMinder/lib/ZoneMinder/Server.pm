# ==========================================================================
#
# ZoneMinder Server Module
# Copyright (C) 2023 ZoneMinder Inc
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
package ZoneMinder::Server;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Config;
require ZoneMinder::Logger;
require ZoneMinder::Object;

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial @identified_by %defaults $debug/;

$debug = 1;
$table = 'Servers';
@identified_by = ('Id');
$serial = $primary_key = 'Id';
%fields = map { $_, $_ } qw(
  Id
  Name
  Protocol
  Hostname
  Port
  PathToIndex
  PathToZMS
  PathToApi
  State_Id
  Status
  CpuLoad
  CpuUserPercent
  CpuNicePercent
  CpuSystemPercent
  CpuIdlePercent
  CpuUsagePercent
  TotalMem
  FreeMem
  TotalSwap
  FreeSwap
  zmstats
  zmaudit
  zmtrigger
  zmeventnotification
  Latitude
  Longitude
  );

sub CpuLoad {
  my $output = qx(uptime);
  my @sysloads = split ', ', (split ': ', $output)[-1];
  # returned value is 1min, 5min, 15min load
  tr/,/./ foreach @sysloads;

  if (join(', ', @sysloads) =~ /(\d+\.\d+)\s*,\s+(\d+\.\d+)\s*,\s+(\d+\.\d+)\s*$/) {
    return @sysloads;
  }

  return (undef, undef, undef);
} # end sub CpuLoad

# These conditions do not clear themselves, so only complain about them once
# per process instead of every ZM_STATS_UPDATE_INTERVAL.
my $warned_cpu_usage = 0;

# Parse the CPU state lines of OpenBSD's top. It prints either a single
# aggregate "CPU states:" line or one "CPU0 states:" line per core,
# depending on its mode, so sum whichever we get and average over the
# number of lines seen. Field widths vary with the value (100.0 vs 0.0),
# so don't depend on the padding. Returns
# ($user, $nice, $system, $idle), or the empty list if nothing matched.
sub parse_bsd_top_cpu {
  my $top_output = shift;
  my ($user, $nice, $system, $idle, $cpus) = (0, 0, 0, 0, 0);

  foreach my $line (split(/\n/, $top_output)) {
    if ($line =~ /^CPU\d*\s+states:\s*([\d\.]+)%\s*user,\s*([\d\.]+)%\s*nice,\s*([\d\.]+)%\s*sys,\s*([\d\.]+)%\s*spin,\s*([\d\.]+)%\s*intr,\s*([\d\.]+)%\s*idle/) {
      $user += $1;
      $nice += $2;
      $system += $3;
      $idle += $6;
      $cpus += 1;
    } # end if line match
  } # end foreach line

  return () if !$cpus;
  return ($user/$cpus, $nice/$cpus, $system/$cpus, $idle/$cpus);
} # end sub parse_bsd_top_cpu

sub CpuUsage {

  my $fileNameCurStat = '/proc/stat';
  # If we fail, fall through to using top
  my $stat_error;
  if (!-e $fileNameCurStat) {
    # /proc/stat is hidden from us when we are in a mount namespace that was
    # set up with ProcSubset=pid, which is systemd's default hardening for
    # apache. Daemons started from the web ui inherit it. top reads /proc/stat
    # as well, so the fallback below cannot work either.
    $stat_error = "$fileNameCurStat does not exist. Are we in a mount namespace with ProcSubset=pid? If we were started from the web ui, add ProcSubset=all to the web server's service.";
  } elsif (!open(STAT, $fileNameCurStat)) {
    $stat_error = "Failed to open $fileNameCurStat: $!";
  }

  if (!$stat_error) {
    my ($self, $prev_user, $prev_nice, $prev_sys, $prev_idle, $prev_total, $cpu_user, $cpu_nice, $cpu_sys, $cpu_idle);
    my ($user_percent, $nice_percent, $sys_percent, $idle_percent, $usage_percent);

    if (@_==1) {
      $self = shift;
      ($prev_user, $prev_nice, $prev_sys, $prev_idle, $prev_total) = @$self{'prev_user','prev_nice','prev_sys','prev_idle','prev_total'};
    } elsif (@_>1) {
      $self = {};
      ($prev_user, $prev_nice, $prev_sys, $prev_idle, $prev_total) = @_;
    }
    while (<STAT>) {
      # Individual core lines will start with cpu\d+.  We want all cpus, which tends to be the first line, sans digit.
      if (/^cpu\s+[0-9]+/) {
        (undef, $cpu_user, $cpu_nice, $cpu_sys, $cpu_idle) = split /\s+/, $_;
        last;
      }
    }
    close STAT;

    my $diff_user = $cpu_user - ($prev_user // 0);
    my $diff_nice = $cpu_nice - ($prev_nice // 0);
    my $diff_sys = $cpu_sys - ($prev_sys // 0);
    my $diff_idle = $cpu_idle - ($prev_idle // 0);
    my $diff_total = $diff_user + $diff_nice + $diff_sys + $diff_idle;

    if ($diff_total != 0){
      $user_percent = 100 * $diff_user / $diff_total;
      $nice_percent = 100 * $diff_nice / $diff_total;
      $sys_percent = 100 * $diff_sys / $diff_total;
      $idle_percent = 100 * $diff_idle / $diff_total;
      $usage_percent = 100 * ($diff_total - $diff_idle) / $diff_total;
    }

    $$self{prev_user} = $cpu_user;
    $$self{prev_nice} = $cpu_nice;
    $$self{prev_sys} = $cpu_sys;
    $$self{prev_idle} = $cpu_idle;

    return ($user_percent, $nice_percent, $sys_percent, $idle_percent, $usage_percent);

  } else {
    # Get CPU utilization percentages
    my $uname_output = lc(qx($ZoneMinder::Config{ZM_PATH_UNAME} -s));
    chomp($uname_output);
    my $top_cmd = '';
    if ($uname_output eq 'freebsd') {
      $top_cmd = q`top -b -n 1 | grep "^CPU" | sed 's/%//g' | awk '{print $2, $6, $4, $10}'`;
    } else {
      $top_cmd = q`top -b -n 1 | grep -i "^%Cpu(s)" | awk '{print $2, $4, $6, $8}'`;
    }
    my $top_output = `$top_cmd` // '';

    if ($top_output =~ /\d/) {
      # split on an empty $top_output returns an empty list, so default all four
      # before touching them or we warn under -w on every interval.
      my ($user, $system, $nice, $idle) = split(/ /, $top_output);
      foreach ($user, $system, $nice, $idle) {
        $_ = defined($_) ? $_ : '';
        s/[^\d\.]//g;
        $_ = 0 if $_ eq '';
      }
      if (!$user or !$system) {
        if (!$warned_cpu_usage) {
          $warned_cpu_usage = 1;
          ZoneMinder::Logger::Warning("$stat_error Falling back to top also failed: $top_cmd gave output '$top_output'.");
        }
      }
      return ($user, $nice, $system, $idle, $user + $system);
    }

    # OpenBSD's top prints CPU states in a format neither grep above matches,
    # so parse its raw output instead.
    my $raw_top = `top -b -n 1` // '';
    my ($user, $nice, $system, $idle) = parse_bsd_top_cpu($raw_top);
    if (!defined $user) {
      if (!$warned_cpu_usage) {
        $warned_cpu_usage = 1;
        ZoneMinder::Logger::Warning("$stat_error Falling back to top also failed: $top_cmd gave no numbers and top output could not be parsed: '$raw_top'.");
      }
      ($user, $nice, $system, $idle) = (0, 0, 0, 0);
    }
    return ($user, $nice, $system, $idle, $user + $system);
  }
} # end sub CpuUsage

# Is systemd running? This is what sd_booted(3) does. Do not probe pid 1
# through /proc instead: a web server hardened with ProtectProc=invisible hides
# it from the web user, and we would conclude that systemd is absent precisely
# when we most need to hand our daemons over to it.
sub systemdRunning {
  return -d '/run/systemd/system';
}

# Does $cgroup, the contents of a cgroup file, put us inside $unit's service?
# Takes the text rather than reading the file so that it can be tested. Copes
# with the single "0::/path" line of cgroup v2 and the several
# "id:controller:/path" lines of v1, and does not confuse a unit whose name
# merely starts with $unit for $unit itself.
sub cgroup_in_service {
  my ($cgroup, $unit) = @_;
  return 0 if !defined $cgroup or !defined $unit or $unit eq '';

  return scalar grep { m{/\Q$unit\E\.service(?:/|$)} } split(/\n/, $cgroup);
}

# Are we already running as $unit, so that asking systemd to start it would ask
# systemd to start us again? Our own cgroup answers that however /proc is
# hardened, and we cannot shed it by daemonising.
#
# Not the parent's name from /proc: our parent is pid 1, which a web server
# with ProtectProc=invisible hides from us, and we get re-parented anyway if it
# exits first. Not $ENV{INVOCATION_ID} either: systemd sets that for every
# descendant of a unit, so anything forked from the web ui inherits the web
# server's copy and would wrongly look as though systemd had started it.
sub calledBysystem {
  my $unit = shift;
  $unit = 'zoneminder' if !defined $unit;

  open(my $cgroup, '<', '/proc/self/cgroup') or return 0;
  my $contents = do { local $/ = undef; <$cgroup> };
  close($cgroup);

  return cgroup_in_service($contents, $unit);
}

sub PathToZMS {
  my $this = shift;
  $this->{PathToZMS} = shift if @_;
  if ($this->Id() and $this->{PathToZMS}) {
    return $this->{PathToZMS};
  } else {
    return $ZoneMinder::Config{ZM_PATH_ZMS};
  }
}

sub UrlToZMS {
  my $this = shift;
  return $this->Url(@_).$this->PathToZMS();
}

sub Url {
  my $this = shift;
  my $port = shift if @_;

  if (!$this->Id()) {
    return '';
  }

  my $url = $this->Protocol().'://';
  $url .= $this->Hostname();
  if ( !$port ) {
    $port = $this->Port();
  }
  if ( $this->Protocol() == 'https' and $port == 443 ) {
  } elsif ( $this->Protocol() == 'http' and $port == 80 ) {
  } else {
    $url .= ':'.$port;
  }
  return $url;
}

sub PathToIndex {
  my $this = shift;
  $this->{PathToIndex} = shift if @_;

  return $this->{PathToIndex} if $this->{PathToIndex};
}

sub UrlToIndex {
  my $this = shift;
  return $this->Url(@_).$this->PathToIndex();
}

sub UrlToApi {
  my $this = shift;
  return $this->Url(@_).$this->PathToApi();
}

sub PathToApi {
  my $this = shift;
  $this->{PathToApi} = shift if @_;
  return $this->{'PathToApi'} if $this->{PathToApi};
  return '/zm/api';
}

1;
__END__

=head1 NAME

ZoneMinder::Server - Perl extension for the ZoneMinder Server Object

=head1 SYNOPSIS

use ZoneMinder::Server;

=head1 DESCRIPTION
