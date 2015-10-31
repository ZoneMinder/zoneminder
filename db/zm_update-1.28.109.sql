SET @max = (SELECT max(`id`) FROM `Config`);

INSERT into Config
set
Id = @max + 1,
Name = 'ZM_SSMTP_MAIL',
Value = '0',
Type = 'boolean',
DefaultValue = 'no',
Hint = 'yes|no',
Pattern = '(?^i:^([yn]))',
Format = ' ($1 =~ /^y/) ? \"yes\" : \"no\" ',
Prompt = 'Use a SSMTP mail server if available. NEW_MAIL_MODULES must be enabled',
Help = 'Please visit the following wiki page for more information on setting up ssmtp: http://www.zoneminder.com/wiki/index.php/How_to_get_ssmtp_working_with_Zoneminder.',
Category = 'mail',
Readonly = '0',
Requires = 'ZM_OPT_EMAIL=1;ZM_OPT_MESSAGE=1,ZM_NEW_MAIL_MODULES=1';

INSERT into Config
set
Id = @max + 1,
Name = 'ZM_SSMTP_PATH',
Value = '',
Type = 'string',
DefaultValue = '',
Hint = 'file path',
Pattern = '(?^:^(.+)$)',
Format = ' $1 ',
Prompt = 'SSMTP path if in custom location',
Help = 'Please visit the following wiki page for more information on setting up ssmtp: http://www.zoneminder.com/wiki/index.php/How_to_get_ssmtp_working_with_Zoneminder.',
Category = 'mail',
Readonly = '0',
Requires = 'ZM_SSMTP_MAIL=1';
