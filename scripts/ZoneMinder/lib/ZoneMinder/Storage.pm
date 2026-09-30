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

use vars qw/ $serial $table $primary_key %fields %defaults $debug/;
$serial = $primary_key = 'Id';
$debug = 1;
$table = 'Storage';
$primary_key = 'Id';
%fields = map { $_ => $_ } qw( Id Name Path DoDelete ServerId Type Url DiskSpace Scheme Enabled);

%defaults = (
  Type => 'local',
  Scheme => 'Medium',
);

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
    if (!$Config{ZM_PATH_RM}) {
      Info('No value set for ZM_PATH_RM.  ZM_PATH_RM should have been set automatically by the distro in '.$Config{ZM_CONFIG_DIR}.'/conf.d/01-system-paths.conf. Defaulting to /bin/rm');
      $Config{ZM_PATH_RM} = '/bin/rm';
    }
    my $command = "$Config{ZM_PATH_RM} -rf $storage_path/$path 2>&1";
    if (ZoneMinder::General::executeShellCommand($command)) {
      Error("Error deleting event directory at $storage_path/$path using $command");
    }
  }
} # end sub delete_path

# ==========================================================================
#
# Storage.DiskSpace accounting
#
# ==========================================================================
#
# Storage.DiskSpace is a running total, adjusted relative to its current value
# as events are deleted or moved. It must NOT be maintained with the
# read-modify-write pattern (lock_and_load + save): outside a transaction the
# SELECT ... FOR UPDATE autocommits and drops its lock immediately, so the
# absolute value written afterwards clobbers anything zmc, zmaudit or another
# filter did in between. A single relative UPDATE is atomic on its own, needs no
# pre-read, and holds the row lock only for the length of that one statement.
#
# Keeping it to one statement also keeps Storage[Id] out of the lock chain that
# deleting an event walks (see db/triggers.sql):
#   Events[Id] -> Events_Hour/Day/Week/Month[EventId] -> Event_Summaries[MonitorId]
#
# The value remains an approximation: it is a cache of SUM(Events.DiskSpace),
# and zmaudit.pl resyncs it (with a CAS so it does not clobber concurrent
# writers).

# $delta is signed: negative when an event leaves this storage area.
# A storage area with no Id isn't stored anywhere, so there is nothing to adjust
# - Event::Storage() hands back a blank object when the event has no StorageId.
sub adjust_diskspace {
  my ($self, $delta) = @_;
  return if !$$self{Id} or !$delta;

  my $rows = ZoneMinder::Database::zmDbDo(
    'UPDATE Storage SET DiskSpace=GREATEST(COALESCE(DiskSpace,0)+?,0) WHERE Id=?',
    $delta, $$self{Id});

  # Read the new total back rather than adding $delta to whatever we are already
  # holding. ZoneMinder::Object caches objects for the life of the process and
  # its accessors never re-load, so that value can be arbitrarily old; adjusting
  # it locally would keep it wrong while making it look authoritative, and its
  # clamp at zero could disagree with the GREATEST above. This is still only a
  # snapshot - another process may adjust the row before anyone reads it - but
  # it is the value that was actually stored rather than a guess at it.
  if (defined $rows) {
    my $row = ZoneMinder::Database::zmDbFetchOne(
      'SELECT DiskSpace FROM Storage WHERE Id=?', $$self{Id});
    $$self{DiskSpace} = $$row{DiskSpace} if $row;
  }

  return $rows;
}

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

Licensed under the GNU General Public License v2 or later; see the COPYING
file distributed with ZoneMinder for the full text.


=cut
