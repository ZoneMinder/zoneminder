#include "zm_analysis_thread.h"

AnalysisThread::AnalysisThread(Monitor *p_monitor) {
  monitor = p_monitor;
  terminate = false;
  //sigemptyset(&block_set);
}

AnalysisThread::~AnalysisThread() {
  Debug(2, "THREAD: deleteing");
}

int AnalysisThread::run() {

  useconds_t analysis_rate = monitor->GetAnalysisRate();
  Debug(2, "after getanalysisrate rate is %u", analysis_rate);
  unsigned int analysis_update_delay = monitor->GetAnalysisUpdateDelay();
  Debug(2, "after getanalysisUpdateDelay delay is %u", analysis_update_delay);
  time_t last_analysis_update_time, cur_time;
  monitor->UpdateAdaptiveSkip();
  Debug(2, "after UpdateAdaptiveSkip");
  last_analysis_update_time = time(0);

  Debug(2, "THREAD: Getting ref image");
  monitor->get_ref_image();
  Debug(2, "THREAD: after Getting ref image");

  while ( !(terminate or zm_terminate) ) {
    // Process the next image
    //sigprocmask(SIG_BLOCK, &block_set, 0);

    // Some periodic updates are required for variable capturing framerate
    if ( analysis_update_delay ) {
      cur_time = time( 0 );
      if ( (unsigned int)( cur_time - last_analysis_update_time ) > analysis_update_delay ) {
        analysis_rate = monitor->GetAnalysisRate();
        monitor->UpdateAdaptiveSkip();
        last_analysis_update_time = cur_time;
      }
    }

    Debug(2, "Analyzing");
    if ( !monitor->Analyse() ) {
Debug(2, "uSleeping for %d", 10*(monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE));
      usleep(10*(monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE));
    } else if ( analysis_rate ) {
Debug(2, "uSleeping for %d", analysis_rate);
      usleep(analysis_rate);
    } else {
Debug(2, "Not Sleeping");
    }

    //sigprocmask(SIG_UNBLOCK, &block_set, 0);
  } // end while ! terminate
  return 0;
} // end in AnalysisThread::run()
