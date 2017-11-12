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
  unsigned int analysis_update_delay = monitor->GetAnalysisUpdateDelay();
  time_t last_analysis_update_time, cur_time;
  monitor->UpdateAdaptiveSkip();
  last_analysis_update_time = time(0);

  Debug(2, "THREAD: Getting ref image");
  monitor->get_ref_image();

  while( !terminate ) {
    // Process the next image
    //sigprocmask(SIG_BLOCK, &block_set, 0);

    // Some periodic updates are required for variable capturing framerate
    if ( analysis_update_delay ) {
      cur_time = time( 0 );
      if ( (unsigned int)( cur_time - last_analysis_update_time ) > analysis_update_delay ) {
Debug(4, "Updating " );
        analysis_rate = monitor->GetAnalysisRate();
        monitor->UpdateAdaptiveSkip();
        last_analysis_update_time = cur_time;
      }
    } else {
Debug(4, "Not Updating " );
    }

    if ( !monitor->Analyse() ) {
Debug(4, "Sleeping for %d", monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE);
      usleep(monitor->Active()?ZM_SAMPLE_RATE:ZM_SUSPENDED_RATE);
    } else if ( analysis_rate ) {
Debug(4, "Sleeping for %d", analysis_rate);
      usleep(analysis_rate);
    }

    //sigprocmask(SIG_UNBLOCK, &block_set, 0);
  } // end while ! terminate
  return 0;
} // end in AnalysisThread::run()
