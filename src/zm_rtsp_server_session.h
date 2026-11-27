#ifndef ZM_RTSP_SERVER_SESSION_H
#define ZM_RTSP_SERVER_SESSION_H

#include "zm_config.h"

#if HAVE_RTSP_SERVER
#include "xop/RtspServer.h"

// Registers a session with the server. xop refuses a session whose url
// suffix is already taken and then does not own it, so it is deleted here.
// Returns false in that case.
inline bool RtspAddSession(xop::RtspServer &server, xop::MediaSession *session) {
  if (!server.AddSession(session)) {
    delete session;
    return false;
  }
  return true;
}
#endif  // HAVE_RTSP_SERVER

#endif  // ZM_RTSP_SERVER_SESSION_H
