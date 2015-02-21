-- 
-- New values for the Config table
-- 
insert into Config set Id = 107, Name = 'ZM_PATH_ARP', Value = '/sbin/ip neigh', Type = 'string', DefaultValue = '/sbin/ip neigh', Hint = '/absolute/path/to/somewhere', Pattern = '(?-xism:^((?:/[^/]*)+?)/?$)', Format = ' $1 ', Prompt = 'Path to a supported ARP tool', Help = 'The camera probe function uses Address Resolution Protocol in order to find known devices on the network. Supply the full path to \"ip neigh\", \"arp -a\", or any other tool on your system that returns ip/mac address pairs.', Category = 'paths', Readonly = '0', Requires = '';


