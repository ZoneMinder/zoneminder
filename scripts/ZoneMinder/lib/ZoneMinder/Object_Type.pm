use strict;
package ZoneMinder::Object_Type;
our @ISA = qw( ZoneMinder::Object );

use vars qw( $debug $table %fields %transforms %defaults $primary_key );

$debug = 0;
$table = 'Object_Types';
$primary_key = 'id';
%fields = (
	Id		=>	'Id',
	Name	=>	'Name',
	Human	=>	'Human',
);
%defaults = (
);
%transforms = (
  Name  => [ 's/^\s+//', 's/\s+$//', 's/\s\s+/ /g' ],
  Human => [ 's/^\s+//', 's/\s+$//', 's/\s\s+/ /g', 's/^ZoneMinder:://' ],
);

sub Object {
	if ( $_[0]{Name} ) {
    my $name = $_[0]{Name};
    $name =~ s/::/\//g;
    eval {
      require $name.'.pm';
    };
		$ZoneMinder::log->error("failed requiring $name $@") if $@;
  	return $_[0]{Name}->new($_[1]);
	}
	my ($caller, undef, $line) = caller;
	$ZoneMinder::log->error("Unknown object from $caller:$line");
	return new ZoneMinder::Object();
} # end sub Object

sub Human {
	if ( @_ > 1 ) {
		$_[0]{Human} = $_[1];
	}
	if ( ! $_[0]{Human} ) {
		$_[0]{Human} = $_[0]{Name};
		$_[0]{Human} =~ s/^ZoneMinder:://;
	}
	return $_[0]{Human};
}

1;
__END__
