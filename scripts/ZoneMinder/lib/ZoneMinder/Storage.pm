# ==========================================================================
#
# ZoneMinder Storage Module, $Date$, $Revision$
# Copyright (C) 2001-2008  Philip Coombes
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
# This module contains the common definitions and functions used by the rest
# of the ZoneMinder scripts
#
package ZoneMinder::Storage;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Server;
require ZoneMinder::General;

use parent qw(Exporter ZoneMinder::Object);

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use POSIX;

use vars qw/ $serial $primark_key $table $primary_key %fields $debug/;
$serial = $primary_key = 'Id';
$debug = 1;
$table = 'Storage';
$primary_key = 'Id';
%fields = map { $_ => $_ } qw( Id Name Path DoDelete ServerId Type Url DiskSpace Scheme Enabled);

sub Path {
  if ( @_ > 1 ) {
    $_[0]{Path} = $_[1];
  }
  if ( ! ( $_[0]{Id} or $_[0]{Path} ) ) {
    $_[0]{Path} = ($Config{ZM_DIR_EVENTS}=~/^\//) ? $Config{ZM_DIR_EVENTS} : ($Config{ZM_PATH_WEB}.'/'.$Config{ZM_DIR_EVENTS});
  }
  return $_[0]{Path};
} # end sub Path

sub Name {
  if ( @_ > 1 ) {
    $_[0]{Name} = $_[1];
  }
  return $_[0]{Name};
} # end sub Path

sub DoDelete {
  my $self = shift;
  $$self{DoDelete} = shift if @_;
  if ( ! defined $$self{DoDelete} ) {
    $$self{DoDelete} = 1;
  }
  return $$self{DoDelete};
}

sub Server {
  my $self = shift;
  if ( ! $$self{Server} ) {
    $$self{Server} = new ZoneMinder::Server( $$self{ServerId} );
  }
  return $$self{Server};
}

sub s3 {
  my $self = shift;
  if (!$$self{s3}) {
    my $url = $$self{Url};
    $url =~ s/^(s3|s3fs):\/\///ig;
    $url =~ /^\s*(?<ID>[^:]+):(?<SECRET>[^@]+)@(?<HOST>(https?:\/\/)?[^\/]*)\/(?<BUCKET>[^\/]+)(?<SUBPATH>\/.+)?\s*$/;
    my ( $aws_id, $aws_secret, $aws_host, $aws_bucket, $subpath ) = ($+{ID},$+{SECRET}, $+{HOST}, $+{BUCKET}, $+{SUBPATH});
    $$self{aws_bucket} = $aws_bucket;
    $$self{aws_subpath} = $subpath;
    $subpath = '' if !$subpath;
    Debug("S3 url parsed to id:$aws_id secret:$aws_secret host:$aws_host, bucket:$aws_bucket, subpath:$subpath\n from $url");
    if ($aws_id and $aws_secret and $aws_host and $aws_bucket) {
      eval {
        require Net::Amazon::S3;
        require Net::Amazon::S3::Vendor::Generic;
        my $vendor = undef;
        if ($aws_host) {
          $aws_host =~ s/^https?:\/\///ig;
          $vendor = Net::Amazon::S3::Vendor::Generic->new(
            host=>$aws_host,
            authorization_method => 'Net::Amazon::S3::Signature::V4',
            use_virtual_host => 0,
          );
        }
        my $s3 = $$self{s3} = Net::Amazon::S3->new( {
            aws_access_key_id     => $aws_id,
            aws_secret_access_key => $aws_secret,
            ( $vendor ? (vendor => $vendor) : (
              )),
          });
        #$s3->ua(LWP::UserAgent->new(keep_alive => 0, requests_redirectable => [qw'GET HEAD DELETE PUT POST']));
      };
      Error($@) if $@;
    } else {
      Warning('Failed to parse s3fs url.');
    } # end if parsed url
  }
  return $$self{s3};
}

sub bucket {
  my $self = shift;
  if (!$$self{bucket}) {
    my $s3 = $self->s3();
    if ($s3) {
      my $bucket = $$self{bucket} = $s3->bucket($$self{aws_bucket});
      if ( !$bucket ) {
        Error("S3 bucket $bucket not found.");
        die;
      }
    } # end if s3 
  } # end if bucket
  return $$self{bucket};
}

sub aws_subpath {
  my $self = shift;
  return defined($$self{aws_subpath}) ? $$self{aws_subpath} : '';
}

sub delete_path {
  my $self = shift;
  my $path = shift;

  my $deleted = 0;
  
  if ($$self{Type} and ($$self{Type} eq 's3fs')) {
    Debug("Delete $path");
    my $s3 = $self->s3();
    my $bucket = $self->bucket();
    if ($s3 and $bucket) {
      eval {
        if ($bucket->delete_key($$self{aws_subpath}.$path)) {
          $deleted = 1;
        } else {
          Error('Failed to delete from S3:'.$s3->err . ': ' . $s3->errstr);
        }
      };
      Error($@) if $@;
    } # end if s3
  } # end if s3fs

  if (!$deleted) {
    my $storage_path = $self->Path();
    ($storage_path) = ($storage_path =~ /^(.*)$/); # De-taint
    ($path) = ($path =~ /^(.*)$/); # De-taint
    my $command = "$Config{ZM_PATH_RM} -rf $storage_path/$path 2>&1";
    if (ZoneMinder::General::executeShellCommand($command)) {
      Error("Error deleting event directory at $storage_path/$path using $command");
    }
  }
} # end sub delete_path

1;
__END__

=head1 NAME

ZoneMinder::Storage - Perl modules for Storage objects

=head1 SYNOPSIS

  use ZoneMinder::Storage;
  my $Storage = ZoneMinder::Storage->find_one(Name=>'Default');
  my @S3Areas = ZoneMinder::Stroage->find(Type=>'s3fs');
  etc...

=head1 DESCRIPTION

Stub documentation for ZoneMinder, created by h2xs. It looks like the
author of the extension was negligent enough to leave the stub
unedited.

Blah blah blah.

=head2 EXPORT

None by default.



=head1 SEE ALSO

Mention other useful documentation such as the documentation of
related modules or operating system documentation (such as man pages
in UNIX), or any relevant external documentation such as RFCs or
standards.

If you have a mailing list set up for your module, mention it here.

If you have a web site set up for your module, mention it here.

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2022 ZoneMinder Inc

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
