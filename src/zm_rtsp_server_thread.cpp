#include "zm_rtsp_server_thread.h"
#if HAVE_RTSP_SERVER
#define ZM_RTSP_SERVER_BASE 10000

RTSPServerThread::RTSPServerThread(Monitor *p_monitor) : 
  monitor(p_monitor),
  terminate(false) {

  TaskScheduler* scheduler = BasicTaskScheduler::createNew();
  UsageEnvironment* env = BasicUsageEnvironment::createNew(*scheduler);
  UserAuthenticationDatabase* authDB = NULL;
  authDB = new UserAuthenticationDatabase;
  authDB->addUserRecord("username1", "password1"); // replace these with real strings

  portNumBits rtspServerPortNum = ZM_RTSP_SERVER_BASE + monitor->Id();
  RTSPServer* rtspServer = RTSPServer::createNew(*env, rtspServerPortNum, authDB);
  if ( rtspServer == NULL) {
    Error("Failed to create rtspServer at port %d", rtspServerPortNum);
  }
  Debug(1, "RTSP prefix is %s", rtspServer->rtspURLPrefix());
}

RTSPServerThread::~RTSPServerThread() {
  Debug(2, "THREAD: deleting");
}

int RTSPServerThread::run() {
  Debug(2, "RTSPServerThread::run()");
  if ( rtspServer )
    env->taskScheduler().doEventLoop(); // does not return
  return 0;
} // end in RTSPServerThread::run()
#endif
