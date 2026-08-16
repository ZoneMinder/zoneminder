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
require POSIX;
use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);
require ZoneMinder::Storage;
require ZoneMinder::Server;

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields /;
$table = 'Filters';
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
EmailServer
EmailFormat
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

  my $sth = $ZoneMinder::Database::dbh->prepare($sql);
  if (!$sth) {
    Error("Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr());
    return;
  }
  my $res = $sth->execute();
  if ( !$res ) {
    Error("Can't execute filter '$sql', ignoring: ".$sth->errstr());
    return;
  }
  Debug("Filter::Execute SQL ($sql)");
  my @results;
  while ( my $event = $sth->fetchrow_hashref() ) {
    push @results, $event;
  }
  $sth->finish();
  Debug('Loaded ' . @results . ' events for filter '.$$self{Name}.' using query ('.$sql.')"');
  if ($self->{PostSQLConditions} and @{$self->{PostSQLConditions}}) {
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
    $self->{PostSQLConditions} = [];
    $self->{HasDiskPercent} = 0;
    $self->{HasDiskBlocks} = 0;
    $self->{HasSystemLoad} = 0;
    if ( !$self->{Query_json} ) {
      Warning('No query in Filter!');
      return;
    }

    my $filter_expr = ZoneMinder::General::jsonDecode($self->{Query_json});
    my $fields = 'E.*, unix_timestamp(E.StartDateTime) AS Time';
    my $from = 'Events AS E';

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

        # Date attrs (Date/StartDate/EndDate) emit sargable range queries
        # against E.StartDateTime / E.EndDateTime instead of wrapping the
        # column in to_days(), which would prevent index use.  See
        # _dateRangeSQL below.
        my $date_column = '';
        if ( $term->{attr} eq 'Date' or $term->{attr} eq 'StartDate' ) {
          $date_column = 'E.StartDateTime';
        } elsif ( $term->{attr} eq 'EndDate' ) {
          $date_column = 'E.EndDateTime';
        }

        if ( $term->{attr} eq 'AlarmedZoneId' ) {
          $term->{op} = 'EXISTS';
        } elsif ( $term->{attr} eq 'Tags' ) {
          $fields .= ', (SELECT GROUP_CONCAT(Name) FROM Tags WHERE Id IN (SELECT TagId FROM Events_Tags WHERE Events_Tags.EventId=E.Id)) As Tags';
          # Don't prepend T.Id for special tag values (0="No Tag", -1="Any Tag")
          # as those use EXISTS/NOT EXISTS subqueries instead
          if (!defined($term->{val}) or ($term->{val} ne '0' and $term->{val} ne '-1')) {
            $self->{Sql} .= 'T.Id';
          }
          $from .= ' LEFT JOIN Events_Tags AS ET ON E.Id = ET.EventId LEFT JOIN Tags AS T ON T.Id = ET.TagId';
        } elsif ( $term->{attr} =~ /^Monitor/ ) {
          if (!($fields =~ /MonitorName/)) {
            $fields .= ', M.Name as MonitorName';
            $from .= ' INNER JOIN Monitors as M on M.Id = E.MonitorId';
          }
          my ( $temp_attr_name ) = $term->{attr} =~ /^Monitor(.+)$/;
          $self->{Sql} .= 'M.'.($temp_attr_name ? $temp_attr_name : 'Id');
        } elsif ( $term->{attr} eq 'ServerId' or $term->{attr} eq 'MonitorServerId' ) {
          if (!($fields =~ /MonitorName/)) {
            $fields .= ', M.Name as MonitorName';
            $from .= ' INNER JOIN Monitors as M on M.Id = E.MonitorId';
          }
          $self->{Sql} .= 'M.ServerId';
        } elsif ( $term->{attr} eq 'StorageServerId' ) {
          $self->{Sql} .= '(SELECT Storage.ServerId FROM Storage WHERE Storage.Id=E.StorageId)';
        } elsif ( $term->{attr} eq 'FilterServerId' ) {
          $self->{Sql} .= (defined($Config{ZM_SERVER_ID}) ? $Config{ZM_SERVER_ID}: '0').' /* ZM_SERVER_ID */';
          # StartTime options
        } elsif ( $term->{attr} eq 'CurrentDateTime' ) {
          $self->{Sql} .= 'NOW()';
        } elsif ( $term->{attr} eq 'CurrentTime' ) {
          $self->{Sql} .= 'extract( hour_second from NOW())';
        } elsif ( $term->{attr} eq 'CurrentDate' ) {
          $self->{Sql} .= 'to_days(NOW())';
        } elsif ( $term->{attr} eq 'DateTime' ) {
          # Mirror web/includes/FilterTerm.php: DateTime is an "event overlaps
          # this instant/window" idiom, not a plain StartDateTime comparison. A
          # lower bound (>=/>) is satisfied by an event still running at that
          # time, so compare against EndDateTime (NULL end = ongoing = never
          # ends). An upper bound (<=/</=) is satisfied by an event that had
          # already started, so compare against StartDateTime. Keep this in sync
          # with the PHP so the web UI and the zmfilter.pl daemon select the
          # same events. refs #4976
          if ( ($term->{op}//'') eq '>=' or ($term->{op}//'') eq '>' ) {
            $self->{Sql} .= "COALESCE(E.EndDateTime, '9999-12-31 23:59:59')";
          } else {
            $self->{Sql} .= 'E.StartDateTime';
          }
        } elsif ( $term->{attr} eq 'Date' ) {
          # column emitted as part of range expression below
        } elsif ( $term->{attr} eq 'StartDate' ) {
          # column emitted as part of range expression below
        } elsif ( $term->{attr} eq 'Time' or $term->{attr} eq 'StartTime' ) {
          $self->{Sql} .= 'extract( hour_second from E.StartDateTime )';
        } elsif ( $term->{attr} eq 'Weekday' or $term->{attr} eq 'StartWeekday' ) {
          $self->{Sql} .= 'weekday( E.StartDateTime )';

          # EndTime options
        } elsif ( $term->{attr} eq 'EndDateTime' ) {
          $self->{Sql} .= 'E.EndDateTime';
        } elsif ( $term->{attr} eq 'EndDate' ) {
          # column emitted as part of range expression below
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

        my $value = defined($term->{val}) ? $term->{val} : '';
        my @value_list;

        if ( $term->{attr} eq 'ExistsInFileSystem' ) {
          # PostCondition, so no further SQL
        } else {
          my $stripped_value = $value;
          $stripped_value =~ s/^["\']+?(.+)["\']+?$/$1/ if $stripped_value;

          # Empty value will result in () from split
          foreach my $temp_value ( $stripped_value ne '' ? split( /["'\s]*?,["'\s]*?/, $stripped_value ) : $stripped_value ) {
            if ( $term->{attr} eq 'AlarmedZoneId' ) {
              $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND Score > 0 AND ZoneId='.$value.')';
            } elsif ( $term->{attr} =~ /^MonitorName/ ) {
              $value = "'$temp_value'";
            } elsif (
              $term->{attr} eq 'ServerId' or
              $term->{attr} eq 'MonitorServerId' or
              $term->{attr} eq 'StorageServerId' or
              $term->{attr} eq 'FilterServerId' ) {
              if ( $temp_value eq 'ZM_SERVER_ID' ) {
                $value = "'$ZoneMinder::Config::Config{ZM_SERVER_ID}'";
                # This gets used later, I forget for what
                $$self{Server} = new ZoneMinder::Server($ZoneMinder::Config::Config{ZM_SERVER_ID});
              } elsif ( uc($temp_value) eq 'NULL' ) {
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
              || $term->{attr} eq 'Tags'
            ) {
              if ( $term->{op} eq 'LIKE'
                || $term->{op} eq 'NOT LIKE'
              ) {
                $temp_value = '%'.$temp_value.'%' if $temp_value !~ /%/;
              }
              $value = "'$temp_value'";
            } elsif ( $term->{attr} eq 'DateTime' or $term->{attr} eq 'StartDateTime' or $term->{attr} eq 'EndDateTime' or $term->{attr} eq 'CurrentDateTime') {
              if ( uc($temp_value) eq 'NULL' ) {
                $value = $temp_value;
              } else {
                $value = DateTimeToSQL($temp_value);
                if ( !$value ) {
                  Error("Error parsing date/time '$temp_value', skipping filter '$self->{Name}'");
                  return;
                }
                $value = "'$value'";
              }
            } elsif ( $term->{attr} eq 'Date' or $term->{attr} eq 'StartDate' or $term->{attr} eq 'EndDate' or $term->{attr} eq 'CurrentDate') {
              if ( uc($temp_value) eq 'NULL' ) {
                $value = $temp_value;
              } elsif ( $temp_value eq 'CURDATE()' or $temp_value eq 'NOW()' ) {
                # For Date/StartDate/EndDate the value is consumed by
                # _dateRangeSQL below; leave it raw.  For CurrentDate
                # (left side is to_days(NOW()), a constant), preserve
                # the legacy to_days() wrapping.
                $value = $date_column ? $temp_value : 'to_days('.$temp_value.')';
              } else {
                $value = DateTimeToSQL($temp_value);
                if ( !$value ) {
                  Error("Error parsing date/time '$temp_value', skipping filter '$self->{Name}'");
                  return;
                }
                $value = $date_column ? "'$value'" : "to_days( '$value' )";
              }
            } elsif ( $term->{attr} eq 'Time' or $term->{attr} eq 'StartTime' or $term->{attr} eq 'EndTime' or $term->{attr} eq 'CurrentTime') {
              if ( uc($temp_value) eq 'NULL' ) {
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
            # Date attrs: emit a sargable range expression covering the
            # whole day(s) instead of comparing to_days(col) op to_days(val),
            # which would defeat the index on StartDateTime/EndDateTime.
            if ( $date_column ) {
              $self->{Sql} .= ' '._dateRangeSQL($date_column, $term->{op}, \@value_list);
            }
            # Handle special tag values before generic operators to avoid
            # LEFT JOIN NULL comparison issues with EXISTS/NOT EXISTS
            elsif ( $term->{attr} eq 'Tags' and defined($term->{val}) and $term->{val} eq '0' ) {
              # "No Tag": = means no tags (NOT EXISTS), != means has tags (EXISTS)
              if ($term->{op} eq '!=' or $term->{op} eq 'IS NOT') {
                $self->{Sql} .= 'EXISTS (SELECT NULL FROM `Events_Tags` AS ET WHERE ET.EventId = E.Id)';
              } else {
                $self->{Sql} .= 'NOT EXISTS (SELECT NULL FROM `Events_Tags` AS ET WHERE ET.EventId = E.Id)';
              }
            } elsif ( $term->{attr} eq 'Tags' and defined($term->{val}) and $term->{val} eq '-1' ) {
              # "Any Tag": = means has tags (EXISTS), != means no tags (NOT EXISTS)
              if ($term->{op} eq '!=' or $term->{op} eq 'IS NOT') {
                $self->{Sql} .= 'NOT EXISTS (SELECT NULL FROM `Events_Tags` AS ET WHERE ET.EventId = E.Id)';
              } else {
                $self->{Sql} .= 'EXISTS (SELECT NULL FROM `Events_Tags` AS ET WHERE ET.EventId = E.Id)';
              }
            } elsif ( $term->{op} eq '=~' ) {
              $self->{Sql} .= ' REGEXP '.$value;
            } elsif ( $term->{op} eq '!~' ) {
              $self->{Sql} .= ' NOT REGEXP '.$value;
            } elsif ( $term->{op} eq 'IS' ) {
              if ( $value eq 'Odd' ) {
                $self->{Sql} .= ' % 2 = 1';
              } elsif ( $value eq 'Even' ) {
                $self->{Sql} .= ' % 2 = 0';
              } elsif (uc($value) ne 'NULL') {
                $self->{Sql} .= ' = '.$value;
              } else {
                $self->{Sql} .= ' IS '.$value;
              }
            } elsif ( $term->{op} eq 'EXISTS' ) {
              $self->{Sql} .= ' EXISTS '.$value;
            } elsif ( $term->{op} eq 'IS NOT' ) {
              if (uc($value) ne 'NULL') {
                $self->{Sql} .= ' != '.$value;
              } else {
                $self->{Sql} .= ' IS NOT '.$value;
              }
            } elsif ( $term->{op} eq '=[]' or $term->{op} eq 'IN' ) {
              $self->{Sql} .= ' IN ('.join(',', @value_list).")";
            } elsif ( $term->{op} eq '![]' or $term->{op} eq 'NOT IN') {
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

    my $sql = ' SELECT '.$fields. ' FROM ' . $from;
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
    if ($filter_expr->{sort_field}) {
      if ( $filter_expr->{sort_field} eq 'Id' ) {
        $sort_column = 'E.Id';
      } elsif ( $filter_expr->{sort_field} eq 'Tag' ) {
        $sort_column = 'T.Name';
      } elsif ( $filter_expr->{sort_field} eq 'MonitorName' ) {
        if (!($fields =~ /MonitorName/)) {
          $fields .= ', M.Name as MonitorName';
          $from .= ' INNER JOIN Monitors as M on M.Id = E.MonitorId';
          $sql = ' SELECT '.$fields. ' FROM ' . $from;
          $sql .= ' WHERE ( '.$self->{Sql}.' )' if $self->{Sql};
          $sql .= ' AND ( '.join(' or ', @auto_terms).' )' if @auto_terms;
        }
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
    }
    #$sql .= ' GROUP BY E.Id ';
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
  require Date::Manip;

  Date::Manip::Date_Init("SetDate=now,".$ZoneMinder::Config{ZM_TIMEZONE}) if $ZoneMinder::Config{ZM_TIMEZONE};
  my $dt = Date::Manip::ParseDate($dt_str);
  return Date::Manip::UnixDate($dt, '%s');
}

#
# More or less replicates the equivalent PHP function
#
sub str_repeat {
  my $string = shift;
  my $count = shift;
  return ${string}x${count};
}

# Returns ($day_start, $next_day_start) SQL literals for a date value.
# $value is either 'YYYY-MM-DD HH:MM:SS' (quoted), or CURDATE()/NOW().
sub _dateBounds {
  my $value = shift;
  if ( $value eq 'CURDATE()' or $value eq 'NOW()' ) {
    return ($value, "$value + INTERVAL 1 DAY");
  }
  my $stripped = $value;
  $stripped =~ s/^'(.+)'$/$1/;
  my ($y, $m, $d) = $stripped =~ /^(\d{4})-(\d{2})-(\d{2})/;
  if (!defined $y) {
    Error("_dateBounds: unable to parse '$value'");
    return ($value, $value);
  }
  my $lo = sprintf("'%04d-%02d-%02d 00:00:00'", $y, $m, $d);
  my $next_t = POSIX::mktime(0, 0, 0, $d + 1, $m - 1, $y - 1900);
  my $hi = POSIX::strftime("'%Y-%m-%d 00:00:00'", localtime($next_t));
  return ($lo, $hi);
}

# Emits a sargable WHERE-clause fragment for date-precision comparisons
# against $column.  Values in $value_list are raw SQL literals (quoted
# date strings or CURDATE()/NOW() or 'NULL').
sub _dateRangeSQL {
  my ($column, $op, $value_list) = @_;
  my @values = @$value_list;

  if ( @values == 1 and uc($values[0]) eq 'NULL' ) {
    if ( $op eq 'IS' or $op eq '=' ) {
      return "$column IS NULL";
    } elsif ( $op eq 'IS NOT' or $op eq '!=' ) {
      return "$column IS NOT NULL";
    }
  }

  if ( $op eq 'IN' or $op eq '=[]' ) {
    my @ors;
    for my $v (@values) {
      my ($lo, $hi) = _dateBounds($v);
      push @ors, "($column >= $lo AND $column < $hi)";
    }
    return '('.join(' OR ', @ors).')';
  }
  if ( $op eq 'NOT IN' or $op eq '![]' ) {
    my @ands;
    for my $v (@values) {
      my ($lo, $hi) = _dateBounds($v);
      push @ands, "($column < $lo OR $column >= $hi)";
    }
    return '('.join(' AND ', @ands).')';
  }

  my ($lo, $hi) = _dateBounds($values[0]);
  if ( $op eq '=' )  { return "$column >= $lo AND $column < $hi"; }
  if ( $op eq '!=' ) { return "($column < $lo OR $column >= $hi)"; }
  if ( $op eq '>' )  { return "$column >= $hi"; }
  if ( $op eq '>=' ) { return "$column >= $lo"; }
  if ( $op eq '<' )  { return "$column < $lo"; }
  if ( $op eq '<=' ) { return "$column < $hi"; }
  if ( $op eq 'IS' ) { return "$column >= $lo AND $column < $hi"; }
  if ( $op eq 'IS NOT' ) { return "($column < $lo OR $column >= $hi)"; }

  Warning("_dateRangeSQL: unhandled op '$op', falling back to to_days");
  return "to_days($column) $op $values[0]";
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

Licensed under the GNU General Public License v2 or later; see the COPYING
file distributed with ZoneMinder for the full text.


=cut
