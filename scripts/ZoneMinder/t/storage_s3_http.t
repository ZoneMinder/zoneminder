use strict;
use warnings;
use Test::More;

eval { require ZoneMinder::Storage; 1 } or plan skip_all => "cannot load ZoneMinder::Storage: $@";
plan tests => 4;

# An s3fs Url may name its endpoint with http:// or https://. The scheme is
# stripped from the host, so it has to be passed on as use_https or the client
# falls back to its default of https and cannot reach a plain http endpoint.
# Net::Amazon::S3 is stubbed to record what it is given.
my %vendor;
BEGIN {
  $INC{'Net/Amazon/S3.pm'} = 1;
  $INC{'Net/Amazon/S3/Vendor/Generic.pm'} = 1;
}
{
  no warnings 'redefine', 'once';
  *Net::Amazon::S3::new = sub { return bless {}, 'Net::Amazon::S3'; };
  *Net::Amazon::S3::Vendor::Generic::new = sub { shift; %vendor = @_; return bless {}, 'Net::Amazon::S3::Vendor::Generic'; };
  *ZoneMinder::Storage::Debug = sub { };
  *ZoneMinder::Storage::Warning = sub { };
  *ZoneMinder::Storage::Error = sub { diag(join(' ', @_)); };
}

sub vendor_for {
  %vendor = ();
  my $Storage = bless { Type => 's3fs', Url => shift }, 'ZoneMinder::Storage';
  $Storage->s3();
  return %vendor;
}

my %v = vendor_for('s3fs://id:secret@http://minio.lan:9000/bucket/events');
is($v{host}, 'minio.lan:9000', 'the scheme is not part of the host');
ok(exists $v{use_https} && !$v{use_https}, 'an http:// endpoint is reached over http');

%v = vendor_for('s3fs://id:secret@https://minio.lan:9000/bucket/events');
ok(!exists $v{use_https} || $v{use_https}, 'an https:// endpoint is reached over https');

%v = vendor_for('s3fs://id:secret@s3.ca-central-1.amazonaws.com/bucket/events');
ok(!exists $v{use_https} || $v{use_https}, 'an endpoint without a scheme keeps https');
