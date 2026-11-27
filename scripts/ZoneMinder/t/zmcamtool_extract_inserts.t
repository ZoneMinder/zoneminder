#!/usr/bin/perl
# zmcamtool's extractInserts() read seed files line by line and only matched an
# INSERT that sat on one line, so the multi-line HeroSpeed L5TW/L5BW rows in
# db/controls.sql were skipped without a word and --import never added them.
#
# extractInserts() and its helpers are taken from zmcamtool.pl.in and run on the
# real db/controls.sql, with the table's columns read from zm_create.sql.in
# instead of a database.
use strict;
use warnings;
use File::Basename qw(dirname);
use File::Spec;
use Test::More;

my $root = File::Spec->rel2abs(dirname(__FILE__).'/../../..');
my $src = $ENV{ZMCAMTOOL_SRC} || "$root/scripts/zmcamtool.pl.in";

open(my $fh, '<', $src) or die "can't read $src: $!";
my $script = do { local $/; <$fh> };
close $fh;

my $code = '';
for my $sub (qw(splitSqlList sqlUnquote extractInserts)) {
  $script =~ /^(sub \Q$sub\E \{.*?^\})/ms or die "no sub $sub in $src";
  $code .= "$1\n";
}

# Columns of a table, in order, from the CREATE TABLE in zm_create.sql.in.
sub tableColumns {
  my ($dbh, $table) = @_;
  open(my $c, '<', "$root/db/zm_create.sql.in") or die $!;
  my ($in, @cols);
  while (my $l = <$c>) {
    if ($l =~ /^CREATE TABLE `\Q$table\E`/) { $in = 1; next; }
    next if !$in;
    last if $l =~ /^\)/;
    push @cols, $1 if $l =~ /^\s*`(\w+)`/;
  }
  return @cols;
}

my @warnings;
local $SIG{__WARN__} = sub { push @warnings, @_ };
eval $code;
die $@ if $@;

my $controls = "$root/db/controls.sql";
my $found = extractInserts(undef, $controls, ['Controls']);

ok(exists $found->{Controls}{'HeroSpeed L5TW'}, 'multi-line HeroSpeed L5TW insert is found');
ok(exists $found->{Controls}{'HeroSpeed L5BW'}, 'multi-line HeroSpeed L5BW insert is found');
like($found->{Controls}{'HeroSpeed L5TW'} // '', qr/'Herospeed'/, 'its values are kept');

open(my $s, '<', $controls) or die $!;
my $statements = grep { /^\s*INSERT\s+INTO\s+`?Controls`?/i } <$s>;
close $s;
is(scalar(keys %{$found->{Controls}}), $statements, 'every Controls insert in controls.sql is found');
is_deeply(\@warnings, [], 'no rows are skipped with a warning');

done_testing();
