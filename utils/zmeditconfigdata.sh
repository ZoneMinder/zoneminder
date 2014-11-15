#!/bin/bash
# This script allows the package maintainer to change the default value
# of any variable specified in ConfigData.pm without writing a patch.
# Run this script from your build folder, before running configure or cmake.

usage()
{
cat <<EOF

USAGE:
$0 VARIABLE DEFAULT [CONFIGDATA DIRECTORY]

Replace the default value, DEFAULT, of the specified ZoneMinder
variable, VARIABLE, located in ConfigData.pm.in.

Default folder for ConfigData is ./scripts/ZoneMinder/lib/ZoneMinder
Specify CONFIGDATA DIRETORY to override.

Run this script from your build folder, before running configure or cmake.

EXAMPLE:
zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes

WARNING:
The user supplied value for DEFAULT is not checked for sanity. For example,  
changing ZM_LANG_DEFAULT to "pony" will cause bad things to happen!

EOF
}

if [ -z "$1" ] || [ -z "$2" ]; then 
	usage
	exit 0
fi

# Check to see if this script has access to all the commands it needs
for CMD in set echo printf grep sed ; do
  type $CMD &> /dev/null

  if [ $? -ne 0 ]; then
    echo
    echo "ERROR: The script cannot find the required command \"${CMD}\"."
    echo
    exit 1
  fi
done

escape()
{
escaped=""
local temp="$(printf %q "$1")"
escaped="$(echo $temp | sed 's/\//\\\//g')"
}

# Assign variables once they are properly escaped
escape $1
variable=$escaped
escape $2
default=$escaped

# Set the path to ConfigData
if [ -n "$3" ]; then
	configdata="$3/ConfigData.pm.in"
else
	configdata="./scripts/ZoneMinder/lib/ZoneMinder/ConfigData.pm.in"
fi

# Check to make sure we can find ConfigData
if [ ! -e "$configdata" ]; then
	echo "CONFIGDATA FILE NOT FOUND: $configdata"
	exit 1
fi

# Now that we've found ConfidData, verify the supplied variable
# is defined inside the ConfigData file.
if [ -z "$(grep $variable $configdata)" ]; then
	echo "ZONEMINDER VARIABLE NOT FOUND: $variable"
	exit 1
fi

# Update the supplied variable with the new default value.
# Don't stare too closely. You will burn your eyes out.
sed -i '/.*'${variable}'.*/{
	$!{ N
	s/\(.*'${variable}'.*\n.*\)\"\(.*\)\"/\1\"'${default}'\"/
	t yes
	P
	D
	:yes

	}
	}' $configdata

if [ "$?" != "0" ]; then
	echo "SED RETURNED FAILURE"
	exit 1
fi



