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

sub CpuUsage {

  my $fileNameCurStat = '/proc/stat';
  # If we fail, fall through to using top
  if (-e $fileNameCurStat and open(STAT, $fileNameCurStat)) {
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
    my $top_output = `top -b -n 1 | grep -i "^%Cpu(s)" | awk '{print \$2, \$4, \$6, \$8}'`;
    my ($user, $system, $nice, $idle) = split(/ /, $top_output);
    $user =~ s/[^\d\.]//g;
    $system =~ s/[^\d\.]//g;
    $nice =~ s/[^\d\.]//g;
    $idle =~ s/[^\d\.]//g;
    if (!$user) {
      ZoneMinder::Logger::Warning("Failed getting user_utilization from $top_output");
      $user = 0;
    }
    if (!$system) {
      ZoneMinder::Logger::Warning("Failed getting system_utilization from $top_output");
      $system = 0;
    }
    return ($user, $nice, $system, $idle, $user + $system);
  }
} # end sub CpuUsage

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
