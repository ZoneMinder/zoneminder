//
// ZoneMinder Control Utility, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

/*

=head1 NAME

zmc - The ZoneMinder Utility

=head1 SYNOPSIS

 zmu -d device_path [-v] [function] [-U<username> -P<password>]
 zmu --device device_path [-v] [function] [-U<username> -P<password>]

 zmu -m monitor_id [-v] [function] [-U<username> -P<password>]
 zmu --monitor monitor_id [-v] [function] [-U<username> -P<password>]

=head1 DESCRIPTION

This binary is a handy command line interface to several useful functions. It's
not really meant to be used by anyone except the web page (there's only limited
'help' in it so far) but can be if necessary, especially for debugging video
problems. 

=head1 OPTIONS

General options:
  -v, --verbose                           - Produce more verbose output
  -l, --list                              - List the current status of active (or all with -v) monitors
  -h, --help                               - Display usage information
  -v, --version                            - Print the installed version of ZoneMinder

Options for use with devices:
  -d, --device [device_path]              - Get the current video device settings for [device_path] or all devices
  -V, --version <V4L version>             - Set the Video 4 Linux API version to use for the query, use 1 or 2
  -q, --query                             - Query the current settings for the device

Options for use with monitors:
  -m, --monitor <monitor_id>              - Specify which monitor to address, default 1 if absent
  -q, --query                             - Query the current settings for the monitor
  -s, --state                             - Output the current monitor state, 0 = idle, 1 = prealarm, 2 = alarm,
                                            3 = alert, 4 = tape
  -B, --brightness [value]                - Output the current brightness, set to value if given 
  -C, --contrast [value]                  - Output the current contrast, set to value if given 
  -H, --hue [value]                       - Output the current hue, set to value if given 
  -O, --colour [value]                    - Output the current colour, set to value if given 
  -i, --image [image_index]               - Write captured image to disk as <monitor_name>.jpg, last image captured
                                            or specified ring buffer index if given.
  -S, --scale <scale_%%ge>                - With --image specify any scaling (in %%) to be applied to the image
  -t, --timestamp [image_index]           - Output captured image timestamp, last image captured or specified
                                            ring buffer index if given
  -R, --read_index                        - Output ring buffer read index
  -W, --write_index                       - Output ring buffer write index
  -e, --event                             - Output last event index
  -f, --fps                               - Output last Frames Per Second captured reading
  -z, --zones                             - Write last captured image overlaid with zones to <monitor_name>-Zones.jpg
  -a, --alarm                             - Force alarm in monitor, this will trigger recording until cancelled with -c
  -n, --noalarm                           - Force no alarms in monitor, this will prevent alarms until cancelled with -c
  -c, --cancel                            - Cancel a forced alarm/noalarm in monitor, required after being enabled with -a or -n
  -L, --reload                            - Signal monitor to reload settings
  -E, --enable                            - Enable detection, wake monitor up
  -D, --disable                           - Disable detection, put monitor to sleep
  -u, --suspend                           - Suspend detection, useful to prevent bogus alarms when panning etc
  -r, --resume                            - Resume detection after a suspend
  -U, --username <username>               - When running in authenticated mode the username and
  -P, --password <password>               - password combination of the given user
  -A, --auth <authentication>             - Pass authentication hash string instead of user details

=cut

*/

#include <getopt.h>
#include <cinttypes>

#include "zm.h"
#include "zm_db.h"
#include "zm_user.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_local_camera.h"

void Usage(int status=-1) {
  fputs(
			"zmu <-d device_path> [-v] [function] [-U<username> -P<password>]\n"
			"zmu <-m monitor_id> [-v] [function] [-U<username> -P<password>]\n"
			"General options:\n"
			"  -h, --help           : This screen\n"
			"  -v, --verbose          : Produce more verbose output\n"
			"  -l, --list           : List the current status of active (or all with -v) monitors\n"
			"Options for use with devices:\n"
			"  -d, --device [device_path]   : Get the current video device settings for [device_path] or all devices\n"
			"  -V, --version <V4L version>  : Set the Video 4 Linux API version to use for the query, use 1 or 2\n"
			"  -q, --query          : Query the current settings for the device\n"
			"Options for use with monitors:\n"
			"  -m, --monitor <monitor_id>   : Specify which monitor to address, default 1 if absent\n"
			"  -q, --query          : Query the current settings for the monitor\n"
			"  -s, --state          : Output the current monitor state, 0 = idle, 1 = prealarm, 2 = alarm,\n"
			"                   3 = alert, 4 = tape\n"
			"  -B, --brightness [value]     : Output the current brightness, set to value if given \n"
			"  -C, --contrast [value]     : Output the current contrast, set to value if given \n"
			"  -H, --hue [value]        : Output the current hue, set to value if given \n"
			"  -O, --colour [value]       : Output the current colour, set to value if given \n"
			"  -i, --image [image_index]    : Write captured image to disk as <monitor_name>.jpg, last image captured\n"
			"                   or specified ring buffer index if given.\n"
			"  -S, --scale <scale_%%ge>    : With --image specify any scaling (in %%) to be applied to the image\n"
			"  -t, --timestamp [image_index]  : Output captured image timestamp, last image captured or specified\n"
			"                   ring buffer index if given\n"
			"  -R, --read_index         : Output ring buffer read index\n"
			"  -W, --write_index        : Output ring buffer write index\n" 
			"  -e, --event          : Output last event index\n" 
			"  -f, --fps            : Output last Frames Per Second captured reading\n" 
			"  -z, --zones          : Write last captured image overlaid with zones to <monitor_name>-Zones.jpg\n" 
			"  -a, --alarm          : Force alarm in monitor, this will trigger recording until cancelled with -c\n" 
			"  -n, --noalarm          : Force no alarms in monitor, this will prevent alarms until cancelled with -c\n" 
			"  -c, --cancel           : Cancel a forced alarm/noalarm in monitor, required after being enabled with -a or -n\n" 
			"  -L, --reload           : Signal monitor to reload settings\n" 
			"  -E, --enable           : Enable detection, wake monitor up\n" 
			"  -D, --disable          : Disable detection, put monitor to sleep\n" 
			"  -u, --suspend          : Suspend detection, useful to prevent bogus alarms when panning etc\n" 
			"  -r, --resume           : Resume detection after a suspend\n" 
			"  -U, --username <username>    : When running in authenticated mode the username and\n" 
			"  -P, --password <password>    : password combination of the given user\n" 
			"  -A, --auth <authentication>  : Pass authentication hash string instead of user details\n"
      "  -T, --token <token>  : Pass JWT token string instead of user details\n"
	 "", stderr );

  exit(status);
}

typedef enum {
	ZMU_BOGUS      = 0x00000000,
	ZMU_STATE      = 0x00000001,
	ZMU_IMAGE      = 0x00000002,
	ZMU_TIME       = 0x00000004,
	ZMU_READ_IDX   = 0x00000008,
	ZMU_WRITE_IDX  = 0x00000010,
	ZMU_EVENT      = 0x00000020,
	ZMU_FPS        = 0x00000040,
	ZMU_ZONES      = 0x00000080,
	ZMU_ALARM      = 0x00000100,
	ZMU_NOALARM    = 0x00000200,
	ZMU_CANCEL     = 0x00000400,
	ZMU_QUERY      = 0x00000800,
	ZMU_BRIGHTNESS = 0x00001000,
	ZMU_CONTRAST   = 0x00002000,
	ZMU_HUE        = 0x00004000,
	ZMU_COLOUR     = 0x00008000,
	ZMU_RELOAD     = 0x00010000,
	ZMU_ENABLE     = 0x00100000,
	ZMU_DISABLE    = 0x00200000,
	ZMU_SUSPEND    = 0x00400000,
	ZMU_RESUME     = 0x00800000,
	ZMU_LIST       = 0x10000000,
} Function;

bool ValidateAccess(User *user, int mon_id, int function) {
  bool allowed = true;
  if ( function & (ZMU_STATE|ZMU_IMAGE|ZMU_TIME|ZMU_READ_IDX|ZMU_WRITE_IDX|ZMU_FPS) ) {
    if ( user->getStream() < User::PERM_VIEW )
      allowed = false;
  }
  if ( function & ZMU_EVENT ) {
    if ( user->getEvents() < User::PERM_VIEW )
      allowed = false;
  }
  if ( function & (ZMU_ZONES|ZMU_QUERY|ZMU_LIST) ) {
    if ( user->getMonitors() < User::PERM_VIEW )
      allowed = false;
  }
  if ( function & (ZMU_ALARM|ZMU_NOALARM|ZMU_CANCEL|ZMU_RELOAD|ZMU_ENABLE|ZMU_DISABLE|ZMU_SUSPEND|ZMU_RESUME|ZMU_BRIGHTNESS|ZMU_CONTRAST|ZMU_HUE|ZMU_COLOUR) ) {
    if ( user->getMonitors() < User::PERM_EDIT )
      allowed = false;
  }
  if ( mon_id > 0 ) {
    if ( !user->canAccess(mon_id) ) {
      allowed = false;
    }
  }
  return allowed;
}

int exit_zmu(int exit_code) {
  logTerm();
  zmDbClose();

  exit(exit_code);
  return exit_code;
}

int main(int argc, char *argv[]) {
  if ( access(ZM_CONFIG, R_OK) != 0 ) {
    fprintf(stderr, "Can't open %s: %s\n", ZM_CONFIG, strerror(errno));
    exit(-1);
  }

  self = argv[0];

  srand(getpid() * time(0));

  static struct option long_options[] = {
    {"device", 2, 0, 'd'},
    {"monitor", 1, 0, 'm'},
    {"verbose", 0, 0, 'v'},
    {"image", 2, 0, 'i'},
    {"scale", 1, 0, 'S'},
    {"timestamp", 2, 0, 't'},
    {"state", 0, 0, 's'},
    {"brightness", 2, 0, 'B'},
    {"contrast", 2, 0, 'C'},
    {"hue", 2, 0, 'H'},
    {"contrast", 2, 0, 'O'},
    {"read_index", 0, 0, 'R'},
    {"write_index", 0, 0, 'W'},
    {"event", 0, 0, 'e'},
    {"fps", 0, 0, 'f'},
    {"zones", 2, 0, 'z'},
    {"alarm", 0, 0, 'a'},
    {"noalarm", 0, 0, 'n'},
    {"cancel", 0, 0, 'c'},
    {"reload", 0, 0, 'L'},
    {"enable", 0, 0, 'E'},
    {"disable", 0, 0, 'D'},
    {"suspend", 0, 0, 'u'},
    {"resume", 0, 0, 'r'},
    {"query", 0, 0, 'q'},
    {"username", 1, 0, 'U'},
    {"password", 1, 0, 'P'},
    {"auth", 1, 0, 'A'},
    {"token", 1, 0, 'T'},
    {"version", 1, 0, 'V'},
    {"help", 0, 0, 'h'},
    {"list", 0, 0, 'l'},
    {0, 0, 0, 0}
  };

  const char *device = 0;
  int mon_id = 0;
  bool verbose = false;
  int function = ZMU_BOGUS;

  int image_idx = -1;
  int scale = -1;
  int brightness = -1;
  int contrast = -1;
  int hue = -1;
  int colour = -1;
  char *zoneString = 0;
  char *username = 0;
  char *password = 0;
  char *auth = 0;
  std::string jwt_token_str = "";
#if ZM_HAS_V4L
#if ZM_HAS_V4L2
    int v4lVersion = 2;
#elif ZM_HAS_V4L1
    int v4lVersion = 1;
#endif // ZM_HAS_V4L2/1
#endif // ZM_HAS_V4L
  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "d:m:vsEDLurwei::S:t::fz::ancqhlB::C::H::O::U:P:A:V:T:", long_options, &option_index);
    if ( c == -1 ) {
      break;
    }

    switch (c) {
      case 'd':
        if ( optarg )
          device = optarg;
        break;
      case 'm':
        mon_id = atoi(optarg);
        break;
      case 'v':
        verbose = true;
        break;
      case 's':
        function |= ZMU_STATE;
        break;
      case 'i':
        function |= ZMU_IMAGE;
        if ( optarg )
          image_idx = atoi(optarg);
        break;
      case 'S':
        scale = atoi(optarg);
        break;
      case 't':
        function |= ZMU_TIME;
        if ( optarg )
          image_idx = atoi(optarg);
        break;
      case 'R':
        function |= ZMU_READ_IDX;
        break;
      case 'W':
        function |= ZMU_WRITE_IDX;
        break;
      case 'e':
        function |= ZMU_EVENT;
        break;
      case 'f':
        function |= ZMU_FPS;
        break;
      case 'z':
        function |= ZMU_ZONES;
        if ( optarg )
          zoneString = optarg;
        break;
      case 'a':
        function |= ZMU_ALARM;
        break;
      case 'n':
        function |= ZMU_NOALARM;
        break;
      case 'c':
        function |= ZMU_CANCEL;
        break;
      case 'L':
        function |= ZMU_RELOAD;
        break;
      case 'E':
        function |= ZMU_ENABLE;
        break;
      case 'D':
        function |= ZMU_DISABLE;
        break;
      case 'u':
        function |= ZMU_SUSPEND;
        break;
      case 'r':
        function |= ZMU_RESUME;
        break;
      case 'q':
        function |= ZMU_QUERY;
        break;
      case 'B':
        function |= ZMU_BRIGHTNESS;
        if ( optarg )
          brightness = atoi(optarg);
        break;
      case 'C':
        function |= ZMU_CONTRAST;
        if ( optarg )
          contrast = atoi(optarg);
        break;
      case 'H':
        function |= ZMU_HUE;
        if ( optarg )
          hue = atoi(optarg);
        break;
      case 'O':
        function |= ZMU_COLOUR;
        if ( optarg )
          colour = atoi(optarg);
        break;
      case 'U':
        username = optarg;
        break;
      case 'P':
        password = optarg;
        break;
      case 'A':
        auth = optarg;
        break;
      case 'T':
        jwt_token_str = std::string(optarg);
        break;
#if ZM_HAS_V4L
			case 'V':
				v4lVersion = (atoi(optarg)==1)?1:2;
				break;
#endif // ZM_HAS_V4L
      case 'h':
      case '?':
        Usage(0);
        break;
      case 'l':
        function |= ZMU_LIST;
        break;
      default:
        //fprintf( stderr, "?? getopt returned character code 0%o ??\n", c );
        break;
    }
  } // end getopt loop

  if ( optind < argc ) {
    fprintf(stderr, "Extraneous options, ");
    while (optind < argc)
      fprintf(stderr, "%s ", argv[optind++]);
    fprintf(stderr, "\n");
    Usage();
  }

  if ( device && !(function&ZMU_QUERY) ) {
    fprintf(stderr, "Error, -d option cannot be used with this option\n");
    Usage();
  }
  if ( scale != -1 && !(function&ZMU_IMAGE) ) {
    fprintf(stderr, "Error, -S option cannot be used with this option\n");
    Usage();
  }
  //printf( "Monitor %d, Function %d\n", mon_id, function );

  zmLoadConfig();

  logInit("zmu");

  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  User *user = 0;

  if ( config.opt_use_auth ) {
    if ( jwt_token_str != "" ) {
      user = zmLoadTokenUser(jwt_token_str, false);
    } else if ( strcmp(config.auth_relay, "none") == 0 ) {
      if ( !username ) {
        Error("Username must be supplied");
        exit_zmu(-1);
      }

      if ( !checkUser(username)) {
        Error("Username greater than allowed 32 characters");
        exit_zmu(-1);
      }

      user = zmLoadUser(username);
    } else {
       
      if ( !(username && password) && !auth ) {
        Error("Username and password or auth/token string must be supplied");
        exit_zmu(-1);
      }
      if ( auth ) {
        user = zmLoadAuthUser(auth, false);
      }
      if ( username && password ) {
        if ( !checkUser(username)) {
          Error("username greater than allowed 32 characters");
          exit_zmu(-1);
        }
        if ( !checkPass(password)) {
          Error("password greater than allowed 64 characters");
          exit_zmu(-1);
        }
        user = zmLoadUser(username, password);
      } // end if username && password
    } // end if relay or not
    if ( !user ) {
      Error("Unable to authenticate user");
      exit_zmu(-1);
    }
		if ( !ValidateAccess(user, mon_id, function) ) {
			Error("Insufficient privileges for requested action");
			exit_zmu(-1);
		}
  } // end if auth

  if ( mon_id > 0 ) {
    Monitor *monitor = Monitor::Load(mon_id, function&(ZMU_QUERY|ZMU_ZONES), Monitor::QUERY);
    if ( monitor ) {
      if ( verbose ) {
        printf("Monitor %d(%s)\n", monitor->Id(), monitor->Name());
      }
      if ( !monitor->connect() ) {
        Error("Can't connect to capture daemon: %d %s", monitor->Id(), monitor->Name());
        exit_zmu(-1);
      }

      char separator = ' ';
      bool have_output = false;
      if ( function & ZMU_STATE ) {
        Monitor::State state = monitor->GetState();
        if ( verbose ) {
          printf("Current state: %s\n", state==Monitor::ALARM?"Alarm":(state==Monitor::ALERT?"Alert":"Idle"));
        } else {
          if ( have_output ) fputc(separator, stdout);
          printf("%d", state);
          have_output = true;
        }
      }
      if ( function & ZMU_TIME ) {
        struct timeval timestamp = monitor->GetTimestamp(image_idx);
        if ( verbose ) {
          char timestamp_str[64] = "None";
          if ( timestamp.tv_sec )
            strftime(timestamp_str, sizeof(timestamp_str), "%Y-%m-%d %H:%M:%S", localtime(&timestamp.tv_sec));
          if ( image_idx == -1 )
            printf("Time of last image capture: %s.%02ld\n", timestamp_str, timestamp.tv_usec/10000);
          else
            printf("Time of image %d capture: %s.%02ld\n", image_idx, timestamp_str, timestamp.tv_usec/10000);
        } else {
          if ( have_output ) fputc(separator, stdout);
          printf("%ld.%02ld", timestamp.tv_sec, timestamp.tv_usec/10000);
          have_output = true;
        }
      }
      if ( function & ZMU_READ_IDX ) {
        if ( verbose )
          printf("Last read index: %d\n", monitor->GetLastReadIndex());
        else {
          if ( have_output ) fputc(separator, stdout);
          printf("%d", monitor->GetLastReadIndex());
          have_output = true;
        }
      }
      if ( function & ZMU_WRITE_IDX ) {
        if ( verbose ) {
          printf("Last write index: %d\n", monitor->GetLastWriteIndex());
        } else {
          if ( have_output ) fputc(separator, stdout);
          printf("%d", monitor->GetLastWriteIndex());
          have_output = true;
        }
      }
      if ( function & ZMU_EVENT ) {
        if ( verbose ) {
          printf("Last event id: %" PRIu64 "\n", monitor->GetLastEventId());
        } else {
          if ( have_output ) fputc(separator, stdout);
          printf("%" PRIu64, monitor->GetLastEventId());
          have_output = true;
        }
      }
      if ( function & ZMU_FPS ) {
        if ( verbose ) {
          printf("Current capture rate: %.2f frames per second\n", monitor->GetFPS());
        } else {
          if ( have_output ) fputc(separator, stdout);
          printf("%.2f", monitor->GetFPS());
          have_output = true;
        }
      }
      if ( function & ZMU_IMAGE ) {
        if ( verbose ) {
          if ( image_idx == -1 )
            printf("Dumping last image captured to Monitor%d.jpg", monitor->Id());
          else
            printf("Dumping buffer image %d to Monitor%d.jpg", image_idx, monitor->Id());
          if ( scale != -1 )
            printf(", scaling by %d%%", scale);
          printf("\n");
        }
        monitor->GetImage(image_idx, scale>0?scale:100);
      }
      if ( function & ZMU_ZONES ) {
        if ( verbose )
          printf("Dumping zone image to Zones%d.jpg\n", monitor->Id());
        monitor->DumpZoneImage(zoneString);
      }
      if ( function & ZMU_ALARM ) {
        if ( monitor->GetFunction() == Monitor::Function::MONITOR ) {
         printf("A Monitor in monitor mode cannot handle alarms.  Please use NoDect\n");
        } else {
          Monitor::State state = monitor->GetState();

          if ( verbose ) {
            printf("Forcing alarm on current state: %s, event %" PRIu64 "\n",
                state==Monitor::ALARM?"Alarm":(state==Monitor::ALERT?"Alert":"Idle"),
                monitor->GetLastEventId()
                );
          }
          monitor->ForceAlarmOn(config.forced_alarm_score, "Forced Web");
          while ( ((state = monitor->GetState()) != Monitor::ALARM) && !zm_terminate ) {
            // Wait for monitor to notice.
            usleep(1000);
          }
          printf("Alarmed event id: %" PRIu64 "\n", monitor->GetLastEventId());
        } // end if ! MONITOR
      }
      if ( function & ZMU_NOALARM ) {
        if ( verbose )
          printf("Forcing alarm off\n");
        monitor->ForceAlarmOff();
      }
      if ( function & ZMU_CANCEL ) {
        if ( verbose )
          printf("Cancelling forced alarm on/off\n");
        monitor->CancelForced();
      }
      if ( function & ZMU_RELOAD ) {
        if ( verbose )
          printf("Reloading monitor settings\n");
        monitor->actionReload();
      }
      if ( function & ZMU_ENABLE ) {
        if ( verbose )
          printf("Enabling event generation\n");
        monitor->actionEnable();
      }
      if ( function & ZMU_DISABLE ) {
        if ( verbose )
          printf("Disabling event generation\n");
        monitor->actionDisable();
      }
      if ( function & ZMU_SUSPEND ) {
        if ( verbose )
          printf("Suspending event generation\n");
        monitor->actionSuspend();
      }
      if ( function & ZMU_RESUME ) {
        if ( verbose )
          printf("Resuming event generation\n");
        monitor->actionResume();
      }
      if ( function & ZMU_QUERY ) {
        char monString[16382] = "";
        monitor->DumpSettings(monString, verbose);
        printf("%s\n", monString);
      }
      if ( function & ZMU_BRIGHTNESS ) {
        if ( verbose ) {
          if ( brightness >= 0 )
            printf("New brightness: %d\n", monitor->actionBrightness(brightness));
          else
            printf("Current brightness: %d\n", monitor->actionBrightness());
        } else {
          if ( have_output ) fputc(separator, stdout);
          if ( brightness >= 0 )
            printf("%d", monitor->actionBrightness(brightness));
          else
            printf("%d", monitor->actionBrightness());
          have_output = true;
        }
      }
      if ( function & ZMU_CONTRAST ) {
        if ( verbose ) {
          if ( contrast >= 0 )
            printf("New brightness: %d\n", monitor->actionContrast(contrast));
          else
            printf("Current contrast: %d\n", monitor->actionContrast());
        } else {
          if ( have_output ) fputc(separator, stdout);
          if ( contrast >= 0 )
            printf("%d", monitor->actionContrast(contrast));
          else
            printf("%d", monitor->actionContrast());
          have_output = true;
        }
      }
      if ( function & ZMU_HUE ) {
        if ( verbose ) {
          if ( hue >= 0 )
            printf("New hue: %d\n", monitor->actionHue(hue));
          else
            printf("Current hue: %d\n", monitor->actionHue());
        } else {
          if ( have_output ) fputc(separator, stdout);
          if ( hue >= 0 )
            printf("%d", monitor->actionHue(hue));
          else
            printf("%d", monitor->actionHue());
          have_output = true;
        }
      }
      if ( function & ZMU_COLOUR ) {
        if ( verbose ) {
          if ( colour >= 0 )
            printf("New colour: %d\n", monitor->actionColour(colour));
          else
            printf("Current colour: %d\n", monitor->actionColour());
        } else {
          if ( have_output ) fputc(separator, stdout);
          if ( colour >= 0 )
            printf("%d", monitor->actionColour(colour));
          else
            printf("%d", monitor->actionColour());
          have_output = true;
        }
      }
      if ( have_output ) {
        printf("\n");
      }
      if ( !function ) {
        Usage();
      }
      delete monitor;
    } else {
      Error("Invalid monitor id %d", mon_id);
      exit_zmu(-1);
    }
  } else {
    if ( function & ZMU_QUERY ) {
#if ZM_HAS_V4L
			char vidString[0x10000] = "";
			bool ok = LocalCamera::GetCurrentSettings(device, vidString, v4lVersion, verbose);
			printf("%s", vidString);
			exit_zmu(ok ? 0 : -1);
#else // ZM_HAS_V4L
			Error("Video4linux is required for device querying");
      exit_zmu(-1);
#endif // ZM_HAS_V4L
    }

    if ( function & ZMU_LIST ) {
      std::string sql = "SELECT `Id`, `Function`+0 FROM `Monitors`";
      if ( !verbose ) {
        sql += "WHERE `Function` != 'None'";
      }
      sql += " ORDER BY Id ASC";

      if ( mysql_query(&dbconn, sql.c_str()) ) {
        Error("Can't run query: %s", mysql_error(&dbconn));
        exit_zmu(mysql_errno(&dbconn));
      }

      MYSQL_RES *result = mysql_store_result(&dbconn);
      if ( !result ) {
        Error("Can't use query result: %s", mysql_error(&dbconn));
        exit_zmu(mysql_errno(&dbconn));
      }
      Debug(1, "Got %d monitors", mysql_num_rows(result));

      printf("%4s%5s%6s%9s%14s%6s%6s%8s%8s\n", "Id", "Func", "State", "TrgState", "LastImgTim", "RdIdx", "WrIdx", "LastEvt", "FrmRate");
      for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
        int mon_id = atoi(dbrow[0]);
        int function = atoi(dbrow[1]);
        if ( !user || user->canAccess(mon_id) ) {
          if ( function > 1 ) {
            Monitor *monitor = Monitor::Load(mon_id, false, Monitor::QUERY);
            if ( monitor && monitor->connect() ) {
              struct timeval tv = monitor->GetTimestamp();
              printf( "%4d%5d%6d%9d%11ld.%02ld%6d%6d%8" PRIu64 "%8.2f\n",
                monitor->Id(),
                function,
                monitor->GetState(),
                monitor->GetTriggerState(),
                tv.tv_sec, tv.tv_usec/10000,
                monitor->GetLastReadIndex(),
                monitor->GetLastWriteIndex(),
                monitor->GetLastEventId(),
                monitor->GetFPS()
              );
              delete monitor;
            }
          } else {
            struct timeval tv = { 0, 0 };
            printf("%4d%5d%6d%9d%11ld.%02ld%6d%6d%8d%8.2f\n",
              mon_id,
              function,
              0,
              0,
              tv.tv_sec, tv.tv_usec/10000,
              0,
              0,
              0,
              0.0
            );
          } // end if function filter
        } // endif !user || canAccess(mon_id)
      } // end foreach row
      mysql_free_result(result);
    } // end if function && ZMU_LIST
  }
  delete user;

  return exit_zmu(0);
}
