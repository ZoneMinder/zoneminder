use strict;
use warnings;
use Test::More tests => 24;

# Load the umbrella first. ZoneMinder::Config connects on include, so requiring
# ZoneMinder::Database on its own re-enters it before its subs are defined.
use ZoneMinder;
require_ok('ZoneMinder::Database');

my $dsn = ZoneMinder::Database->can('zmDbDsn');
ok($dsn, 'zmDbDsn is available');
my $attrs = ZoneMinder::Database->can('zmDbConnectAttributes');
ok($attrs, 'zmDbConnectAttributes is available');
ok(ZoneMinder::Database->can('zmDbDriver'), 'zmDbDriver is available');

my %plain = (ZM_DB_NAME => 'zm', ZM_DB_HOST => 'localhost');

# --- the scheme names the driver ---------------------------------------------

like($dsn->('mysql', \%plain), qr/^DBI:mysql:database=zm;/, 'mysql dsn names DBD::mysql');
like($dsn->('MariaDB', \%plain), qr/^DBI:MariaDB:database=zm;/, 'MariaDB dsn names DBD::MariaDB');

# --- host and port are driver independent, the socket is not -----------------

is($dsn->('mysql', {%plain, ZM_DB_HOST => 'db.example:3307'}),
  'DBI:mysql:database=zm;host=db.example;port=3307', 'a port is a plain host/port pair');
is($dsn->('MariaDB', {%plain, ZM_DB_HOST => 'db.example:3307'}),
  'DBI:MariaDB:database=zm;host=db.example;port=3307', 'and is spelled the same for MariaDB');

is($dsn->('mysql', {%plain, ZM_DB_HOST => 'localhost:/run/mysqld/mysqld.sock'}),
  'DBI:mysql:database=zm;mysql_socket=/run/mysqld/mysqld.sock', 'DBD::mysql takes mysql_socket');
is($dsn->('MariaDB', {%plain, ZM_DB_HOST => 'localhost:/run/mysqld/mysqld.sock'}),
  'DBI:MariaDB:database=zm;mariadb_socket=/run/mysqld/mysqld.sock',
  'DBD::MariaDB takes mariadb_socket, not mysql_socket which it would ignore');

# --- TLS parameters carry the driver's prefix or are silently dropped --------

my %ssl = (%plain,
  ZM_DB_SSL_CA_CERT => '/etc/ssl/ca.pem',
  ZM_DB_SSL_CLIENT_KEY => '/etc/ssl/client.key',
  ZM_DB_SSL_CLIENT_CERT => '/etc/ssl/client.pem');

my $mysql_ssl = $dsn->('mysql', \%ssl);
like($mysql_ssl, qr/;mysql_ssl=1;/, 'mysql_ssl=1');
like($mysql_ssl, qr/;mysql_ssl_ca_file=\/etc\/ssl\/ca\.pem/, 'mysql_ssl_ca_file');
unlike($mysql_ssl, qr/mariadb_/, 'no mariadb_ parameters leak into a DBD::mysql dsn');

my $maria_ssl = $dsn->('MariaDB', \%ssl);
like($maria_ssl, qr/;mariadb_ssl=1;/, 'mariadb_ssl=1');
like($maria_ssl, qr/;mariadb_ssl_ca_file=\/etc\/ssl\/ca\.pem/, 'mariadb_ssl_ca_file');
unlike($maria_ssl, qr/mysql_/, 'no mysql_ parameters leak into a DBD::MariaDB dsn');

is($dsn->('MariaDB', {%ssl, ZM_DB_SSL_VERIFY_SERVER_CERT => 'off'}) =~
  /;mariadb_ssl_verify_server_cert=(\d)/ ? $1 : 'absent', '0',
  'a false-y verify setting disables verification under the mariadb prefix');
unlike($dsn->('MariaDB', \%ssl), qr/verify_server_cert/,
  'an unset verify setting leaves the driver default alone');

# --- attributes --------------------------------------------------------------

is_deeply($attrs->('mysql'), {mysql_enable_utf8mb4 => 1},
  'DBD::mysql must be told to use utf8mb4 or 4 byte characters are mangled');
is_deeply($attrs->('MariaDB'), {},
  'DBD::MariaDB is always utf8mb4 and takes no such attribute');

# --- caller supplied options, named after DBD::mysql (zmupdate.pl does this) --

is($dsn->('mysql', \%plain, {mysql_multi_statements => 1}),
  'DBI:mysql:database=zm;host=localhost;mysql_multi_statements=1',
  'a mysql_ option is passed through untouched to DBD::mysql');
is($dsn->('MariaDB', \%plain, {mysql_multi_statements => 1}),
  'DBI:MariaDB:database=zm;host=localhost;mariadb_multi_statements=1',
  'a mysql_ option is renamed for DBD::MariaDB, which would ignore it otherwise');
is($dsn->('MariaDB', \%plain, {some_other_option => 'x'}),
  'DBI:MariaDB:database=zm;host=localhost;some_other_option=x',
  'an option that is not a mysql_ one is left alone');
is($dsn->('mysql', \%plain, {b_opt => 2, a_opt => 1}),
  'DBI:mysql:database=zm;host=localhost;a_opt=1;b_opt=2',
  'options are emitted in a stable order');
