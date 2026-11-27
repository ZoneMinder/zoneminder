# Stubs so the ZoneMinder::Trigger modules load without the generated modules.
# Use from a BEGIN block, before the first "use ZoneMinder::Trigger::...".
package ZmtStubs;
sub install {
  $INC{'ZoneMinder/Base.pm'} = $INC{'ZoneMinder/Logger.pm'} = 1;
  $ZoneMinder::Base::VERSION = '1';
  *ZoneMinder::Logger::import = sub {
    no strict 'refs';
    my $c = caller;
    *{"${c}::$_"} = sub { print "LOG $_[0]\n" if $ENV{ZMT_VERBOSE}; } for qw(Debug Info Warning Error Fatal);
  };
}
1;
