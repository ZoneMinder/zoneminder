# ==========================================================================
#
# ZoneMinder Config Admin Module, $Date$, $Revision$
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# ==========================================================================
#
# This module contains the debug definitions and functions used by the rest 
# of the ZoneMinder scripts
#
package ZoneMinder::ConfigAdmin;

use 5.006;
use strict;
use warnings;

require Exporter;
require ZoneMinder::Base;

our @ISA = qw(Exporter ZoneMinder::Base);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration	use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
	'functions' => [ qw(
		loadConfigFromDB
		saveConfigToDB
	) ]
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'functions'} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Configuration Administration
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::ConfigData qw(:all);

use Carp;

sub loadConfigFromDB
{
	print( "Loading config from DB\n" );
	my $dbh = DBI->connect( "DBI:mysql:database=".$Config{ZM_DB_NAME}.";host=".$Config{ZM_DB_HOST}, $Config{ZM_DB_USER}, $Config{ZM_DB_PASS} );
	
	if ( !$dbh )
	{
		print( "Error: unable to load options from database: $DBI::errstr\n" );
		return( 0 );
	}
	my $sql = "select * from Config";
	my $sth = $dbh->prepare_cached( $sql ) or croak( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute() or croak( "Can't execute: ".$sth->errstr() );
	my $option_count = 0;
	while( my $config = $sth->fetchrow_hashref() )
	{
		my ( $name, $value ) = ( $config->{Name}, $config->{Value} );
		#print( "Name = '$name'\n" );
		my $option = $options_hash{$name};
		if ( !$option )
		{
			warn( "No option '$name' found, removing" );
			next;
		}
		#next if ( $option->{category} eq 'hidden' );
		if ( defined($value) )
		{
			if ( $option->{type} == $types{boolean} )
			{
				$option->{value} = $value?"yes":"no";
			}
			else
			{
				$option->{value} = $value;
			}
		}
		$option_count++;;
	}
	$sth->finish();
	$dbh->disconnect();
	return( $option_count );
}

sub saveConfigToDB
{
	print( "Saving config to DB\n" );
	my $dbh = DBI->connect( "DBI:mysql:database=".$Config{ZM_DB_NAME}.";host=".$Config{ZM_DB_HOST}, $Config{ZM_DB_USER}, $Config{ZM_DB_PASS} );

	if ( !$dbh )
	{
		print( "Error: unable to save options to database: $DBI::errstr\n" );
		return( 0 );
	}

    my $ac = $dbh->{AutoCommit};
    $dbh->{AutoCommit} = 0;

    $dbh->do('LOCK TABLE Config WRITE') or croak( "Can't lock Config table: " . $dbh->errstr() );

	my $sql = "delete from Config";
	my $res = $dbh->do( $sql ) or croak( "Can't do '$sql': ".$dbh->errstr() );

	$sql = "replace into Config set Id = ?, Name = ?, Value = ?, Type = ?, DefaultValue = ?, Hint = ?, Pattern = ?, Format = ?, Prompt = ?, Help = ?, Category = ?, Readonly = ?, Requires = ?";
	my $sth = $dbh->prepare_cached( $sql ) or croak( "Can't prepare '$sql': ".$dbh->errstr() );
	foreach my $option ( @options )
	{
		#next if ( $option->{category} eq 'hidden' );
		#print( $option->{name}."\n" ) if ( !$option->{category} );
		$option->{db_type} = $option->{type}->{db_type};
		$option->{db_hint} = $option->{type}->{hint};
		$option->{db_pattern} = $option->{type}->{pattern};
		$option->{db_format} = $option->{type}->{format};
		if ( $option->{db_type} eq "boolean" )
		{
			$option->{db_value} = ($option->{value} eq "yes")?"1":"0";
		}
		else
		{
			$option->{db_value} = $option->{value};
		}
		if ( my $requires = $option->{requires} )
		{
			$option->{db_requires} = join( ";", map { my $value = $_->{value}; $value = ($value eq "yes")?1:0 if ( $options_hash{$_->{name}}->{db_type} eq "boolean" ); ( "$_->{name}=$value" ) } @$requires );
		}
		else
		{
		}
		my $res = $sth->execute( $option->{id}, $option->{name}, $option->{db_value}, $option->{db_type}, $option->{default}, $option->{db_hint}, $option->{db_pattern}, $option->{db_format}, $option->{description}, $option->{help}, $option->{category}, $option->{readonly}?1:0, $option->{db_requires} ) or croak( "Can't execute: ".$sth->errstr() );
	}
	$sth->finish();

    $dbh->do('UNLOCK TABLES');
    $dbh->{AutoCommit} = $ac;

	$dbh->disconnect();
}

1;
__END__

=head1 NAME

ZoneMinder::ConfigAdmin - ZoneMinder Configuration Administration module

=head1 SYNOPSIS

  use ZoneMinder::ConfigAdmin;
  use ZoneMinder::ConfigAdmin qw(:all);

  loadConfigFromDB();
  saveConfigToDB();

=head1 DESCRIPTION

The ZoneMinder:ConfigAdmin module contains the master definition of the ZoneMinder configuration options as well as helper methods. This module is intended for specialist confguration management and would not normally be used by end users.

The configuration held in this module, which was previously in zmconfig.pl, includes the name, default value, description, help text, type and category for each option, as well as a number of additional fields in a small number of cases.

=head1 METHODS

=over 4

=item loadConfigFromDB ();

Loads existing configuration from the database (if any) and merges it with the definitions held in this module. This results in the merging of any new configuration and the removal of any deprecated configuration while preserving the existing values of every else.

=item saveConfigToDB ();

Saves configuration held in memory to the database. The act of loading and saving configuration is a convenient way to ensure that the configuration held in the database corresponds with the most recent definitions and that all components are using the same set of configuration.

=head2 EXPORT

None by default.
The :data tag will export the various configuration data structures
The :functions tag will export the helper functions.
The :all tag will export all above symbols.


=head1 SEE ALSO

http://www.zoneminder.com

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
