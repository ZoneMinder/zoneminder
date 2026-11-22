use strict;
use warnings;
use Test::More;
use FindBin;

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 6;

# An event without a StartDateTime has no date-based path, and zmaudit must not
# search the other storage areas with that empty path.

{
  no warnings 'redefine', 'once';
  *ZoneMinder::Event::Error = sub {};
}

foreach my $scheme ( 'Deep', 'Medium' ) {
  my $Event = ZoneMinder::Event->new(5, { Id => 5, MonitorId => 1, Scheme => $scheme, StartDateTime => undef });
  is($Event->RelativePath(), '', "$scheme scheme without StartDateTime has no relative path");
}

my $Event = ZoneMinder::Event->new(6, { Id => 6, MonitorId => 1, Scheme => 'Shallow', StartDateTime => undef });
is($Event->RelativePath(), '1/6', 'Shallow scheme does not need StartDateTime');

$Event = ZoneMinder::Event->new(7, { Id => 7, MonitorId => 1, Scheme => 'Medium', StartDateTime => '2024-05-06 07:08:09' });
like($Event->RelativePath(), qr{^1/2024-05-0[56]/7$}, 'Medium scheme with StartDateTime');

open(my $fh, '<', "$FindBin::Bin/../../zmaudit.pl.in") or die "zmaudit.pl.in: $!";
my $source = do { local $/; <$fh> };
my ($search) = $source =~ /(# Check for existence in other Storage Areas.*?\} # end foreach Storage)/s;
ok($search, 'found the cross-storage search');
like($search // '', qr/\$relative_path \? \@all_Storage_Areas : \(\)/, 'search is skipped without a relative path');
