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

#include "zm.h"
#include "zm_db.h"
#include "zm_user.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_local_camera.h"
#include <getopt.h>
#include <unistd.h>

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
  if ( function & (ZMU_NOALARM|ZMU_RELOAD|ZMU_ENABLE|ZMU_DISABLE|ZMU_SUSPEND|ZMU_RESUME|ZMU_BRIGHTNESS|ZMU_CONTRAST|ZMU_HUE|ZMU_COLOUR) ) {
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

void exit_zmu(int exit_code) {
  logTerm();
  dbQueue.stop();
  zmDbClose();

  exit(exit_code);
}

int main(int argc, char *argv[]) {
  if ( access(ZM_CONFIG, R_OK) != 0 ) {
    fprintf(stderr, "Can't open %s: %s\n", ZM_CONFIG, strerror(errno));
    exit(-1);
  }

  self = argv[0];

  srand(getpid() * time(nullptr));

  static struct option long_options[] = {
    {"device", 2, nullptr, 'd'},
    {"monitor", 1, nullptr, 'm'},
    {"verbose", 0, nullptr, 'v'},
    {"image", 2, nullptr, 'i'},
    {"scale", 1, nullptr, 'S'},
    {"timestamp", 2, nullptr, 't'},
    {"state", 0, nullptr, 's'},
    {"brightness", 2, nullptr, 'B'},
    {"contrast", 2, nullptr, 'C'},
    {"hue", 2, nullptr, 'H'},
    {"contrast", 2, nullptr, 'O'},
    {"read_index", 0, nullptr, 'R'},
    {"write_index", 0, nullptr, 'W'},
    {"event", 0, nullptr, 'e'},
    {"fps", 0, nullptr, 'f'},
    {"zones", 2, nullptr, 'z'},
    {"alarm", 0, nullptr, 'a'},
    {"noalarm", 0, nullptr, 'n'},
    {"cancel", 0, nullptr, 'c'},
    {"reload", 0, nullptr, 'L'},
    {"enable", 0, nullptr, 'E'},
    {"disable", 0, nullptr, 'D'},
    {"suspend", 0, nullptr, 'u'},
    {"resume", 0, nullptr, 'r'},
    {"query", 0, nullptr, 'q'},
    {"username", 1, nullptr, 'U'},
    {"password", 1, nullptr, 'P'},
    {"auth", 1, nullptr, 'A'},
    {"token", 1, nullptr, 'T'},
    {"version", 1, nullptr, 'V'},
    {"help", 0, nullptr, 'h'},
    {"list", 0, nullptr, 'l'},
    {nullptr, 0, nullptr, 0}
  };

  std::string device;
  int mon_id = 0;
  bool verbose = false;
  int function = ZMU_BOGUS;

  int image_idx = -1;
  int scale = -1;
  int brightness = -1;
  bool have_brightness = false;

  int contrast = -1;
  bool have_contrast = false;

  int hue = -1;
  bool have_hue = false;
  int colour = -1;
  bool have_colour = false;

  char *zoneString = nullptr;
  char *username = nullptr;
  char *password = nullptr;
  char *auth = nullptr;
  std::string jwt_token_str = "";
#if ZM_HAS_V4L2
    int v4lVersion = 2;
#endif // ZM_HAS_V4L2
  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "d:m:vsEDLurwei::S:t::fz::ancqhlB::C::H::O::RWU:P:A:V:T:", long_options, &option_index);
    if (c == -1) {
      break;
    }

    switch (c) {
      case 'd':
        if (optarg)
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
        if (optarg)
          image_idx = atoi(optarg);
        break;
      case 'S':
        scale = atoi(optarg);
        break;
      case 't':
        function |= ZMU_TIME;
        if (optarg)
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
        if (optarg)
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
        if (optarg) {
          have_brightness = true;
          brightness = atoi(optarg);
        }
        break;
      case 'C':
        function |= ZMU_CONTRAST;
        if (optarg) {
          have_contrast = true;
          contrast = atoi(optarg);
        }
        break;
      case 'H':
        function |= ZMU_HUE;
        if (optarg) {
          have_hue = true;
          hue = atoi(optarg);
        }
        break;
      case 'O':
        function |= ZMU_COLOUR;
        if (optarg) {
          have_colour = true;
          colour = atoi(optarg);
        }
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
#if ZM_HAS_V4L2
			case 'V':
				v4lVersion = (atoi(optarg)==1)?1:2;
				break;
#endif // ZM_HAS_V4L2
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

  if ( !device.empty() && !(function&ZMU_QUERY) ) {
    fprintf(stderr, "Error, -d option cannot be used with this option\n");
    Usage();
  }
  if ( scale != -1 && !(function&ZMU_IMAGE) ) {
    fprintf(stderr, "Error, -S option cannot be used with this option\n");
    Usage();
  }
  //printf( "Monitor %d, Function %d\n", mon_id, function );

  logInit("zmu");
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
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
			Error("Insufficient privileges for user %s for requested function %x", username, function);
			exit_zmu(-1);
		}
  } // end if auth

  if ( mon_id > 0 ) {
    std::shared_ptr<Monitor> monitor = Monitor::Load(mon_id, function&(ZMU_QUERY|ZMU_ZONES), Monitor::QUERY);
    if ( !monitor ) {
      Error("Unable to load monitor %d", mon_id);
      exit_zmu(-1);
    } // end if ! MONITOR

    if ( verbose ) {
      printf("Monitor %u(%s)\n", monitor->Id(), monitor->Name());
    }

    if (monitor->GetFunction() == Monitor::NONE) {
      if (verbose) {
        printf("Current state: None\n");
      } else {
        printf("%d", Monitor::UNKNOWN);
      }
      exit_zmu(-1);
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
        printf("%d", state);
        have_output = true;
      }
    }
    if ( function & ZMU_TIME ) {
      SystemTimePoint timestamp = monitor->GetTimestamp(image_idx);
      if (verbose) {
        char timestamp_str[64] = "None";
        if (timestamp.time_since_epoch() != Seconds(0)) {
          tm tm_info = {};
          time_t timestamp_t = std::chrono::system_clock::to_time_t(timestamp);
          strftime(timestamp_str, sizeof(timestamp_str), "%Y-%m-%d %H:%M:%S", localtime_r(&timestamp_t, &tm_info));
        }
        Seconds ts_sec = std::chrono::duration_cast<Seconds>(timestamp.time_since_epoch());
        Microseconds ts_usec = std::chrono::duration_cast<Microseconds>(timestamp.time_since_epoch() - ts_sec);
        if (image_idx == -1) {
          printf("Time of last image capture: %s.%02d\n", timestamp_str, static_cast<int32>(ts_usec.count()));
        }
        else {
          printf("Time of image %d capture: %s.%02d\n", image_idx, timestamp_str, static_cast<int32>(ts_usec.count()));
        }
      } else {
        if (have_output) {
          fputc(separator, stdout);
        }
        printf("%.2f", FPSeconds(timestamp.time_since_epoch()).count());
        have_output = true;
      }
    }
    if ( function & ZMU_READ_IDX ) {
      if ( verbose )
        printf("Last read index: %u\n", monitor->GetLastReadIndex());
      else {
        if ( have_output ) fputc(separator, stdout);
        printf("%u", monitor->GetLastReadIndex());
        have_output = true;
      }
    }
    if ( function & ZMU_WRITE_IDX ) {
      if ( verbose ) {
        printf("Last write index: %u\n", monitor->GetLastWriteIndex());
      } else {
        if ( have_output ) fputc(separator, stdout);
        printf("%u", monitor->GetLastWriteIndex());
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
        printf("Current capture rate: %.2f frames per second, analysis rate: %.2f frames per second\n",
            monitor->get_capture_fps(), monitor->get_analysis_fps());
      } else {
        if ( have_output ) fputc(separator, stdout);
        printf("capture: %.2f, analysis: %.2f", monitor->get_capture_fps(), monitor->get_analysis_fps());
        have_output = true;
      }
    }
    if ( function & ZMU_IMAGE ) {
      if ( verbose ) {
        if ( image_idx == -1 )
          printf("Dumping last image captured to Monitor%u.jpg", monitor->Id());
        else
          printf("Dumping buffer image %d to Monitor%u.jpg", image_idx, monitor->Id());
        if ( scale != -1 )
          printf(", scaling by %d%%", scale);
        printf("\n");
      }
      monitor->GetImage(image_idx, scale>0?scale:100);
    }
    if ( function & ZMU_ZONES ) {
      if ( verbose )
        printf("Dumping zone image to Zones%u.jpg\n", monitor->Id());
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

        // Ensure that we are not recording.  So the forced alarm is distinct from what was recording before
        monitor->ForceAlarmOff();
        monitor->ForceAlarmOn(config.forced_alarm_score, "Forced Web");

        Microseconds wait_time = Seconds(10);
        while ((monitor->GetState() != Monitor::ALARM) and !zm_terminate and wait_time > Seconds(0)) {
          // Wait for monitor to notice.
          Microseconds sleep = Microseconds(1);
          std::this_thread::sleep_for(sleep);
          wait_time -= sleep;
        }

        if (monitor->GetState() != Monitor::ALARM and wait_time == Seconds(0)) {
          Error("Monitor failed to respond to forced alarm.");
        } else {
          printf("Alarmed event id: %" PRIu64 "\n", monitor->GetLastEventId());
        }
      }
    }  // end if ZMU_ALARM

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
    if (function & ZMU_BRIGHTNESS) {
      if (verbose) {
        if (have_brightness)
          printf("New brightness: %d\n", monitor->actionBrightness(brightness));
        else
          printf("Current brightness: %d\n", monitor->actionBrightness());
      } else {
        if (have_output) fputc(separator, stdout);
        if (have_brightness)
          printf("%d", monitor->actionBrightness(brightness));
        else
          printf("%d", monitor->actionBrightness());
        have_output = true;
      }
    }
    if (function & ZMU_CONTRAST) {
      if (verbose) {
        if (have_contrast)
          printf("New contrast: %d\n", monitor->actionContrast(contrast));
        else
          printf("Current contrast: %d\n", monitor->actionContrast());
      } else {
        if (have_output) fputc(separator, stdout);
        if (have_contrast)
          printf("%d", monitor->actionContrast(contrast));
        else
          printf("%d", monitor->actionContrast());
        have_output = true;
      }
    }
    if (function & ZMU_HUE) {
      if (verbose) {
        if (have_hue)
          printf("New hue: %d\n", monitor->actionHue(hue));
        else
          printf("Current hue: %d\n", monitor->actionHue());
      } else {
        if (have_output) fputc(separator, stdout);
        if (have_hue)
          printf("%d", monitor->actionHue(hue));
        else
          printf("%d", monitor->actionHue());
        have_output = true;
      }
    }
    if (function & ZMU_COLOUR) {
      if (verbose) {
        if (have_colour)
          printf("New colour: %d\n", monitor->actionColour(colour));
        else
          printf("Current colour: %d\n", monitor->actionColour());
      } else {
        if (have_output) fputc(separator, stdout);
        if (have_colour)
          printf("%d", monitor->actionColour(colour));
        else
          printf("%d", monitor->actionColour());
        have_output = true;
      }
    }

    if (have_output) {
      printf("\n");
    }
    if ( !function ) {
      Usage();
    }
  } else { // non monitor functions
    if ( function & ZMU_QUERY ) {
#if ZM_HAS_V4L2
			char vidString[0x10000] = "";
			bool ok = LocalCamera::GetCurrentSettings(device, vidString, v4lVersion, verbose);
			printf("%s", vidString);
			exit_zmu(ok ? 0 : -1);
#else // ZM_HAS_V4L2
			Error("Video4linux is required for device querying");
      exit_zmu(-1);
#endif // ZM_HAS_V4L2
    }

    if ( function & ZMU_LIST ) {
      std::string sql = "SELECT `Id`, `Function`+0 FROM `Monitors`";
      if (!verbose) {
        sql += "WHERE `Function` != 'None'";
      }
      sql += " ORDER BY Id ASC";

      MYSQL_RES *result = zmDbFetch(sql);
      if (!result) {
        exit_zmu(-1);
      }
      Debug(1, "Got %" PRIu64 " monitors", static_cast<uint64>(mysql_num_rows(result)));

      printf("%4s%5s%6s%9s%14s%6s%6s%8s%8s\n", "Id", "Func", "State", "TrgState", "LastImgTim", "RdIdx", "WrIdx", "LastEvt", "FrmRate");
      for ( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
        int monitor_id = atoi(dbrow[0]);
        int monitor_function = atoi(dbrow[1]);
        if ( !user || user->canAccess(monitor_id) ) {
          if ( monitor_function > 1 ) {
            std::shared_ptr<Monitor> monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);
            if ( monitor && monitor->connect() ) {
              SystemTimePoint timestamp = monitor->GetTimestamp();

              printf( "%4d%5d%6d%9d%14.2f%6d%6d%8" PRIu64 "%8.2f\n",
                monitor->Id(),
                monitor_function,
                monitor->GetState(),
                monitor->GetTriggerState(),
                FPSeconds(timestamp.time_since_epoch()).count(),
                monitor->GetLastReadIndex(),
                monitor->GetLastWriteIndex(),
                monitor->GetLastEventId(),
                monitor->GetFPS()
              );
            }
          } else {
            printf("%4d%5d%6d%9d%11ld.%02ld%6d%6d%8d%8.2f\n",
              mon_id,
              function,
              0,
              0,
              0l, 0l,
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
  } // end if monitor id or not
  delete user;

  exit_zmu(0);
  return 0;
}  // end int main()
