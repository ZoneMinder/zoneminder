#include "zm_analysis_thread.h"

AnalysisThread::AnalysisThread(Monitor *p_monitor) {
  monitor = p_monitor;
  terminate = false;
  //sigemptyset(&block_set);
}

AnalysisThread::~AnalysisThread() {
  Debug(2, "THREAD: deleteing analysis thread");
}

int AnalysisThread::run() {
  Debug(2, "AnalysisThread::run()");

  useconds_t analysis_rate = monitor->GetAnalysisRate();
  unsigned int analysis_update_delay = monitor->GetAnalysisUpdateDelay();
  time_t last_analysis_update_time, cur_time;
  monitor->UpdateAdaptiveSkip();
  last_analysis_update_time = time(0);

  while ( !(terminate or zm_terminate) ) {

    // Some periodic updates are required for variable capturing framerate
    if ( analysis_update_delay ) {
      cur_time = time(0);
      if ( (unsigned int)( cur_time - last_analysis_update_time ) > analysis_update_delay ) {
        analysis_rate = monitor->GetAnalysisRate();
        monitor->UpdateAdaptiveSkip();
        last_analysis_update_time = cur_time;
      }
    }

    Debug(2, "Analyzing");
    if ( !monitor->Analyse() ) {
Debug(2, "uSleeping for %d", (monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE));
      usleep(monitor->Active() ? ZM_SAMPLE_RATE : ZM_SUSPENDED_RATE);
    } else if ( analysis_rate ) {
Debug(2, "uSleeping for %d", analysis_rate);
      usleep(analysis_rate);
    } else {
Debug(2, "Not Sleeping");
    }

  } // end while ! terminate
  return 0;
} // end in AnalysisThread::run()
