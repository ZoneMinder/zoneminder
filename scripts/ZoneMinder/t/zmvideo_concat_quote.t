use strict;
use warnings;

use Test::More;

my $path = defined $ARGV[0] ? $ARGV[0]
  : defined $ENV{ZMVIDEO} ? $ENV{ZMVIDEO}
  : 'scripts/zmvideo.pl.in';

plan tests => 6;

my $src;
{
  open my $fh, '<', $path or die "open $path: $!";
  local $/;
  $src = <$fh>;
  close $fh;
}

my ($loop) = $src =~ /
  ^[ \t]*my[ \t]*\([ \t]*\$fd[ \t]*,[ \t]*\$concat_list_file[ \t]*\)
  [ \t]*=[ \t]*tempfile\([^\n]*\n
  (.*?)
  ^[ \t]*close[ \t]+\$fd[ \t]*;
/msx;

ok(defined $loop && $loop =~ /\S/, "extracted concat list loop from $path")
  or BAIL_OUT("could not extract the concat list loop from $path");

my @paths = (
  "/store/1/2024-01-01/00/a.mp4",
  "/store/it's/b.mp4",
  "/store/a'b'c/c.mp4",
);

sub perl_quote {
  my ($s) = @_;
  $s =~ s/\\/\\\\/g;
  $s =~ s/'/\\'/g;
  return "'$s'";
}

my $eval_src =
    'my @video_files = (' . join(', ', map { perl_quote($_) } @paths) . ");\n"
  . "open my \$fd, '>', \\my \$buf or die \"open: \$!\";\n"
  . $loop
  . "\nclose \$fd or die \"close: \$!\";\n"
  . "\$buf\n";

my $buf = eval $eval_src;
die "eval of extracted concat loop failed: $@" if $@;

my @lines = split /\n/, $buf, -1;
pop @lines while @lines && $lines[-1] eq '';

my $escape = q{'\\''};
my $shape = qr/^file '(?:[^']|'\\'')*'$/;

my @parsed;
foreach my $i (0 .. $#paths) {
  my $line = defined $lines[$i] ? $lines[$i] : '';
  like($line, $shape, "line $i has only escaped single quotes inside the quoted body");

  my ($body) = $line =~ /^file '(.*)'$/;
  $body = '' unless defined $body;
  $body =~ s/\Q$escape\E/'/g;
  push @parsed, $body;
}

is_deeply(\@parsed, \@paths, 'quoted paths round-trip through the ffmpeg concat parser');

is($lines[0], "file '/store/1/2024-01-01/00/a.mp4'",
  'plain path with no quote is written unchanged');
