#!/usr/bin/env perl

# While this script is running, it will print out the state of each alarm on the system.
# This script is an example of calling external scripts in reaction to a
# monitor changing state.  Simply replace the print() commands with system(),
# for example, to call external scripts.

use strict;
use warnings;
use ZoneMinder;
use Switch;

$| = 1;

my @monitors;
my $dbh = zmDbConnect();

my $sql = "SELECT * FROM Monitors
  WHERE find_in_set( `Function`, 'Modect,Mocord,Nodect' )".
  ( $Config{ZM_SERVER_ID} ? 'AND ServerId=?' : '' )
  ;

my $sth = $dbh->prepare_cached( $sql )
  or die( "Can't prepare '$sql': ".$dbh->errstr() );

my $res = $sth->execute()
  or die( "Can't execute '$sql': ".$sth->errstr() );

while ( my $monitor = $sth->fetchrow_hashref() ) {
    push( @monitors, $monitor );
}

while (1) {
        foreach my $monitor (@monitors) {
               # Check shared memory ok
               if ( !zmMemVerify( $monitor ) ) {
                 zmMemInvalidate( $monitor );
                 next;
                }

                my $monitorState = zmGetMonitorState($monitor);
                printState($monitor->{Id}, $monitor->{Name}, $monitorState);
        }
        sleep 1;
}

sub printState {
        my ($monitor_id, $monitor_name, $state) = @_;
        my $time = localtime();

        switch ($state) {
                case 0 { print "$time - $monitor_name:\t Idle!\n" }
                case 1 { print "$time - $monitor_name:\t Prealarm!\n" }
                case 2 { print "$time - $monitor_name:\t Alarm!\n" }
                case 3 { print "$time - $monitor_name:\t Alert!\n" }
        }
}
