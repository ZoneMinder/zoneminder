# ==========================================================================
#
# ZoneMinder Filter Module
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
package ZoneMinder::Filter;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require Date::Manip;
require POSIX;

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key /;
$table = 'Filters';
$primary_key = 'Id';
# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);
require ZoneMinder::Storage;
require ZoneMinder::Server;

use vars qw/ $table $primary_key %fields /;
$table = 'Users';
$primary_key = 'Id';

%fields = map { $_ => $_ } qw(
Id
Name
ExecuteInterval
Query_json
AutoArchive
AutoUnarchive
AutoVideo
AutoUpload
AutoEmail
EmailTo
EmailSubject
EmailBody
AutoMessage
AutoExecute
AutoExecuteCmd
AutoDelete
AutoMove
AutoMoveTo
AutoCopy
AutoCopyTo
UpdateDiskSpace
UserId
Background
Concurrent
LockRows
);

sub Execute {
  my $self = $_[0];
  my $sql = $self->Sql(undef);

  if ( $$self{PreSQLConditions} and @{$$self{PreSQLConditions}} ) {
    foreach my $term ( @{$$self{PreSQLConditions}} ) {
      if ( $$term{attr} eq 'DiskPercent' ) {
      }
    }
  }

  if ( $self->{HasDiskPercent} ) {
    $$self{Storage} = ZoneMinder::Storage->find_one() if ! $$self{Storage};
		my $disk_percent = getDiskPercent($$self{Storage} ? $$self{Storage}->Path() : $Config{ZM_DIR_EVENTS});
    $sql =~ s/zmDiskPercent/$disk_percent/g;
  }
  if ( $self->{HasDiskBlocks} ) {
    $$self{Storage} = ZoneMinder::Storage->find_one() if ! $$self{Storage};
		my $disk_blocks = getDiskBlocks($$self{Storage} ? $$self{Storage}->Path() : $Config{ZM_DIR_EVENTS});
    $sql =~ s/zmDiskBlocks/$disk_blocks/g;
  }
  if ( $self->{HasSystemLoad} ) {
    my $load = getLoad();
    $sql =~ s/zmSystemLoad/$load/g;
  }


  Debug("Filter::Execute SQL ($sql)");
  my $sth = $ZoneMinder::Database::dbh->prepare_cached($sql)
    or Fatal("Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr());
  my $res = $sth->execute();
  if ( !$res ) {
    Error("Can't execute filter '$sql', ignoring: ".$sth->errstr());
    return;
  }
  my @results;
  while ( my $event = $sth->fetchrow_hashref() ) {
    push @results, $event;
  }
  $sth->finish();
  Debug('Loaded ' . @results . ' events for filter '.$$self{Name}.' using query ('.$sql.')"');
  if ( $self->{PostSQLConditions} ) {
    my @filtered_events;
    foreach my $term ( @{$$self{PostSQLConditions}} ) {
      if ( $$term{attr} eq 'ExistsInFileSystem' ) {
        foreach my $row ( @results ) {
          my $event = new ZoneMinder::Event($$row{Id}, $row);
          if ( -e $event->Path() ) {
            push @filtered_events, $row if $$term{val} eq 'true';
          } else {
            push @filtered_events, $row if $$term{val} eq 'false';
          }
        }
      }
    } # end foreach term
    @results = @filtered_events;
  } # end if has PostSQLConditions
  return @results;
} # end sub Execute

sub Sql {
  my $self = shift;
  $$self{Sql} = shift if @_;
  if ( !$$self{Sql} ) {
    $self->{Sql} = '';
    if ( !$self->{Query_json} ) {
      Warning('No query in Filter!');
      return;
    }

    my $filter_expr = ZoneMinder::General::jsonDecode($self->{Query_json});
    my $sql = 'SELECT E.*, unix_timestamp(E.StartDateTime) as Time
         FROM Events as E';

    if ( $filter_expr->{terms} ) {
      foreach my $term ( @{$filter_expr->{terms}} ) {

        # See getFilterQueryConjunctionTypes()
        if ( exists($term->{cnj}) and $term->{cnj} =~ /^(and|or)$/ ) {
          $self->{Sql} .= ' '.$term->{cnj};
        }
        $self->{Sql} .= ' ';
        if ( exists($term->{obr}) ) {
          $self->{Sql} .= str_repeat('(', $term->{obr}).' ';
        }
        if (!$term->{attr}) {
          Error("Invalid term in filter $$self{Id}. Empty attr");
          next;
        }

        my $value = $term->{val};
        my @value_list;

        if ( $term->{attr} eq 'AlarmedZoneId' ) {
          $term->{op} = 'EXISTS';
        } elsif ( $term->{attr} =~ /^Monitor/ ) {
          $sql = 'SELECT E.*, unix_timestamp(E.StartDateTime) as Time, M.Name as MonitorName
          FROM Events as E INNER JOIN Monitors as M on M.Id = E.MonitorId';
          my ( $temp_attr_name ) = $term->{attr} =~ /^Monitor(.+)$/;
          $self->{Sql} .= 'M.'.$temp_attr_name;
        } elsif ( $term->{attr} eq 'ServerId' or $term->{attr} eq 'MonitorServerId' ) {
          $sql = 'SELECT E.*, unix_timestamp(E.StartDateTime) as Time, M.Name as MonitorName
          FROM Events as E INNER JOIN Monitors as M on M.Id = E.MonitorId';
          $self->{Sql} .= 'M.ServerId';
        } elsif ( $term->{attr} eq 'StorageServerId' ) {
          $self->{Sql} .= '(SELECT Storage.ServerId FROM Storage WHERE Storage.Id=E.StorageId)';
        } elsif ( $term->{attr} eq 'FilterServerId' ) {
          $self->{Sql} .= $Config{ZM_SERVER_ID};
          # StartTime options
        } elsif ( $term->{attr} eq 'DateTime' ) {
          $self->{Sql} .= 'E.StartDateTime';
        } elsif ( $term->{attr} eq 'Date' ) {
          $self->{Sql} .= 'to_days( E.StartDateTime )';
        } elsif ( $term->{attr} eq 'StartDate' ) {
          $self->{Sql} .= 'to_days( E.StartDateTime )';
        } elsif ( $term->{attr} eq 'Time' or $term->{attr} eq 'StartTime' ) {
          $self->{Sql} .= 'extract( hour_second from E.StartDateTime )';
        } elsif ( $term->{attr} eq 'Weekday' or $term->{attr} eq 'StartWeekday' ) {
          $self->{Sql} .= 'weekday( E.StartDateTime )';

          # EndTIme options
        } elsif ( $term->{attr} eq 'EndDateTime' ) {
          $self->{Sql} .= 'E.EndDateTime';
        } elsif ( $term->{attr} eq 'EndDate' ) {
          $self->{Sql} .= 'to_days( E.EndDateTime )';
        } elsif ( $term->{attr} eq 'EndTime' ) {
          $self->{Sql} .= 'extract( hour_second from E.EndDateTime )';
        } elsif ( $term->{attr} eq 'EndWeekday' ) {
          $self->{Sql} .= 'weekday( E.EndDateTime )';
        } elsif ( $term->{attr} eq 'ExistsInFileSystem' ) {
          push @{$self->{PostSQLConditions}}, $term;
          $self->{Sql} .= 'TRUE /* ExistsInFileSystem */';
        } elsif ( $term->{attr} eq 'DiskPercent' ) {
          $self->{Sql} .= 'zmDiskPercent';
          $self->{HasDiskPercent} = !undef;
        } elsif ( $term->{attr} eq 'DiskBlocks' ) {
          $self->{Sql} .= 'zmDiskBlocks';
          $self->{HasDiskBlocks} = !undef;
        } elsif ( $term->{attr} eq 'SystemLoad' ) {
          $self->{Sql} .= 'zmSystemLoad';
          $self->{HasSystemLoad} = !undef;
        } else {
          $self->{Sql} .= 'E.'.$term->{attr};
        }

        if ( $term->{attr} eq 'ExistsInFileSystem' ) {
          # PostCondition, so no further SQL
        } else {
          ( my $stripped_value = $value ) =~ s/^["\']+?(.+)["\']+?$/$1/;
          # Empty value will result in () from split
          foreach my $temp_value ( $stripped_value ? split( /["'\s]*?,["'\s]*?/, $stripped_value ) : $stripped_value ) {
            if ( $term->{attr} eq 'AlarmedZoneId' ) {
              $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND Score > 0 AND ZoneId='.$value.')';
            } elsif ( $term->{attr} =~ /^MonitorName/ ) {
              $value = "'$temp_value'";
            } elsif ( $term->{attr} =~ /ServerId/) {
              Debug("ServerId, temp_value is ($temp_value) ($ZoneMinder::Config::Config{ZM_SERVER_ID})");
              if ( $temp_value eq 'ZM_SERVER_ID' ) {
                $value = "'$ZoneMinder::Config::Config{ZM_SERVER_ID}'";
                # This gets used later, I forget for what
                $$self{Server} = new ZoneMinder::Server($ZoneMinder::Config::Config{ZM_SERVER_ID});
              } elsif ( $temp_value eq 'NULL' ) {
                $value = $temp_value;
              } else {
                $value = "'$temp_value'";
                # This gets used later, I forget for what
                $$self{Server} = new ZoneMinder::Server($temp_value);
              }
            } elsif ( $term->{attr} eq 'StorageId' ) {
              # Empty means NULL, otherwise must be an integer
              $value = $temp_value ne '' ? int($temp_value) : 'NULL';
              $$self{Storage} = new ZoneMinder::Storage($temp_value);
            } elsif ( $term->{attr} eq 'Name'
              || $term->{attr} eq 'Cause'
              || $term->{attr} eq 'Notes'
            ) {
              if ( $term->{op} eq 'LIKE'
                || $term->{op} eq 'NOT LIKE'
              ) {
                $temp_value = '%'.$temp_value.'%' if $temp_value !~ /%/;
              }
              $value = "'$temp_value'";
            } elsif ( $term->{attr} eq 'DateTime' or $term->{attr} eq 'StartDateTime' or $term->{attr} eq 'EndDateTime' ) {
              if ( $temp_value eq 'NULL' ) {
                $value = $temp_value;
              } else {
                $value = DateTimeToSQL($temp_value);
                if ( !$value ) {
                  Error("Error parsing date/time '$temp_value', skipping filter '$self->{Name}'");
                  return;
                }
                $value = "'$value'";
              }
            } elsif ( $term->{attr} eq 'Date' or $term->{attr} eq 'StartDate' or $term->{attr} eq 'EndDate' ) {
              if ( $temp_value eq 'NULL' ) {
                $value = $temp_value;
              } elsif ( $temp_value eq 'CURDATE()' or $temp_value eq 'NOW()' ) {
                $value = 'to_days('.$temp_value.')';
              } else {
                $value = DateTimeToSQL($temp_value);
                if ( !$value ) {
                  Error("Error parsing date/time '$temp_value', skipping filter '$self->{Name}'");
                  return;
                }
                $value = "to_days( '$value' )";
              }
            } elsif ( $term->{attr} eq 'Time' or $term->{attr} eq 'StartTime' or $term->{attr} eq 'EndTime' ) {
              if ( $temp_value eq 'NULL' ) {
                $value = $temp_value;
              } else {
                $value = DateTimeToSQL($temp_value);
                if ( !$value ) {
                  Error("Error parsing date/time '$temp_value', skipping filter '$self->{Name}'");
                  return;
                }
                $value = "extract( hour_second from '$value' )";
              }
            } else {
              $value = $temp_value;
            }
            push @value_list, $value;
          } # end foreach temp_value

          if ( $term->{op} ) {
            if ( $term->{op} eq '=~' ) {
              $self->{Sql} .= ' REGEXP '.$value;
            } elsif ( $term->{op} eq '!~' ) {
              $self->{Sql} .= ' NOT REGEXP '.$value;
            } elsif ( $term->{op} eq 'IS' ) {
              if ( $value eq 'Odd' ) {
                $self->{Sql} .= ' % 2 = 1';
              } elsif ( $value eq 'Even' ) {
                $self->{Sql} .= ' % 2 = 0';
              } else {
                $self->{Sql} .= ' IS '.$value;
              }
            } elsif ( $term->{op} eq 'EXISTS' ) {
              $self->{Sql} .= ' EXISTS '.$value;
            } elsif ( $term->{op} eq 'IS NOT' ) {
              $self->{Sql} .= ' IS NOT '.$value;
            } elsif ( $term->{op} eq '=[]' or $term->{op} eq 'IN' ) {
              $self->{Sql} .= ' IN ('.join(',', @value_list).")";
            } elsif ( $term->{op} eq '![]' ) {
              $self->{Sql} .= ' NOT IN ('.join(',', @value_list).')';
            } elsif ( $term->{op} eq 'LIKE' ) {
              $self->{Sql} .= ' LIKE '.$value;
            } elsif ( $term->{op} eq 'NOT LIKE' ) {
              $self->{Sql} .= ' NOT LIKE '.$value;
            } else {
              $self->{Sql} .= ' '.$term->{op}.' '.$value;
            }
          } # end if has an operator
        } # end if Pre/Post or SQL
        $self->{Sql} .= ' '.str_repeat(')', $term->{cbr}) if exists($term->{cbr});
        $self->{Sql} .= "\n";
      } # end foreach term
    } # end if terms

    if ( $self->{Sql} ) {
# Include all events, including events that are still ongoing
# and have no EndTime yet
      $sql .= ' WHERE ( '.$self->{Sql}.' )';
    }
    my @auto_terms;
    if ( $self->{AutoArchive} ) {
      push @auto_terms, 'E.Archived = 0';
    }
    if ( $self->{AutoUnarchive} ) {
      push @auto_terms, 'E.Archived = 1';
    }
    # Don't do this, it prevents re-generation and concatenation.
    # If the file already exists, then the video won't be re-recreated
    if ( $self->{AutoVideo} ) {
      push @auto_terms, 'E.Videoed = 0';
    }
    if ( $self->{AutoUpload} ) {
      push @auto_terms, 'E.Uploaded = 0';
    }
    if ( $self->{AutoEmail} ) {
      push @auto_terms, 'E.Emailed = 0';
    }
    if ( $self->{AutoMessage} ) {
      push @auto_terms, 'E.Messaged = 0';
    }
    if ( $self->{AutoExecute} ) {
      push @auto_terms, 'E.Executed = 0';
    }
    if ( @auto_terms ) {
      $sql .= ' AND ( '.join(' or ', @auto_terms).' )';
    }

    my $sort_column = '';
    if ( $filter_expr->{sort_field} eq 'Id' ) {
      $sort_column = 'E.Id';
    } elsif ( $filter_expr->{sort_field} eq 'MonitorName' ) {
            $sql = 'SELECT E.*, unix_timestamp(E.StartDateTime) as Time, M.Name as MonitorName
         FROM Events as E INNER JOIN Monitors as M on M.Id = E.MonitorId';
      $sort_column = 'M.Name';
    } elsif ( $filter_expr->{sort_field} eq 'Name' ) {
      $sort_column = 'E.Name';
    } elsif ( $filter_expr->{sort_field} eq 'StartDateTime' ) {
      $sort_column = 'E.StartDateTime';
    } elsif ( $filter_expr->{sort_field} eq 'StartTime' ) {
      $sort_column = 'E.StartDateTime';
    } elsif ( $filter_expr->{sort_field} eq 'EndTime' ) {
      $sort_column = 'E.EndDateTime';
    } elsif ( $filter_expr->{sort_field} eq 'EndDateTime' ) {
      $sort_column = 'E.EndDateTime';
    } elsif ( $filter_expr->{sort_field} eq 'Secs' ) {
      $sort_column = 'E.Length';
    } elsif ( $filter_expr->{sort_field} eq 'Frames' ) {
      $sort_column = 'E.Frames';
    } elsif ( $filter_expr->{sort_field} eq 'AlarmFrames' ) {
      $sort_column = 'E.AlarmFrames';
    } elsif ( $filter_expr->{sort_field} eq 'TotScore' ) {
      $sort_column = 'E.TotScore';
    } elsif ( $filter_expr->{sort_field} eq 'AvgScore' ) {
      $sort_column = 'E.AvgScore';
    } elsif ( $filter_expr->{sort_field} eq 'MaxScore' ) {
      $sort_column = 'E.MaxScore';
    } elsif ( $filter_expr->{sort_field} eq 'DiskSpace' ) {
      $sort_column = 'E.DiskSpace';
    } elsif ( $filter_expr->{sort_field} ne '' ) {
      $sort_column = 'E.'.$filter_expr->{sort_field};
    }
    if ( $sort_column ne '' ) {
      $sql .= ' ORDER BY '.$sort_column.' '.($filter_expr->{sort_asc} ? 'ASC' : 'DESC');
    }
    if ($filter_expr->{limit}) {
      $sql .= ' LIMIT 0,'.$filter_expr->{limit};
    }
    if ($$self{LockRows}) {
      $sql .= ' FOR UPDATE';
      if ($filter_expr->{skip_locked}) {
        $sql .= ' SKIP LOCKED';
      }
    }
    $self->{Sql} = $sql;
  } # end if has Sql
  return $self->{Sql};
} # end sub Sql

sub getDiskPercent {
  my $command = 'df ' . ($_[0] ? $_[0] : '.');
  my $df = qx( $command );
  my $space = -1;
  if ( $df =~ /\s(\d+)%/ms ) {
    $space = $1;
  }
  return $space;
}

sub getDiskBlocks {
  my $command = 'df ' . ($_[0] ? $_[0] : '.');
  my $df = qx( $command );
  my $space = -1;
  if ( $df =~ /\s(\d+)\s+\d+\s+\d+%/ms ) {
    $space = $1;
  }
  return $space;
}

sub getLoad {
  my $command = 'uptime .';
  my $uptime = qx( $command );
  my $load = -1;
  if ( $uptime =~ /load average:\s+([\d.]+)/ms ) {
    $load = $1;
    Info("Load: $load");
  }
  return $load;
}

#
# More or less replicates the equivalent PHP function
#
sub strtotime {
  my $dt_str = shift;
  return Date::Manip::UnixDate($dt_str, '%s');
}

#
# More or less replicates the equivalent PHP function
#
sub str_repeat {
  my $string = shift;
  my $count = shift;
  return ${string}x${count};
}

# Formats a date into MySQL format
sub DateTimeToSQL {
  my $dt_str = shift;
  my $dt_val = strtotime($dt_str);
  if ( !$dt_val ) {
    Error("Unable to parse date string '$dt_str'");
    return undef;
  }
  return POSIX::strftime('%Y-%m-%d %H:%M:%S', localtime($dt_val));
}

sub User {
  my $self = shift;
  $$self{User} = shift if @_;
  if ( ! $$self{User} and $$self{UserId} ) {
    $$self{User} = ZoneMinder::User->find_one(Id=>$$self{UserId});
  }
  return $$self{User};
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

use ZoneMinder::Filter;
blah blah blah

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

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
