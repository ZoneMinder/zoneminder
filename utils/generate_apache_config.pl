#!/usr/bin/perl -w
use strict;


use Getopt::Long ();

my $opts = {};
Getopt::Long::GetOptions($opts, 'help', 'output=s',
  'pid_file=s', 
  'min_port=s','max_port=s', 'debug=s',
  'server_name=s','error_log=s','protocol=s',
);

if ($opts->{help}) {
  usage();
  exit 0;
} # end if


my %defaults = (
  error_log => '/var/log/apache2/error.log',
    output  => '/etc/apache2/sites-available/zoneminder.conf',
    protocol  => 'http',
);

foreach my $key ( keys %defaults ) {
  if ( ! $$opts{$key} ) {
    $$opts{$key} = $defaults{$key};
  }
}

my $Listen = '';
my $VirtualHostPorts;

if ( $$opts{protocol} eq 'https' ) {
  if ( ! $$opts{server_name} ) {
    die "https requires a server_name";
  }
  $VirtualHostPorts = ' *:443';
} else {
  $VirtualHostPorts = ' *:80';
}


foreach my $port ( $$opts{min_port} .. $$opts{max_port} ) {
  $Listen .= "Listen $port $$opts{protocol}\n";
  $VirtualHostPorts .= " *:$port";
}

my $template =qq` 
$Listen
<VirtualHost$VirtualHostPorts>
DocumentRoot    /usr/share/zoneminder/www
`. ( $$opts{server_name} ? '  ServerName      ' . $$opts{server_name} : '' ).
qq`
ErrorLog        $$opts{error_log}

ScriptAlias /zm/cgi-bin/ /usr/lib/zoneminder/cgi-bin/
ScriptAlias /cgi-bin/ /usr/lib/zoneminder/cgi-bin/
<Directory "/usr/lib/zoneminder/cgi-bin">
  AllowOverride None
  Options +ExecCGI -MultiViews +SymLinksIfOwnerMatch
  Require all granted
  Satisfy Any
  Order allow,deny
  Allow from all
</Directory>

Alias /zm /usr/share/zoneminder/www

<Directory /usr/share/zoneminder/www>
  php_flag register_globals off
  Options +Indexes +FollowSymLinks
  AllowOverride All
  <IfModule mod_dir.c>
    DirectoryIndex index.php
  </IfModule>
  Require all granted
  Satisfy Any
  Order allow,deny
  Allow from all

</Directory>
`;
if ( $$opts{protocol} eq 'https' ) {
  $template .= qq`
SSLCertificateFile /etc/letsencrypt/live/$$opts{server_name}/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/$$opts{server_name}/privkey.pem
Include /etc/letsencrypt/options-ssl-apache.conf
`;
}
$template .= qq`
</VirtualHost>
`;


if ( open( F, "> $$opts{output}" ) ) {
  binmode F;
  print F $template;
  close F;
} else {
  die "Error opening $$opts{output}, Reason: $!";
} # end if

sub usage {
	print "
Usage: generate-apache-config.pl
	--help			output this help.
	--output=file the file to output the config to,
  --min_port=		The starting port. port 80 or 443 will be added as appropriate depending on protocol.
	--max_port=		The ending port.
	--debug=				more verbose output
  --server_name=[servername]
	--error_log
--protocol=[http|https] 		Whether to turn on https for this host. Will assume a letsencrypt setup for keys.
";
	exit 1;
}
1;
__END__
