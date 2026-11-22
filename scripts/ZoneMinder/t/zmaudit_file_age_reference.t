use strict;
use warnings;
use Test::More tests => 5;
use FindBin;
use File::Temp qw(tempdir);

# zmaudit.pl runs for days in continuous mode, and -M measures a file's age
# from $^T, the script start time. Files created after start therefore had a
# negative age and the old image and swap file cleanup never deleted them.
# The script needs a database, so each pass is checked at source level and the
# cleanup is run with the pass-start line taken from the source.

my $src_path = $ENV{ZMAUDIT_SRC} // "$FindBin::Bin/../../zmaudit.pl.in";
open(my $fh, '<', $src_path) or die "Cannot open $src_path: $!";
my $source = do { local $/; <$fh> };
close($fh);

my ($loop) = $source =~ /(^MAIN: while\( \$loop \) \{\n.*?^\};)\s*^Term\(\);/ms;
ok($loop, 'found the zmaudit main loop');

my ($reset) = ($loop // '') =~ /^\s*(\$\^T = time\(\);)/m;
ok($reset, 'each pass resets $^T');

my ($sub) = $source =~ /^(sub deleteSwapImage \{.*?^\})/ms;
ok($sub, 'found deleteSwapImage');

my $dir = tempdir(CLEANUP => 1);
my $file = "$dir/zmswap-test.jpg";
open(my $out, '>', $file) or die "$file: $!";
close($out);

my $max_swap_age = 1;
eval "sub Debug {} $sub; 1" or die $@;

# The daemon started ten days ago; the swap file was written two days ago.
{
  local $^T = time() - 10*86400;
  my $mtime = time() - 2*86400;
  utime($mtime, $mtime, $file) or die "utime: $!";
  eval $reset if $reset;

  chdir($dir) or die "chdir: $!";
  $_ = 'zmswap-test.jpg';
  deleteSwapImage();
  ok(!-e $file, 'a swap file older than the limit is deleted after a long running start');
}

open($out, '>', $file) or die "$file: $!";
close($out);
{
  local $^T = time();
  $_ = 'zmswap-test.jpg';
  deleteSwapImage();
  ok(-e $file, 'a fresh swap file is kept');
}
