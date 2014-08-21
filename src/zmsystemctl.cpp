//
// ZoneMinder zmsystemctl, $Date: 2014-08-06 23:21:00 +0000 (Wed, 06 Aug 2014) $
// Copyright (C) 2014 David Wilcox
//
// This program is new for systemd control
// The loadconfig section is a modified version of the code in
// zm_config.cpp, speficially looking for the service name record
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

#include <zm_config.h>
#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
#include <unistd.h>
#include <iostream>
#include <fstream>
#include <string.h>
using namespace std;

int servicecount;
char *LoadConfig()
{
        FILE *cfg;
        char line[512];
        char *val;
        if ( (cfg = fopen( ZM_CONFIG, "r")) == NULL )
        {
                printf( "Can't open %s", ZM_CONFIG );
		exit(99);
        }
        while ( fgets( line, sizeof(line), cfg ) != NULL )
        {
                char *line_ptr = line;

                // Trim off any cr/lf line endings
                int chomp_len = strcspn( line_ptr, "\r\n" );
                line_ptr[chomp_len] = '\0';

                // Remove leading white space
                int white_len = strspn( line_ptr, " \t" );
                line_ptr += white_len;

                // Check for comment or empty line
                if ( *line_ptr == '\0' || *line_ptr == '#' )
                        continue;

                // Remove trailing white space
                char *temp_ptr = line_ptr+strlen(line_ptr)-1;
                while ( *temp_ptr == ' ' || *temp_ptr == '\t' )
                {
                        *temp_ptr-- = '\0';
                        temp_ptr--;
                }

                // Now look for the '=' in the middle of the line
                temp_ptr = strchr( line_ptr, '=' );
                if ( !temp_ptr )
                {
                        printf( "Invalid data in %s: '%s'", ZM_CONFIG, line );
                        continue;
                }

                // Assign the name and value parts
                char *name_ptr = line_ptr;
                char *val_ptr = temp_ptr+1;

                // Trim trailing space from the name part
                do
                {
                        *temp_ptr = '\0';
                        temp_ptr--;
                }
                while ( *temp_ptr == ' ' || *temp_ptr == '\t' );

                // Remove leading white space from the value part
                white_len = strspn( val_ptr, " \t" );
                val_ptr += white_len;

                val = (char *)malloc( strlen(val_ptr)+1 );
                strncpy( val, val_ptr, strlen(val_ptr)+1 );
                if ( strcasecmp( name_ptr, "ZM_SERVICE_NAME" ) == 0 )
		{	
			servicecount = 1;   
         		return val;
		}
		else
		{
			// continue through file
		}
        }
        fclose( cfg);
}

char* ErrPrint()
{
   {
      printf("Action must be start, stop or restart\n");
   }
}

int main(int argc, char* argv[])
{
   char *zmservice; 
   servicecount=0; /* set to 1 if we find the service name in the conf file */
   string action;
   if (argc < 2 )
   {
      ErrPrint();
      return 1;
   }
   action=argv[1];
   if ((action != "start") && (action != "stop") && (action != "restart"))
   {
      ErrPrint();
      return 2;
   }
   zmservice = LoadConfig(); /* look in the config file for a service name */
   if (servicecount == 0)
   {
      return 3; /* no record found */
   }
   else if (zmservice[0] == (char)0)
   {
      return 4; /* record found but empty */
   }
   else
   {   
      string command;
      command="/usr/bin/systemctl " + action + " " +  string(zmservice);
      setuid( 0 );
      system((char *)command.c_str()); /* send the command to systemd */
      return 0;  
   }
}
