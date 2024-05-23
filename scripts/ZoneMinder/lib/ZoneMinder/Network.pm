package ZoneMinder::Network;

use 5.006;
use strict;
use warnings;

require Exporter;
require ZoneMinder::Base;

our @ISA = qw(Exporter ZoneMinder::Base);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration   use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    functions => [ qw(
      getInterfaces
      getNetworks
      getSubnets
      getArpScan
      getVendor
      ) ]
    );
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{all} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);

use POSIX;
use Data::Dumper;

our %MacVendors = ();

sub getInterfaces {
  my @interfaces = ();
  my $output = `ip link`;
  foreach my $line ( split(/\n/, $output) ) {
    if ( $line =~ /^\d+: ([[:alnum:]]+):/ ) {
      push @interfaces, $1;

    } else {
      Debug("No match for $line");
    }
  } # end foreach line
  return @interfaces;
} # end sub getInterfaces

sub getNetworks {
  # Skip lo, not interesting in localhost
  my %interfaces = map { $_ ne 'lo' ? ($_ => []) : () } getInterfaces();

  # Add routes
  my $output = `ip route`;
  foreach my $line ( split(/\n/, $output ) ) {
    if ( $line =~ /^default via [.\d]+ dev (\w+)/ ) {
      $interfaces{default} = $1;
    } elsif ( ($line =~ /^([.\d]+\/\d+) dev (\w+)/) ) {
      if ($1 ne '169.254.0.0/16') {
        $interfaces{$2} = [] if !$interfaces{$2};
        push @{$interfaces{$2}}, $1;
      }
    } else {
      Debug("Didn't match line $line in getNetworks");
    }
  } # end foreach line of output
  return %interfaces;
}

sub getSubnets {
}

# Returns a hash with ip as key, mac as value
sub getArpScan {
  my $network = shift;
  my $device = shift;
  my %hosts = ();
  Debug("/usr/sbin/arp-scan $network -I $device 2>&1");
  my $output = `/usr/sbin/arp-scan $network -I $device 2>&1`;
  if ($output) {
	  Debug($output);
  foreach my $line (split(/\n/, $output)) {
    if ($line =~ /^(\d+\.\d+\.\d+\.\d+)\s+([0-9a-f:]+)\s+(.*)$/) {
      $hosts{$1} = {ip=>$1, mac=>$2, vendor=>$3};
    } else {
      Debug("Didn't match preg $line");
    }
  } # end foreach line
  } else {
	  Error("No output from /usr/sbin/arp-scan $network 2>&1");
  }
  return %hosts;
} # end sub getArpScan

# Enhanced OUI get Vendor.  Uses our own database and will cache.
sub getVendor {
  my $mac = shift;
  my $vendor = '';


  if (!%MacVendors) {
	  my $file = '/usr/share/arp-scan/ieee-oui.txt';
	  if ( -e $file) {
		  open my $info, $file or die "Could not open $file: $!";
		  while ( my $line = <$info>)  {   
			  next if $line =~ /^#/;
			  $line =~ /^([A-Fa-f0-9]{6})\s+(.*)$/;
			  $MacVendors{lc $1} = { vendor=>$2 };
		  }
		  close $info;
	  }
    if (-e $Config{ZM_PATH_DATA}.'/MacVendors.json') {
      my $json = ZoneMinder::General::jsonLoad($Config{ZM_PATH_DATA}.'/MacVendors.json');
      if ($json) {
        Debug(Data::Dumper::Dumper($json));
        @MacVendors{keys %{$json}} = values %{$json};
      } else {
        Warning("No json for " . $Config{ZM_PATH_DATA}.'/MacVendors.json');
      }
    } else {
      Warning('MacVendors does not exists at '.$Config{ZM_PATH_DATA}.'/MacVendors.json');
    }
  }
  my $oui = lc $mac;
  $oui =~ s/[^0-9a-f]//g;
  $oui = substr($oui, 0, 6);

  if (%MacVendors and $MacVendors{$oui}) {
    return $MacVendors{$oui};
  }

  eval {
    require LWP::UserAgent;
    my $ua = LWP::UserAgent->new();
    my $url = 'https://api.macvendors.com/' . $mac;
    my $response = $ua->get($url);
    if ($response->is_success) {
      $vendor = $response->content; 
    } else {
      print $response->status_line;
    }
  };
  Error("Error in eval: $@") if $@;
  if ($vendor) {
    $MacVendors{$oui} = { vendor=>$vendor };
  }

  return $MacVendors{$oui};
} # end sub getVendor


1;
__END__

=head1 NAME

ZoneMinder::Network - Network Utility Functions for ZoneMinder

=head1 SYNOPSIS

use ZoneMinder::Network;

=head1 DESCRIPTION

This module contains functions used by the rest
of the ZoneMinder scripts to get network information

=head2 EXPORT

    functions => [ qw(
      getInterfaces
      getNetworks
      getSubnets
      getArpScan
      ) ]


=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2022    ZoneMinder Inc.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

=cut
