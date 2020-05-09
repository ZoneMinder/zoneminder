# ==========================================================================
#
# ZoneMinder Object Module, $Date$, $Revision$
# Copyright (C) 2001-2017  ZoneMinder LLC
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
package ZoneMinder::Object;

use 5.006;
use strict;
use warnings;
use Time::HiRes qw{ gettimeofday tv_interval }; 
use Carp qw( cluck );

require ZoneMinder::Base;

our @ISA = qw(ZoneMinder::Base);

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use vars qw/ $AUTOLOAD $log $dbh %cache $no_cache/;

*log = \$ZoneMinder::Logger::logger;
*dbh = \$ZoneMinder::Database::dbh;

my $debug = 0;
$no_cache = 0;
use constant DEBUG_ALL=>0;

sub init_cache {
    $no_cache = 0;
    %cache = ();
} # end sub init_cache

sub new {
  my ( $parent, $id, $data ) = @_;

  $cache{$parent} = {} if ! $cache{$parent};
  my $sub_cache = $cache{$parent};

  my $self = {};
  bless $self, $parent;
  no strict 'refs';
  my $primary_key = ${$parent.'::primary_key'};
  if ( ! $primary_key ) {
    Error( 'NO primary_key for type ' . $parent );
    return;
  } # end if

  if ( $id and (!$no_cache) and $$sub_cache{$id} ) {
    if ( $data ) {
      # The reason to use load is if we have overriden it in the object,
      $$sub_cache{$id}->load( $data );
    } 
    return $$sub_cache{$id};
  }

  if ( ( $$self{$primary_key} = $id ) or $data ) {
    #$log->debug("loading $parent $id") if $debug or DEBUG_ALL;
    $self->load( $data );
    if ( !$no_cache ) {
      $$sub_cache{$id} = $self;
    } # end if
  } # end if

  return $self;
} # end sub new

sub load {
  my ( $self, $data ) = @_;
  my $type = ref $self;
  if ( !$data ) {
    no strict 'refs';
    my $table = ${$type.'::table'};
    if ( ! $table ) {
      Error('No table for type '.$type);
      return;
    } # end if
    my $primary_key = ${$type.'::primary_key'};
    if ( !$primary_key ) {
      Error('No primary_key for type '.$type);
      return;
    } # end if

    if ( ! $$self{$primary_key} ) { 
      my ( $caller, undef, $line ) = caller;
      Error("$type ::load called without $primary_key from $caller:$line");
    } else {
      Debug("Loading $type from $table WHERE $primary_key = $$self{$primary_key}");
      $data = $ZoneMinder::Database::dbh->selectrow_hashref("SELECT * FROM `$table` WHERE `$primary_key`=?", {}, $$self{$primary_key});
      if ( !$data ) {
        if ( $ZoneMinder::Database::dbh->errstr ) {
          Error( "Failure to load Object record for $$self{$primary_key}: Reason: " . $ZoneMinder::Database::dbh->errstr );
        } else {
          Debug("No Results Loading $type from $table WHERE $primary_key = $$self{$primary_key}");
        } # end if
				delete $$self{$primary_key};
      } # end if
    } # end if
  } # end if ! $data
  if ( $data and %$data ) {
    @$self{keys %$data} = values %$data;
  } # end if
  return $data;
} # end sub load

sub lock_and_load {
  my ( $self ) = @_;
  my $type = ref $self;

  no strict 'refs';
  my $table = ${$type.'::table'};
  if ( ! $table ) {
    Error('NO table for type '.$type);
    return;
  } # end if
  my $primary_key = ${$type.'::primary_key'};
  if ( !$primary_key ) {
    Error('No primary_key for type ' . $type);
    return;
  } # end if

  if ( !$$self{$primary_key} ) {
    my ( $caller, undef, $line ) = caller;
    Error("$type ::lock_and_load called without $primary_key from $caller:$line");
    return;
  }

  Debug("Lock and Load $type from $table WHERE $primary_key = $$self{$primary_key}");
  my $data = $ZoneMinder::Database::dbh->selectrow_hashref("SELECT * FROM `$table` WHERE `$primary_key`=? FOR UPDATE", {}, $$self{$primary_key});
  if ( ! $data ) {
    if ( $ZoneMinder::Database::dbh->errstr ) {
      Error("Failure to load Object record for $$self{$primary_key}: Reason: ".$ZoneMinder::Database::dbh->errstr);
    } else {
      Debug("No Results Lock and Loading $type from $table WHERE $primary_key = $$self{$primary_key}");
    } # end if
  } # end if
  if ( $data and %$data ) {
    @$self{keys %$data} = values %$data;
  } else {
    Debug("No values Lock and Loading $type from $table WHERE $primary_key = $$self{$primary_key}");
  } # end if
} # end sub lock_and_load


sub save {
	my ( $self, $data, $force_insert ) = @_;

	my $type = ref $self;
	if ( ! $type ) {
		my ( $caller, undef, $line ) = caller;
		$log->error("No type in Object::save. self:$self from  $caller:$line");
	}
	my $local_dbh = eval '$'.$type.'::dbh';
  if ( ! $local_dbh ) {
    $local_dbh = $ZoneMinder::Database::dbh;
    if ( $debug or DEBUG_ALL ) {
      $log->debug("Using global dbh");
    }
  }
	$self->set( $data ? $data : {} );
	if ( $debug or DEBUG_ALL ) {
		if ( $data ) {
			foreach my $k ( keys %$data ) {
				$log->debug("Object::save after set $k => $$data{$k} $$self{$k}");
			}
		}
	}

	my $table = eval '$'.$type.'::table';
	my $fields = eval '\%'.$type.'::fields';
	my $debug = eval '$'.$type.'::debug';
	#$debug = DEBUG_ALL if ! $debug;

	my %sql;
	foreach my $k ( keys %$fields ) {
		$sql{$$fields{$k}} = $$self{$k} if defined $$fields{$k};
	} # end foreach
	if ( ! $force_insert ) {
		$sql{$$fields{updated_on}} = 'NOW()' if exists $$fields{updated_on};
	} # end if
	my $serial = eval '$'.$type.'::serial';
	my @identified_by = eval '@'.$type.'::identified_by';

	my $ac = ZoneMinder::Database::start_transaction( $local_dbh );
	if ( ! $serial ) {
		my $insert = $force_insert;
		my %serial = eval '%'.$type.'::serial';
		if ( ! %serial ) {
$log->debug("No serial") if $debug;
			# No serial columns defined, which means that we will do saving by delete/insert instead of insert/update
			if ( @identified_by ) {
				my $where = join(' AND ', map { '`'.$$fields{$_}.'`=?' } @identified_by );
				if ( $debug ) {
					$log->debug("DELETE FROM `$table` WHERE $where");
				} # end if
				
				if ( ! ( ( $_ = $local_dbh->prepare("DELETE FROM `$table` WHERE $where") ) and $_->execute( @$self{@identified_by} ) ) ) {
					$where =~ s/\?/\%s/g;
					$log->error("Error deleting: DELETE FROM $table WHERE " .  sprintf($where, map { defined $_ ? $_ : 'undef' } ( @$self{@identified_by}) ).'):' . $local_dbh->errstr);
					$local_dbh->rollback();
					ZoneMinder::Database::end_transaction( $local_dbh, $ac );
					return $local_dbh->errstr;
				} elsif ( $debug ) {
					$log->debug("SQL succesful DELETE FROM $table WHERE $where");
				} # end if
			} # end if
			$insert = 1;
		} else {
			foreach my $id ( @identified_by ) {
				if ( ! $serial{$id} ) {
          my ( $caller, undef, $line ) = caller;
					$log->error("$id nor in serial for $type from $caller:$line") if $debug;
					next;
				}
				if ( ! $$self{$id} ) {
          my $s = qq{SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE `table_name` = '$table'};

					($$self{$id}) = ($sql{$$fields{$id}}) = $local_dbh->selectrow_array( $s );
					#($$self{$id}) = ($sql{$$fields{$id}}) = $local_dbh->selectrow_array( q{SELECT nextval('} . $serial{$id} . q{')} );
					$log->debug("SQL statement execution SELECT $s returned $$self{$id}") if $debug or DEBUG_ALL;
					$insert = 1;
				} # end if
			} # end foreach
		} # end if ! %serial

		if ( $insert ) {
			my @keys = keys %sql;
			my $command = "INSERT INTO `$table` (" . join(',', @keys ) . ') VALUES (' . join(',', map { '?' } @sql{@keys} ) . ')';
			if ( ! ( ( $_ = $local_dbh->prepare($command) ) and $_->execute( @sql{@keys} ) ) ) {
				my $error = $local_dbh->errstr;
				$command =~ s/\?/\%s/g;
				$log->error('SQL statement execution failed: ('.sprintf($command, , map { defined $_ ? $_ : 'undef' } ( @sql{@keys}) ).'):' . $local_dbh->errstr);
				$local_dbh->rollback();
				ZoneMinder::Database::end_transaction( $local_dbh, $ac );
				return $error;
			} # end if
			if ( $debug or DEBUG_ALL ) {
				$command =~ s/\?/\%s/g;
				$log->debug('SQL statement execution: ('.sprintf($command, , map { defined $_ ? $_ : 'undef' } ( @sql{@keys} ) ).'):' );
			} # end if
		} else {
			my @keys = keys %sql;
			my $command = "UPDATE `$table` SET " . join(',', map { '`'.$_ . '` = ?' } @keys ) . ' WHERE ' . join(' AND ', map { '`'.$_ . '` = ?' } @$fields{@identified_by} );
			if ( ! ( $_ = $local_dbh->prepare($command) and $_->execute( @sql{@keys,@$fields{@identified_by}} ) ) ) {
				my $error = $local_dbh->errstr;
				$command =~ s/\?/\%s/g;
				$log->error('SQL failed: ('.sprintf($command, , map { defined $_ ? $_ : 'undef' } ( @sql{@keys, @$fields{@identified_by}}) ).'):' . $local_dbh->errstr);
				$local_dbh->rollback();
        ZoneMinder::Database::end_transaction( $local_dbh, $ac );
				return $error;
			} # end if
			if ( $debug or DEBUG_ALL ) {
				$command =~ s/\?/\%s/g;
				$log->debug('SQL DEBUG: ('.sprintf($command, map { defined $_ ? $_ : 'undef' } ( @sql{@keys,@$fields{@identified_by}} ) ).'):' );
			} # end if
		} # end if
	} else { # not identified_by
		@identified_by = ('Id') if ! @identified_by;

    # If the size of the arrays are not equal which means one or more are missing
    my @identified_by_without_values = map { $$self{$_} ? () : $_ } @identified_by;
		my $need_serial = @identified_by_without_values > 0;

		if ( $force_insert or $need_serial ) {
			
			if ( $need_serial ) {
				if ( $serial ) {
          $log->debug("Getting auto_increments");
          my $s = qq{SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE `table_name` = '$table'};
					@$self{@identified_by} = @sql{@$fields{@identified_by}} = $local_dbh->selectrow_array( $s );
#@$self{@identified_by} = @sql{@$fields{@identified_by}} = $local_dbh->selectrow_array( q{SELECT nextval('} . $serial . q{')} );
					if ( $local_dbh->errstr() )  {
						$log->error("Error getting next id. " . $local_dbh->errstr() ."\n".
              "SQL statement execution $s returned ".join(',',@$self{@identified_by}));
					} elsif ( $debug or DEBUG_ALL ) {
						$log->debug("SQL statement execution $s returned ".join(',',@$self{@identified_by}));
					} # end if
				} # end if
			} # end if

			my @keys = keys %sql;
			my $command = "INSERT INTO `$table` (" . join(',', map { '`'.$_.'`' } @keys ) . ') VALUES (' . join(',', map { '?' } @sql{@keys} ) . ')';
			if ( ! ( $_ = $local_dbh->prepare($command) and $_->execute( @sql{@keys} ) ) ) {
				$command =~ s/\?/\%s/g;
				my $error = $local_dbh->errstr;
				$log->error('SQL failed: ('.sprintf($command, map { defined $_ ? $_ : 'undef' } ( @sql{@keys}) ).'):' . $error);
				$local_dbh->rollback();
        ZoneMinder::Database::end_transaction( $local_dbh, $ac );
				return $error;
			} # end if
			if ( $debug or DEBUG_ALL ) {
				$command =~ s/\?/\%s/g;
				$log->debug('SQL DEBUG: ('.sprintf($command, map { defined $_ ? $_ : 'undef' } ( @sql{@keys} ) ).'):' );
			} # end if
		} else {
			delete $sql{created_on};
			my @keys = keys %sql;
      my %identified_by = map { $_, $_ } @identified_by;

			@keys = map { $identified_by{$_} ? () : $$fields{$_} } @keys;
			my $command = "UPDATE `$table` SET " . join(',', map { '`'.$_ . '` = ?' } @keys ) . ' WHERE ' . join(' AND ', map { '`'.$$fields{$_} .'`= ?' } @identified_by );
			if ( ! ( $_ = $local_dbh->prepare($command) and $_->execute( @sql{@keys}, @sql{@$fields{@identified_by}} ) ) ) {
				my $error = $local_dbh->errstr;
				$command =~ s/\?/\%s/g;
				$log->error('SQL failed: ('.sprintf($command, map { defined $_ ? $_ : 'undef' } ( @sql{@keys}, @sql{@$fields{@identified_by}} ) ).'):' . $error) if $log;
				$local_dbh->rollback();
        ZoneMinder::Database::end_transaction( $local_dbh, $ac );
				return $error;
			} # end if
			if ( $debug or DEBUG_ALL ) {
				$command =~ s/\?/\%s/g;
				$log->debug('SQL DEBUG: ('.sprintf($command, map { defined $_ ? ( ref $_ eq 'ARRAY' ? join(',',@{$_}) : $_ ) : 'undef' } ( @sql{@keys}, @$self{@identified_by} ) ).'):' );
			} # end if
		} # end if
	} # end if
  ZoneMinder::Database::end_transaction( $local_dbh, $ac );
  #$self->load();
	#if ( $$fields{id} ) {
		#if ( ! $ZoneMinder::Object::cache{$type}{$$self{id}} ) {
			#$ZoneMinder::Object::cache{$type}{$$self{id}} = $self;
		#} # end if
	#delete $ZoneMinder::Object::cache{$config{db_name}}{$type}{$$self{id}};
	#} # end if
#$log->debug("after delete");
	#eval 'if ( %'.$type.'::find_cache ) { %'.$type.'::find_cache = (); }';
#$log->debug("after clear cache");
	return '';
} # end sub save

sub set {
	my ( $self, $params ) = @_;
	my @set_fields = ();

	my $type = ref $self;
	my %fields = eval ('%'.$type.'::fields');
	if ( ! %fields ) {
		$log->warn("ZoneMinder::Object::set called on an object ($type) with no fields".$@);
	} # end if
	my %defaults = eval('%'.$type.'::defaults');

	if ( ref $params ne 'HASH' ) {
		my ( $caller, undef, $line ) = caller;
		$log->error("$type -> set called with non-hash params from $caller $line");
	}

	foreach my $field ( keys %fields ) {
    if ( $params ) {
      $log->debug("field: $field, param: ".$$params{$field}) if $debug;
      if ( exists $$params{$field} ) {
        $log->debug("field: $field, $$self{$field} =? param: ".$$params{$field}) if $debug;
        if ( ( ! defined $$self{$field} ) or ($$self{$field} ne $params->{$field}) ) {
# Only make changes to fields that have changed
          if ( defined $fields{$field} ) {
            $$self{$field} = $$params{$field} if defined $fields{$field};
            push @set_fields, $fields{$field}, $$params{$field};	#mark for sql updating
          } # end if
          $log->debug("Running $field with $$params{$field}") if $debug;
          if ( my $func = $self->can( $field ) ) {
            $func->( $self, $$params{$field} );
          } # end if
        } # end if
      } # end if
		} # end if $params

		if ( defined $fields{$field} ) {
			if ( $$self{$field} ) {
				$$self{$field} = transform( $type, $field, $$self{$field} );
			} # end if $$self{field}
		}
	} # end foreach field

	foreach my $field ( keys %defaults ) {

		if ( ( ! exists $$self{$field} ) or (!defined $$self{$field}) or ( $$self{$field} eq '' ) ) {
			$log->debug("Setting default ($field) ($$self{$field}) ($defaults{$field}) ") if $debug;
			if ( defined $defaults{$field} ) {
				$log->debug("Default $field is defined: $defaults{$field}") if $debug;
				if ( $defaults{$field} eq 'NOW()' ) {
					$$self{$field} = 'NOW()';
				} else {
					$$self{$field} = eval($defaults{$field});
					$log->error( "Eval error of object default $field default ($defaults{$field}) Reason: " . $@ ) if $@;
				} # end if
			} else {
				$$self{$field} = $defaults{$field};
			} # end if
#$$self{$field} = ( defined $defaults{$field} ) ? eval($defaults{$field}) : $defaults{$field};
			$log->debug("Setting default for ($field) using ($defaults{$field}) to ($$self{$field}) ") if $debug;
		} # end if
	} # end foreach default
	return @set_fields;
} # end sub set

sub transform {
	my $type = ref $_[0];
	$type = $_[0] if ! $type;
	my $fields = eval '\%'.$type.'::fields';
	my $value = $_[2];

	if ( defined $$fields{$_[1]} ) {
		my @transforms = eval('@{$'.$type.'::transforms{$_[1]}}');
		$log->debug("Transforms for $_[1] before $_[2]: @transforms") if $debug;
		if ( @transforms ) {
			foreach my $transform ( @transforms ) {
				if ( $transform =~ /^s\// or $transform =~ /^tr\// ) {
					eval '$value =~ ' . $transform;
				} elsif ( $transform =~ /^<(\d+)/ ) {
					if ( $value > $1 ) {
						$value = undef;
					} # end if
				} else {
	$log->debug("evalling $value ".$transform . " Now value is $value" );
					eval '$value '.$transform;
	$log->error("Eval error $@") if $@;
				}
	$log->debug("After $transform: $value") if $debug;
			} # end foreach
		} # end if 
	} else {
		$log->error("Object::transform ($_[1]) not in fields for $type");
	} # end if
	return $value;

} # end sub transform

sub to_string {
  my $type = ref($_[0]);
  my $fields = eval '\%'.$type.'::fields';
  if ( $fields and %{$fields} ) {
    return $type . ': '. join(' ', map { $_[0]{$_} ? "$_ => $_[0]{$_}" : () } sort { $a cmp $b } keys %$fields );
  }
  return $type . ': '. join(' ', map { $_ .' => '.(defined $_[0]{$_} ? $_[0]{$_} : 'undef') } sort { $a cmp $b } keys %{$_[0]} );
}

# We make this a separate function so that we can use it to generate the sql statements for each value in an OR
sub find_operators {
	my ( $field, $type, $operator, $value ) = @_;
$log->debug("find_operators: field($field) type($type) op($operator) value($value)") if DEBUG_ALL;

my $add_placeholder = ( ! ( $field =~ /\?/ ) ) ?  1 : 0;

	if ( sets::isin( $operator, [ '=', '!=', '<', '>', '<=', '>=', '<<=' ] ) ) {
		return ( $field.$type.' ' . $operator . ( $add_placeholder ? ' ?' : '' ), $value );
	} elsif ( $operator eq 'not' ) {
		return ( '( NOT ' . $field.$type.')', $value );
	} elsif ( sets::isin( $operator, [ '&&', '<@', '@>' ] ) ) {
		if ( ref $value eq 'ARRAY' ) {
			if ( $field =~ /^\(/ ) {
				return ( 'ARRAY('.$field.$type.') ' . $operator . ' ?', $value );
			} else {
				return ( $field.$type.' ' . $operator . ' ?', $value );
			} # emd of
		} else {
			return ( $field.$type.' ' . $operator . ' ?', [ $value ] );
		} # end if
	} elsif ( $operator eq 'exists' ) {
			return ( $value ? '' : 'NOT ' ) . 'EXISTS ' . $field.$type;
	} elsif ( sets::isin( $operator, [ 'in', 'not in' ] ) ) {
		if ( ref $value eq 'ARRAY' ) {
			return ( $field.$type.' ' . $operator . ' ('. join(',', map { '?' } @{$value} ) . ')', @{$value} );
		} else {
			return ( $field.$type.' ' . $operator . ' (?)', $value );
		} # end if
	} elsif ( $operator eq 'contains' ) {
		return ( '? IN '.$field.$type, $value );
	} elsif ( $operator eq 'does not contain' ) {
		return ( '? NOT IN '.$field.$type, $value );
	} elsif ( sets::isin( $operator, [ 'like','ilike' ] ) ) {
		return $field.'::text ' . $operator . ' ?', $value;
	} elsif ( $operator eq 'null_or_<=' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' <= ?)', $value;
	} elsif ( $operator eq 'is null or <=' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' <= ?)', $value;
	} elsif ( $operator eq 'null_or_>=' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' >= ?)', $value;
	} elsif ( $operator eq 'is null or >=' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' >= ?)', $value;
	} elsif ( $operator eq 'null_or_>' or $operator eq 'is null or >' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' > ?)', $value;
	} elsif ( $operator eq 'null_or_<' or $operator eq 'is null or <' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' < ?)', $value;
	} elsif ( $operator eq 'null_or_=' or $operator eq 'is null or =' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' = ?)', $value;
	} elsif ( $operator eq 'null or in' or $operator eq 'is null or in' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' IN ('.join(',', map { '?' } @{$value} ) . '))', @{$value};
	} elsif ( $operator eq 'null or not in' ) {
		return '('.$field.$type.' IS NULL OR '.$field.$type.' NOT IN ('.join(',', map { '?' } @{$value} ) . '))', @{$value};
	} elsif ( $operator eq 'exists' ) {
		return ( $value ? ' EXISTS ' : 'NOT EXISTS ' ).$field;
	} elsif ( $operator eq 'lc' ) {
		return 'lower('.$field.$type.') = ?', $value;
	} elsif ( $operator eq 'uc' ) {
		return 'upper('.$field.$type.') = ?', $value;
	} elsif ( $operator eq 'trunc' ) {
		return 'trunc('.$field.$type.') = ?', $value;
	} elsif ( $operator eq 'any' ) {
		if ( ref $value eq 'ARRAY' ) {
			return '(' . join(',', map { '?' } @{$value} ).") = ANY($field)", @{$value}; 
		} else {
			return "? = ANY($field)", $value;
		} # end if
	} elsif ( $operator eq 'not any' ) {
		if ( ref $value eq 'ARRAY' ) {
			return '(' . join(',', map { '?' } @{$value} ).") != ANY($field)", @{$value}; 
		} else {
			return "? != ANY($field)", $value;
		} # end if
	} elsif ( $operator eq 'is null' ) {
		if ( $value ) {
			return $field.$type. ' is null';
		} else {
			return $field.$type. ' is not null';
		} # end if
	} elsif ( $operator eq 'is not null' ) {
		if ( $value ) {
			return $field.$type. ' is not null';
		} else {
			return $field.$type. ' is null';
		} # end if
	} else {
$log->warn("find_operators: op not found field($field) type($type) op($operator) value($value)");
	} # end if
	return;
} # end sub find_operators

sub get_fields_values {
	my ( $object_type, $search, $param_keys ) = @_;

	my @used_fields;
	my @where;
	my @values;
	no strict 'refs';

	foreach my $k ( @$param_keys ) {
		if ( $k eq 'or' ) {
			my $or_ref = ref $$search{or};

			if ( $or_ref eq 'HASH' ) {
				my @keys = keys %{$$search{or}};
				if ( @keys ) {
					my ( $where, $values, $used_fields ) = get_fields_values( $object_type, $$search{or},  \@keys );

					push @where, '('.join(' OR ', @{$where} ).')';
					push @values, @{$values};
				} else {
					$log->error("No keys in or");
				}

			} elsif ( $or_ref eq 'ARRAY' ) {
				my %s = @{$$search{or}};
				my ( $where, $values, $used_fields ) = get_fields_values( $object_type, \%s,  [ keys %s ] );
				push @where, '('.join(' OR ', @{$where} ).')';
				push @values, @{$values};
				
			} else {
				$log->error("Deprecated use of or $or_ref for $$search{or}");
			} # end if
			push @used_fields, $k;
			next;
		} elsif ( $k eq 'and' ) {
			my $and_ref = ref $$search{and};
			if ( $and_ref eq 'HASH' ) {
				my @keys = keys %{$$search{and}};
				if ( @keys ) {
				my ( $where, $values, $used_fields ) = get_fields_values( $object_type, $$search{and},  \@keys );

				push @where, '('.join(' AND ', @{$where} ).')';
				push @values, @{$values};
				} else {
					$log->error("No keys in and");
				} 
			} elsif ( $and_ref eq 'ARRAY' and @{$$search{and}} ) {
				my @sub_where;

				for( my $p_index = 0; $p_index < @{$$search{and}}; $p_index += 2 ) {
					my %p = ( $$search{and}[$p_index], $$search{and}[$p_index+1] );

					my ( $where, $values, $used_fields ) = get_fields_values( $object_type, \%p, [ keys %p ] );
					push @sub_where, @{$where};
					push @values, @{$values};
				}
				push @where, '('.join(' AND ', @sub_where ).')';
			} else {
				$log->error("incorrect ref of and $and_ref");
			}
			push @used_fields, $k;
			next;
		}
		my ( $field, $type, $function ) = $k =~ /^([_\+\w\-]+)(::\w+\[?\]?)?[\s_]*(.*)?$/;
		$type = '' if ! defined $type;
$log->debug("$object_type param $field($type) func($function) " . ( ref $$search{$k} eq 'ARRAY' ? join(',',@{$$search{$k}}) : $$search{$k} ) ) if DEBUG_ALL;

		foreach ( 'find_fields', 'fields' ) {
			my $fields = \%{$object_type.'::'.$_};
			if ( ! $fields ) {
				$log->debug("No $fields in $object_type") if DEBUG_ALL;
				next;
			} # end if

			if ( ! $$fields{$field} ) {
				#$log->debug("No $field in $_ for $object_type") if DEBUG_ALL;
				next;
			} # end if

# This allows mainly for find_fields to reference multiple values, opinion in Project, value
			foreach my $db_field ( ref $$fields{$field} eq 'ARRAY' ? @{$$fields{$field}} : $$fields{$field} ) {
				if ( ! $function ) {
					$db_field .= $type;

					if ( ref $$search{$k} eq 'ARRAY' ) {
$log->debug("Have array for $k $$search{$k}") if DEBUG_ALL;
						
						if ( ! ( $db_field =~ /\?/ ) ) {
							if ( @{$$search{$k}} != 1 ) {
								push @where, $db_field .' IN ('.join(',', map {'?'} @{$$search{$k}} ) . ')';
							} else {
								push @where, $db_field.'=?';
							} # end if
						} else {
$log->debug("Have question ? for $k $$search{$k} $db_field") if DEBUG_ALL;

							$db_field =~ s/=/IN/g;
							my $question_replacement = '('.join(',', map {'?'} @{$$search{$k}} ) . ')';
							$db_field =~ s/\?/$question_replacement/;
							push @where, $db_field;
						}
						push @values, @{$$search{$k}};
					} elsif ( ref $$search{$k} eq 'HASH' ) {
						foreach my $p_k ( keys %{$$search{$k}} ) {
							my $v = $$search{$k}{$p_k};
							if ( ref $v eq 'ARRAY' ) {
								push @where, $db_field.' IN ('.join(',', map {'?'} @{$v} ) . ')';
								push @values, $p_k, @{$v};
							} else {
								push @where, $db_field.'=?';
								push @values, $p_k, $v;
							} # end if
						} # end foreach p_k
					} elsif ( ! defined $$search{$k} ) {
						push @where, $db_field.' IS NULL';
					} else {
						if ( ! ( $db_field =~ /\?/ ) ) {
							push @where, $db_field .'=?';
						} else {
							push @where, $db_field;
						}
						push @values, $$search{$k};
					} # end if
					push @used_fields, $k;
				} else {
					#my @w = 
#ref $search{$k} eq 'ARRAY' ? 
						#map { find_operators( $field, $type, $function, $_ ); } @{$search{$k}} :
					my ( $w, @v ) = find_operators( $db_field, $type, $function, $$search{$k} );
					if ( $w ) {
						#push @where, '(' . join(' OR ', @w ) . ')';
						push @where, $w;
						push @values, @v if @v;
						push @used_fields, $k;
					} # end if @w
				} # end if has function or not
			} # end foreach db_field
		} # end foreach find_field
	} # end foreach k
	return ( \@where, \@values, \@used_fields );
}

sub find {
	no strict 'refs';
	my $object_type = shift;
	my $debug = ${$object_type.'::debug'};
	$debug = DEBUG_ALL if ! $debug;

	my $starttime = [gettimeofday] if $debug;
	my $params;
	if ( @_ == 1 ) {
		$params = $_[0];
		if ( ref $params ne 'HASH' ) {
			$log->error("params $params was not a has");
		} # end if
	} else {
		$params = { @_ };
	} # end if

	my $local_dbh = ${$object_type.'::dbh'};
	if ( $$params{dbh} ) {
		$local_dbh = $$params{dbh};
		delete $$params{dbh};
	} elsif ( ! $local_dbh ) {
		$local_dbh = $dbh if ! $local_dbh;
	} # end if

	my $sql = find_sql( $object_type, $params);

	my $do_cache = $$sql{columns} ne '*' ? 0 : 1;

#$log->debug( 'find prepare: ' . sprintf('%.4f', tv_interval($starttime)*1000) ." useconds") if $debug;
	my $data = $local_dbh->selectall_arrayref($$sql{sql}, { Slice => {} }, @{$$sql{values}});
	if ( ! $data ) {
		$log->error('Error ' . $local_dbh->errstr() . " loading $object_type ($$sql{sql}) (". join(',', map { ref $_ eq 'ARRAY' ? 'ARRAY('.join(',',@$_).')' : $_ } @{$$sql{values}} ) . ')' );
		return ();
	#} elsif ( ( ! @$data ) and $debug ) {
		#$log->debug("No $type ($sql) (@values) " );
	} elsif ( $debug ) {
		$log->debug("Loading Debug:$debug $object_type ($$sql{sql}) (".join(',', map { ref $_ eq 'ARRAY' ? join(',', @{$_}) : $_ } @{$$sql{values}}).') # of results:' . @$data . ' in ' . sprintf('%.4f', tv_interval($starttime)*1000) .' useconds' );
	} # end if

	my $fields = \%{$object_type.'::fields'};
  my $primary_key = ${$object_type.'::primary_key'};
  if ( ! $primary_key ) {
    Error( 'NO primary_key for type ' . $object_type );
    return;
  } # end if
  if ( ! ($fields and keys %{$fields}) ) {
    return map { new($object_type, $$_{$primary_key}, $_ ) } @$data;
  } elsif ( $$fields{$primary_key} ) {
		return map { new($object_type, $_->{$$fields{$primary_key}}, $_) } @$data;
	} else {
		my @identified_by = eval '@'.$object_type.'::identified_by';
		if ( ! @identified_by ) {
			$log->debug("Multi key object $object_type but no identified by $fields") if $debug;
		} # end if
		return map { new($object_type, \@identified_by, $_, !$do_cache) } @$data;
	} # end if
} # end sub find

sub find_one {
	my $object_type = shift;
	my $params;
	if ( @_ == 1 ) {
		$params = $_[0];
	} else {
		%{$params} = @_;
	} # end if
	$$params{limit}=1;
	my @Results = $object_type->find(%$params);
	my ( $caller, undef, $line ) = caller;
$log->debug("returning to $caller:$line from find_one") if DEBUG_ALL;
	return $Results[0] if @Results;
} # end sub find_one

sub find_sql {
	no strict 'refs';
	my $object_type = shift;

	my $debug = ${$object_type.'::debug'};
	$debug = DEBUG_ALL if ! $debug;

	my $params;
	if ( @_ == 1 ) {
		$params = $_[0];
		if ( ref $params ne 'HASH' ) {
			$log->error("params $params was not a has");
		} # end if
	} else {
		$params = { @_ };
	} # end if

	my %sql = (
		( distinct => ( exists $$params{distinct} ? 1:0 ) ),
		( columns => ( exists $$params{columns} ? $$params{columns} : '*' ) ),
		( table => ( exists $$params{table} ? $$params{table} : ${$object_type.'::table'} )),
		'group by'=> $$params{'group by'},
		limit => $$params{limit},
		offset => $$params{offset},
	);
	if ( exists $$params{order} ) {
		$sql{order} = $$params{order};
	} else {
		my $order = eval '$'.$object_type.'::default_sort';
#$log->debug("default sort: $object_type :: default_sort = $order") if DEBUG_ALL;
		$sql{order} = $order if $order;
	} # end if
	delete @$params{'distinct','columns','table','group by','limit','offset','order'};
	
	my @where;
	my @values;
	if ( exists $$params{custom} ) {
		push @where, '(' . (shift @{$$params{custom}}) . ')';
		push @values, @{$$params{custom}};
		delete $$params{custom};
	} # end if

	my @param_keys = keys %$params;

	# no operators, just which fields are being searched on. Mostly just useful for detetion of the deleted field.
	my %used_fields;

	# We use this search hash so that we can mash it up and leave the params hash alone
	my %search;
	@search{@param_keys} = @$params{@param_keys};

	my ( $where, $values, $used_fields ) = get_fields_values( $object_type, \%search, \@param_keys );
	delete @search{@{$used_fields}};
	@used_fields{ @{$used_fields} } = @{$used_fields};
	push @where, @{$where};
	push @values, @{$values};

	my $fields = \%{$object_type.'::fields'};

#optimise this
	if ( $$fields{deleted} and ! $used_fields{deleted} ) {
		push @where, 'deleted=?';
		push @values, 0;
	} # end if
	$sql{where} = \@where;
	$sql{values} = \@values;
	$sql{used_fields} = \%used_fields;

	foreach my $k ( keys %search ) {
		$log->error("Extra parameters in $object_type ::find $k => $search{$k}");
		Carp::cluck("Extra parameters in $object_type ::find $k => $search{$k}");
	} # end foreach

	$sql{sql} = join( ' ',
			( 'SELECT', ( $sql{distinct} ? ('DISTINCT') : () ) ),
			( $sql{columns}, 'FROM', $sql{table} ),
			( @{$sql{where}} ? ('WHERE', join(' AND ', @{$sql{where}})) : () ),
			( $sql{order} ? ( 'ORDER BY', $sql{order} ) : () ),
			( $sql{'group by'} ? ( 'GROUP BY', $sql{'group by'} ) : () ),
			( $sql{limit} ? ( 'LIMIT', $sql{limit}) : () ),
			( $sql{offset} ? ( 'OFFSET', $sql{offset} ) : () ),
	);	
	#$log->debug("Loading Debug:$debug $object_type ($sql) (".join(',', map { ref $_ eq 'ARRAY' ? join(',', @{$_}) : $_ } @values).')' ) if $debug;
	return \%sql;
} # end sub find_sql

sub AUTOLOAD {
  my $type = ref($_[0]);
  Carp::cluck("No type in autoload") if ! $type;
  if ( DEBUG_ALL ) {
    Carp::cluck("Using AUTOLOAD $AUTOLOAD");
  }
  my $name = $AUTOLOAD;
  $name =~ s/.*://;
  if ( @_ > 1 ) {
    return $_[0]{$name} = $_[1];
  }
  return $_[0]{$name};
}

sub DESTROY {
}

1;
__END__

# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Object

=head1 SYNOPSIS

  use parent ZoneMinder::Object;
  
  This package should likely not be used directly, as it is meant mainly to be a parent for all other ZoneMinder classes.

=head1 DESCRIPTION

  A base Object to act as parent for other ZoneMinder Objects.

=head2 EXPORT

None by default.

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
