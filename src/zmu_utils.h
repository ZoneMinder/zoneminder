#ifndef ZMU_UTILS_H
#define ZMU_UTILS_H

#include "zm_monitor.h"
#include <cerrno>
#include <cinttypes>
#include <climits>
#include <cstdlib>
#include <cstdio>
#include <string>

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
  snprintf(buf, sizeof(buf), "%4s %9s %9s %9s %5s %8s %13s %5s %5s %9s %9s",
           "Id", "Capturing", "Analysing", "Recording", "State", "TrgState",
           "LastImageTime", "RdIdx", "WrIdx", "LastEvent", "FrameRate");
  return buf;
}

inline std::string zmu_list_row(int id, int capturing, int analysing, int recording, int state,
                                int trigger_state, double last_image_time, int read_idx, int write_idx,
                                uint64_t last_event, double fps) {
  char buf[256];
  // A monitor that is not capturing has no shared memory to report on
  if (capturing <= Monitor::CAPTURING_NONE) {
    analysing = recording = state = trigger_state = read_idx = write_idx = 0;
    last_image_time = fps = 0.0;
    last_event = 0;
  }
  snprintf(buf, sizeof(buf), "%4d %9d %9d %9d %5d %8d %13.2f %5d %5d %9" PRIu64 " %9.2f",
           id, capturing, analysing, recording, state, trigger_state, last_image_time,
           read_idx, write_idx, last_event, fps);
  return buf;
}

#endif // ZMU_UTILS_H
