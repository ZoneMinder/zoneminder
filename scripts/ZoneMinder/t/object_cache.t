use strict;
use warnings;
use Test::More;
use FindBin;

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 5;

# zmfilter builds an Event for every row a filter returns. Objects are cached by
# id for the life of the process, so a background filter that keeps meeting new
# events (PurgeWhenFull on a full disk) grew without limit until it clears the
# cache each pass.

my $first = new ZoneMinder::Event(101, { Id => 101, Name => 'one' });
new ZoneMinder::Event(102, { Id => 102, Name => 'two' });
is(scalar keys %{$ZoneMinder::Object::cache{'ZoneMinder::Event'}}, 2, 'events built from rows are cached');
is(new ZoneMinder::Event(101), $first, 'the cached object is returned for its id');

ZoneMinder::Object::init_cache();
ok(!%{$ZoneMinder::Object::cache{'ZoneMinder::Event'} // {}}, 'init_cache drops them');

open(my $fh, '<', "$FindBin::Bin/../../zmfilter.pl.in") or die "zmfilter.pl.in: $!";
my $source = do { local $/; <$fh> };
my ($loop) = $source =~ /^while \(!\$zm_terminate\) \{\n(.*?)^\}/ms;
ok($loop, 'found the zmfilter main loop');
like($loop // '', qr/ZoneMinder::Object::init_cache\(\);.*foreach my \$filter \(\@filters\)/s,
  'the main loop clears the cache before running the filters');
