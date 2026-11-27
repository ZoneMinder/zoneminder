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
  -h, --help                              - Display usage information
  -v, --version                           - Print the installed version of ZoneMinder

Options for use with devices:
  -d, --device [device_path]              - Get the current video device settings for [device_path] or all devices
  -V, --version <V4L version>             - Set the Video 4 Linux API version to use for the query, use 1 or 2
  -q, --query                             - Query the current settings for the device

Options for use with monitors:
  -m, --monitor <monitor_id>              - Specify which monitor to address, default 1 if absent
  -q, --query                             - Query the current settings for the monitor
  -s, --state                             - Output the current monitor state, 0 = unknown, 1 = idle, 2 = prealarm,
                                            3 = alarm, 4 = alert
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
      --cause <text>                      - With --alarm, the cause recorded on the event, default "Forced Web"
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
  -x, --xtrigger                          - Output the current monitor trigger state, 0 = not triggered, 1 = triggered
=cut

*/

#include "zm.h"
#include "zm_db.h"
#include "zm_user.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_local_camera.h"
#include <getopt.h>
#include <cerrno>
#include <climits>
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
    "  -s, --state          : Output the current monitor state, 0 = unknown, 1 = idle, 2 = prealarm,\n"
    "                   3 = alarm, 4 = alert\n"
    "  -j, --janus-pin     : Output the pin, if set, used to secure Janus for this monitor \n"
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
    "      --cause <text>     : With --alarm, the cause recorded on the event, default \"Forced Web\"\n"
    "  -n, --noalarm          : Force no alarms in monitor, this will prevent alarms until cancelled with -c\n"
    "  -c, --cancel           : Cancel a forced alarm/noalarm in monitor, required after being enabled with -a or -n\n"
    "  -L, --reload           : Signal monitor to reload settings\n"
    "  -E, --enable           : Enable capture, wake monitor up\n"
    "  -D, --disable          : Disable capture, put monitor to sleep\n"
    "  -u, --suspend          : Suspend motion detection, useful to prevent bogus alarms when panning etc\n"
    "  -r, --resume           : Resume motion detection after a suspend\n"
    "  -U, --username <username>    : When running in authenticated mode the username and\n"
    "  -P, --password <password>    : password combination of the given user\n"
    "  -A, --auth <authentication>  : Pass authentication hash string instead of user details\n"
    "  -T, --token <token>  : Pass JWT token string instead of user details\n"
    "  -x, --xtrigger       : Output the current monitor trigger state, 0 = not triggered, 1 = triggered\n"

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
  ZMU_TRIGGER    = 0x20000000,
  ZMU_JANUS      = 0x40000000,
} Function;

// Functions that act on one monitor and so need -m
static const int ZMU_MONITOR_FUNCTIONS = ZMU_STATE | ZMU_IMAGE | ZMU_TIME | ZMU_READ_IDX | ZMU_WRITE_IDX
    | ZMU_EVENT | ZMU_FPS | ZMU_ZONES | ZMU_ALARM | ZMU_NOALARM | ZMU_CANCEL | ZMU_BRIGHTNESS | ZMU_CONTRAST
    | ZMU_HUE | ZMU_COLOUR | ZMU_RELOAD | ZMU_ENABLE | ZMU_DISABLE | ZMU_SUSPEND | ZMU_RESUME | ZMU_TRIGGER
    | ZMU_JANUS;

// Parse the -m argument. Returns the monitor id, or -1 if it is not a positive integer
inline int zmu_parse_monitor_id(const char *arg) {
  if (!arg || !*arg) return -1;
  char *end = nullptr;
  errno = 0;
  long id = strtol(arg, &end, 10);
  if (errno || *end || id <= 0 || id > INT_MAX) return -1;
  return static_cast<int>(id);
}

// Parse an integer option argument. Returns false unless the whole string is an integer that fits in an int
inline bool zmu_parse_int(const char *arg, int *value) {
  if (!arg || !*arg) return false;
  char *end = nullptr;
  errno = 0;
  long v = strtol(arg, &end, 10);
  if (errno || *end || v < INT_MIN || v > INT_MAX) return false;
  *value = static_cast<int>(v);
  return true;
}

// Returns an error message when the options given cannot work, or an empty string
inline std::string zmu_check_options(int function, int mon_id) {
  if ((function & ZMU_MONITOR_FUNCTIONS) && mon_id <= 0)
    return "this option needs a monitor, specify it with -m";
  return "";
}

// Separator to print before a value in the non verbose single line output
inline std::string zmu_separator(bool have_output) {
  return have_output ? " " : "";
}

inline const char *zmu_state_name(Monitor::State state) {
  switch (state) {
  case Monitor::IDLE: return "Idle";
  case Monitor::PREALARM: return "Prealarm";
  case Monitor::ALARM: return "Alarm";
  case Monitor::ALERT: return "Alert";
  default: return "Unknown";
  }
}

inline std::string zmu_list_header() {
  char buf[256];
  snprintf(buf, sizeof(buf), "%4s %9s %9s %9s %5s %8s %13s %5s %5s %9s %9s %s",
           "Id", "Capturing", "Analysing", "Recording", "State", "TrgState",
           "LastImageTime", "RdIdx", "WrIdx", "LastEvent", "FrameRate", "Name");
  return buf;
}

inline std::string zmu_list_row(int id, const char *name, int capturing, int analysing, int recording,
                                int state, int trigger_state, double last_image_time, int read_idx,
                                int write_idx, uint64_t last_event, double fps) {
  char buf[256];
  // A monitor that is not capturing has no shared memory to report on
  if (capturing <= Monitor::CAPTURING_NONE) {
    analysing = recording = state = trigger_state = read_idx = write_idx = 0;
    last_image_time = fps = 0.0;
    last_event = 0;
  }
  // The name goes last and in full: it can hold spaces, so a column after it
  // would no longer be the same whitespace-separated field on every row.
  snprintf(buf, sizeof(buf), "%4d %9d %9d %9d %5d %8d %13.2f %5d %5d %9" PRIu64 " %9.2f ",
           id, capturing, analysing, recording, state, trigger_state, last_image_time,
           read_idx, write_idx, last_event, fps);
  return std::string(buf) + (name ? name : "");
}

// getopt_long value for --cause, which has no short form
static const int ZMU_OPT_CAUSE = 256;

bool ValidateAccess(User *user, int mon_id, int function) {
  bool allowed = true;
  if ( function & (ZMU_STATE|ZMU_IMAGE|ZMU_TIME|ZMU_READ_IDX|ZMU_WRITE_IDX|ZMU_FPS|ZMU_TRIGGER|ZMU_JANUS) ) {
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
  if ( function & (ZMU_ALARM|ZMU_CANCEL|ZMU_NOALARM|ZMU_RELOAD|ZMU_ENABLE|ZMU_DISABLE|ZMU_SUSPEND|ZMU_RESUME|ZMU_BRIGHTNESS|ZMU_CONTRAST|ZMU_HUE|ZMU_COLOUR) ) {
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
  dbQueue.stop();
  logTerm();
  zmDbClose();

  exit(exit_code);
}

static int zmu_int_option(const char *arg, char opt) {
  int value = 0;
  if (!zmu_parse_int(arg, &value)) {
    fprintf(stderr, "Error, invalid value '%s' for -%c\n", arg ? arg : "", opt);
    Usage();
  }
  return value;
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
    {"janus-pin", 0, nullptr, 'j'},
    {"brightness", 2, nullptr, 'B'},
    {"contrast", 2, nullptr, 'C'},
    {"hue", 2, nullptr, 'H'},
    {"colour", 2, nullptr, 'O'},
    {"read_index", 0, nullptr, 'R'},
    {"write_index", 0, nullptr, 'W'},
    {"event", 0, nullptr, 'e'},
    {"fps", 0, nullptr, 'f'},
    {"zones", 2, nullptr, 'z'},
    {"alarm", 0, nullptr, 'a'},
    {"cause", 1, nullptr, ZMU_OPT_CAUSE},
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
    {"xtrigger", 0, nullptr, 'x'},
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
  std::string alarm_cause = "Forced Web";
  std::string username;
  std::string password;
  char *auth = nullptr;
  std::string jwt_token_str = "";
#if ZM_HAS_V4L2
  int v4lVersion = 2;
#endif // ZM_HAS_V4L2
  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "d:m:vsjEDLurwei::xS:t::fz::ancqhlB::C::H::O::RWU:P:A:V:T:", long_options, &option_index);
    if (c == -1) {
      break;
    }

    switch (c) {
    case 'd':
      if (optarg)
        device = optarg;
      break;
    case 'm':
      mon_id = zmu_parse_monitor_id(optarg);
      if (mon_id < 0) {
        fprintf(stderr, "Error, invalid monitor id '%s'\n", optarg);
        Usage();
      }
      break;
    case 'v':
      verbose = true;
      break;
    case 's':
      function |= ZMU_STATE;
      break;
    case 'j':
      function |= ZMU_JANUS;
      break;
    case 'x':
      function |= ZMU_TRIGGER;
      break;
    case 'i':
      function |= ZMU_IMAGE;
      if (optarg)
        image_idx = zmu_int_option(optarg, 'i');
      break;
    case 'S':
      scale = zmu_int_option(optarg, 'S');
      break;
    case 't':
      function |= ZMU_TIME;
      if (optarg)
        image_idx = zmu_int_option(optarg, 't');
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
    case ZMU_OPT_CAUSE:
      if (optarg && *optarg)
        alarm_cause = optarg;
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
        brightness = zmu_int_option(optarg, 'B');
      }
      break;
    case 'C':
      function |= ZMU_CONTRAST;
      if (optarg) {
        have_contrast = true;
        contrast = zmu_int_option(optarg, 'C');
      }
      break;
    case 'H':
      function |= ZMU_HUE;
      if (optarg) {
        have_hue = true;
        hue = zmu_int_option(optarg, 'H');
      }
      break;
    case 'O':
      function |= ZMU_COLOUR;
      if (optarg) {
        have_colour = true;
        colour = zmu_int_option(optarg, 'O');
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
      Usage(0);
      break;
    case '?':
      Usage(1);
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
  {
    std::string option_error = zmu_check_options(function, mon_id);
    if (!option_error.empty()) {
      fprintf(stderr, "Error, %s\n", option_error.c_str());
      Usage();
    }
  }
  //printf( "Monitor %d, Function %d\n", mon_id, function );

  logInit("zmu");
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit("zmu");

  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  int status = 0;
  User *user = 0;

  if ( config.opt_use_auth ) {
    if ( jwt_token_str != "" ) {
      user = zmLoadTokenUser(jwt_token_str, false);
    } else if ( strcmp(config.auth_relay, "none") == 0 ) {
      if (username.empty()) {
        Error("Username must be supplied");
        exit_zmu(-1);
      }

      if (!checkUser(username)) {
        Error("Username greater than allowed 64 characters");
        exit_zmu(-1);
      }

      user = zmLoadUser(username);
    } else {

      if ( !(!username.empty() && !password.empty()) && !auth ) {
        Error("Username and password or auth/token string must be supplied");
        exit_zmu(-1);
      }
      if ( auth ) {
        user = zmLoadAuthUser(auth, username, false);
      }
      if ( !username.empty() && !password.empty() ) {
        if ( !checkUser(username)) {
          Error("username greater than allowed 64 characters");
          exit_zmu(-1);
        }
        if ( !checkPass(password)) {
          Error("Password must be supplied");
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
      Error("Insufficient privileges for user %s for requested function %x", username.c_str(), function);
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

    if (monitor->Capturing() == Monitor::CAPTURING_NONE) {
      if (verbose) {
        printf("Current capturing state: None\n");
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
    if ( function & ZMU_JANUS ) {
      printf("%s", monitor->get_stream_key());
      have_output = true;
    }
    if ( function & ZMU_STATE ) {
      Monitor::State state = monitor->GetState();
      if ( verbose ) {
        printf("Current state: %s\n", zmu_state_name(state));
      } else {
        fputs(zmu_separator(have_output).c_str(), stdout);
        printf("%d", state);
        have_output = true;
      }
    }
    if ( function & ZMU_TRIGGER ) {
      int trgstate = monitor->GetTriggerState();
      if ( verbose ) {
        printf("Current Triggered state: %s\n", trgstate==0?"Not Triggered":(trgstate==1?"Triggered":"NA"));
      } else {
        if (have_output) fputc(separator, stdout);
        printf("%d", trgstate);
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
          printf("Time of last image capture: %s.%02d\n", timestamp_str, static_cast<int32>(ts_usec.count() / 10000));
        } else {
          printf("Time of image %d capture: %s.%02d\n", image_idx, timestamp_str, static_cast<int32>(ts_usec.count() / 10000));
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
      if (!monitor->GetImage(image_idx, scale>0?scale:100)) {
        status = -1;
      }
    }
    if ( function & ZMU_ZONES ) {
      if ( verbose )
        printf("Dumping zone image to Zones%u.jpg\n", monitor->Id());
      if (!monitor->DumpZoneImage(zoneString)) {
        status = -1;
      }
    }
    if ( function & ZMU_ALARM ) {
      Monitor::State state = monitor->GetState();

      if (verbose) {
        printf("Forcing alarm on current state: %s, event %" PRIu64 "\n",
               zmu_state_name(state),
               monitor->GetLastEventId()
              );
      }

      // Ensure that we are not recording.  So the forced alarm is distinct from what was recording before
      monitor->ForceAlarmOff();
      monitor->ForceAlarmOn(config.forced_alarm_score, alarm_cause.c_str());

      TimePoint deadline = std::chrono::steady_clock::now() + Seconds(10);
      while ((monitor->GetState() != Monitor::ALARM) and !zm_terminate and std::chrono::steady_clock::now() < deadline) {
        // Wait for monitor to notice.
        std::this_thread::sleep_for(Milliseconds(5));
      }

      if (monitor->GetState() != Monitor::ALARM) {
        Error("Monitor failed to respond to forced alarm.");
        exit_zmu(-1);
      }
      printf("Alarmed event id: %" PRIu64 "\n", monitor->GetLastEventId());
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
        printf("Enabling capturing\n");
      monitor->actionEnable();
    }
    if ( function & ZMU_DISABLE ) {
      if ( verbose )
        printf("Disabling capturing\n");
      monitor->actionDisable();
    }
    if ( function & ZMU_SUSPEND ) {
      if ( verbose )
        printf("Suspending motion detection\n");
      monitor->actionSuspend();
    }
    if ( function & ZMU_RESUME ) {
      if ( verbose )
        printf("Resuming motion detection\n");
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
  } // end if monitor id or not

  if (function & ZMU_LIST) {
    std::string sql = "SELECT `Id`, `Name`, `Capturing`+0, `Analysing`+0, `Recording`+0 FROM `Monitors`";
    if (!verbose) {
      sql += " WHERE `Capturing` != 'None'";
    }
    sql += " ORDER BY Id ASC";

    MYSQL_RES *result = zmDbFetch(sql);
    if (!result) {
      exit_zmu(-1);
    }
    Debug(1, "Got %" PRIu64 " monitors", static_cast<uint64>(mysql_num_rows(result)));

    printf("%s\n", zmu_list_header().c_str());
    for (MYSQL_ROW dbrow = mysql_fetch_row(result); dbrow; dbrow = mysql_fetch_row(result)) {
      int monitor_id = atoi(dbrow[0]);
      if (mon_id and (monitor_id != mon_id)) continue;
      if (!user || user->canAccess(monitor_id)) {
        int monitor_capturing = atoi(dbrow[2]);
        if (monitor_capturing > Monitor::CAPTURING_NONE) {
          std::shared_ptr<Monitor> monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);
          if (monitor && monitor->connect()) {
            SystemTimePoint timestamp = monitor->GetTimestamp();

            printf("%s\n", zmu_list_row(monitor->Id(), monitor->Name(), monitor->Capturing(), monitor->Analysing(),
                   monitor->Recording(), monitor->GetState(), monitor->GetTriggerState(),
                   FPSeconds(timestamp.time_since_epoch()).count(), monitor->GetLastReadIndex(),
                   monitor->GetLastWriteIndex(), monitor->GetLastEventId(), monitor->GetFPS()).c_str());
          }
        } else {
          printf("%s\n", zmu_list_row(monitor_id, dbrow[1], monitor_capturing, 0, 0, 0, 0, 0.0, 0, 0, 0, 0.0).c_str());
        } // end if function filter
      } // endif !user || canAccess(mon_id)
    } // end foreach row
    mysql_free_result(result);
  } // end if function && ZMU_LIST
  delete user;

  exit_zmu(status);
  return 0;
}  // end int main()
