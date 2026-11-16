use strict;
use warnings;
use Test::More;
use FindBin;

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 4;

new ZoneMinder::Event(101, { Id => 101, Name => 'one' });
new ZoneMinder::Event(102, { Id => 102, Name => 'two' });
is(scalar keys %{$ZoneMinder::Object::cache{'ZoneMinder::Event'}}, 2, 'events built from rows are cached');

# zmaudit loops forever, so each pass must drop cached events or it leaks every event it has ever seen
ZoneMinder::Object::init_cache();
ok(!%{$ZoneMinder::Object::cache{'ZoneMinder::Event'} // {}}, 'init_cache drops them');

open(my $fh, '<', "$FindBin::Bin/../../zmaudit.pl.in") or die "zmaudit.pl.in: $!";
my $source = do { local $/; <$fh> };
my ($loop) = $source =~ /^MAIN: while\( \$loop \) \{\n(.*?)^\};/ms;
ok($loop, 'found the zmaudit main loop');
like($loop // '', qr/\A\s*ZoneMinder::Object::init_cache\(\);/, 'each pass starts by clearing the object cache');
