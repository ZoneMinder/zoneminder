//
// ZoneMinder RTSP Daemon
// Copyright (C) 2021 Isaac Connor
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

zm_rtsp_server - The ZoneMinder Server

=head1 SYNOPSIS

 zmc -m <monitor_id>
 zmc --monitor <monitor_id>
 zmc -h
 zmc --help
 zmc -v
 zmc --version

=head1 DESCRIPTION

This binary's job is to connect to fifo's provided by local zmc processes
and provide that stream over rtsp

=head1 OPTIONS

 -m, --monitor_id           - ID of a monitor to stream
 -h, --help                 - Display usage information
 -v, --version              - Print the installed version of ZoneMinder

=cut

*/

#include "zm.h"
#include "zm_db.h"
#include "zm_define.h"
#include "zm_monitor.h"
#include "zm_rtsp_server_authenticator.h"
#include "zm_rtsp_server_fifo_h264_source.h"
#include "zm_rtsp_server_fifo_adts_source.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"
#include <getopt.h>
#include <iostream>


#include "xop/RtspServer.h"

void Usage() {
  fprintf(stderr, "zm_rtsp_server -m <monitor_id>\n");

  fprintf(stderr, "Options:\n");
  fprintf(stderr, "  -m, --monitor <monitor_id> : We default to all monitors use this to specify just one\n");
  fprintf(stderr, "  -h, --help                 : This screen\n");
  fprintf(stderr, "  -v, --version              : Report the installed version of ZoneMinder\n");
  exit(0);
}

int main(int argc, char *argv[]) {
  self = argv[0];

  srand(getpid() * time(nullptr));

  int monitor_id = -1;

  static struct option long_options[] = {
    {"monitor", 1, nullptr, 'm'},
    {"help", 0, nullptr, 'h'},
    {"version", 0, nullptr, 'v'},
    {nullptr, 0, nullptr, 0}
  };

  while (1) {
    int option_index = 0;

    int c = getopt_long(argc, argv, "m:h:v", long_options, &option_index);
    if ( c == -1 ) {
      break;
    }

    switch (c) {
      case 'm':
        monitor_id = atoi(optarg);
        break;
      case 'h':
      case '?':
        Usage();
        break;
      case 'v':
        std::cout << ZM_VERSION << "\n";
        exit(0);
      default:
        // fprintf(stderr, "?? getopt returned character code 0%o ??\n", c);
        break;
    }
  }

  if (optind < argc) {
    fprintf(stderr, "Extraneous options, ");
    while (optind < argc)
      printf("%s ", argv[optind++]);
    printf("\n");
    Usage();
  }

  const char *log_id_string = "zm_rtsp_server";
  ///std::string log_id_string = std::string("zm_rtsp_server");
  ///if ( monitor_id > 0 ) log_id_string += stringtf("_m%d", monitor_id);

  logInit(log_id_string);
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit(log_id_string);
  if (!config.min_rtsp_port) {
    Debug(1, "Not starting RTSP server because min_rtsp_port not set");
    exit(-1);
  }

  HwCapsDetect();

  std::string where = "`Function` != 'None' AND `RTSPServer` != false";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  if (monitor_id > 0)
    where += stringtf(" AND `Id`=%d", monitor_id);

  std::vector<std::shared_ptr<Monitor>> monitors = Monitor::LoadMonitors(where, Monitor::QUERY);

  if (monitors.empty()) {
    Error("No monitors found");
    exit(-1);
  } else {
	  Debug(2, "%d monitors loaded", monitors.size());
  }

  Info("Starting RTSP Server version %s", ZM_VERSION);
  zmSetDefaultHupHandler();
  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  std::shared_ptr<xop::EventLoop> eventLoop(new xop::EventLoop());
	std::shared_ptr<xop::RtspServer> rtspServer = xop::RtspServer::Create(eventLoop.get());

  if (config.opt_use_auth) {
    std::shared_ptr<ZM_RtspServer_Authenticator> authenticator(new ZM_RtspServer_Authenticator());
    rtspServer->SetAuthenticator(authenticator);
  }

  if (!rtspServer->Start("0.0.0.0", config.min_rtsp_port)) {
    Debug(1, "Failed starting RTSP server on port %d", config.min_rtsp_port);
    exit(-1);
  }

  xop::MediaSession **sessions = new xop::MediaSession *[monitors.size()];
  for (size_t i = 0; i < monitors.size(); i++) sessions[i] = nullptr;

  std::list<ZoneMinderFifoSource *> sources;

  while (!zm_terminate) {
    for (size_t i = 0; i < monitors.size(); i++) {
      std::shared_ptr<Monitor> monitor = monitors[i];

      if (!monitor->ShmValid()) {
         monitor->disconnect();
         if (!monitor->connect()) {
           Warning("Couldn't connect to monitor %d", monitor->Id());
           if (sessions[i]) {
             rtspServer->RemoveSession(sessions[i]->GetMediaSessionId());
             sessions[i] = nullptr;
           }
           continue;
         }
      }

      if (!sessions[i]) {
        std::string videoFifoPath = monitor->GetVideoFifoPath();
        if (videoFifoPath.empty()) {
          Debug(1, "video fifo is empty. Skipping.");
          continue;
        }
        std::string streamname = monitor->GetRTSPStreamName();

        xop::MediaSession *session = sessions[i] = xop::MediaSession::CreateNew(streamname);
        if (session) {
          session->AddNotifyConnectedCallback([] (xop::MediaSessionId sessionId, std::string peer_ip, uint16_t peer_port){
              Debug(1, "RTSP client connect, ip=%s, port=%hu \n", peer_ip.c_str(), peer_port);
              });

          session->AddNotifyDisconnectedCallback([](xop::MediaSessionId sessionId, std::string peer_ip, uint16_t peer_port) {
              Debug(1, "RTSP client disconnect, ip=%s, port=%hu \n", peer_ip.c_str(), peer_port);
              });

          rtspServer->AddSession(session);
          //char *url = rtspServer->rtspURL(session);
          //Debug(1, "url is %s for stream %s", url, streamname.c_str());
          //delete[] url;
        }

        Debug(1, "Adding video fifo %s", videoFifoPath.c_str());
        ZoneMinderFifoVideoSource *videoSource = nullptr;

        if (std::string::npos != videoFifoPath.find("h264")) {
          session->AddSource(xop::channel_0, xop::H264Source::CreateNew());
          videoSource = new H264_ZoneMinderFifoSource(rtspServer, session->GetMediaSessionId(), xop::channel_0, videoFifoPath);
        } else if (
            std::string::npos != videoFifoPath.find("hevc")
            or
            std::string::npos != videoFifoPath.find("h265")) {
          session->AddSource(xop::channel_0, xop::H265Source::CreateNew());
          videoSource = new H265_ZoneMinderFifoSource(rtspServer, session->GetMediaSessionId(), xop::channel_0, videoFifoPath);
        } else {
          Warning("Unknown format in %s", videoFifoPath.c_str());
        }
        if (videoSource == nullptr) {
          Error("Unable to create source");
        }
        sources.push_back(videoSource);
        if (videoSource) {
          videoSource->setWidth(monitor->Width());
          videoSource->setHeight(monitor->Height());
        }

        std::string audioFifoPath = monitor->GetAudioFifoPath();
        if (audioFifoPath.empty()) {
          Debug(1, "audio fifo is empty. Skipping.");
          continue;
        }
        Debug(1, "Adding audio fifo %s", audioFifoPath.c_str());

        ZoneMinderFifoAudioSource *audioSource = nullptr;

        if (std::string::npos != audioFifoPath.find("aac")) {
          Debug(1, "Adding aac source at %dHz %d channels", monitor->GetAudioFrequency(), monitor->GetAudioChannels());
          session->AddSource(xop::channel_1, xop::AACSource::CreateNew(
                monitor->GetAudioFrequency(),
                monitor->GetAudioChannels(),
                false /* has_adts */));
          audioSource = new ADTS_ZoneMinderFifoSource(rtspServer, session->GetMediaSessionId(), xop::channel_1, audioFifoPath);
          audioSource->setFrequency(monitor->GetAudioFrequency());
          audioSource->setChannels(monitor->GetAudioChannels());
        } else {
          Warning("Unknown format in %s", audioFifoPath.c_str());
        }
        if (audioSource == nullptr) {
          Error("Unable to create source");
        }
        sources.push_back(audioSource);
      }  // end if ! sessions[i]
    }  // end foreach monitor

    sleep(1);

    if (zm_reload) {
      for (size_t i = 0; i < monitors.size(); i++) {
        monitors[i]->Reload();
      }
      logTerm();
      logInit(log_id_string);
      zm_reload = false;
    }  // end if zm_reload
  } // end while !zm_terminate
  Info("RTSP Server shutting down");

  for (size_t i = 0; i < monitors.size(); i++) {
    if (sessions[i]) {
      Debug(1, "Removing session for %s", monitors[i]->Name());
      rtspServer->RemoveSession(sessions[i]->GetMediaSessionId());
      sessions[i] = nullptr;
    }
  }  // end foreach monitor

  for ( std::list<ZoneMinderFifoSource *>::iterator it = sources.begin(); it != sources.end(); ++it ) {
    Debug(1, "RTSPServerThread::stopping source");
    (*it)->Stop();
  }
  while (sources.size()) {
    Debug(1, "RTSPServerThread::stop closing source");
    ZoneMinderFifoSource *source = sources.front();
    sources.pop_front();
    delete source;
  }

  rtspServer->Stop();

  delete[] sessions;
  sessions = nullptr;

  Image::Deinitialise();
  logTerm();
  zmDbClose();

	return 0;
}
