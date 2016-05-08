//ZoneMinder Packet Queue Implementation Class
//Copyright 2016 Steve Gilvarry
//
//This file is part of ZoneMinder.
//
//ZoneMinder is free software: you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation, either version 3 of the License, or
//(at your option) any later version.
//
//ZoneMinder is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with ZoneMinder.  If not, see <http://www.gnu.org/licenses/>.


#include "zm_packetqueue.h"

#define VIDEO_QUEUESIZE 200
#define AUDIO_QUEUESIZE 50

using namespace boost::interprocess;

zm_packetqueue::zm_packetqueue(const std::string &name)
: m_name(name),
msm(open_or_create, m_name.c_str(), 65536),
alloc(msm.get_segment_manager()) {

  try {

      //Construct a shared memory map.
      //Note that the first parameter is the comparison function,
      //and the second one the allocator.
      //This the same signature as std::map's constructor taking an allocator
    ptr = msm.find_or_construct<AVPacket>(m_name.c_str())(alloc);
  }  catch (...) {
      shared_memory_object::remove("MySharedMemory");
      throw;
  }
  shared_memory_object::remove("MySharedMemory");
}

zm_packetqueue::~zm_packetqueue() {
}

bool zm_packetqueue::queueVideoPacket(AVPacket* packet){
  return queuePacket(VideoQueue, packet);
}

bool zm_packetqueue::queueAudioPacket(AVPacket* packet)
{
	return queuePacket(AudioQueue, packet);
}

bool zm_packetqueue::queuePacket(std::queue<AVPacket>& pktQueue, AVPacket* packet) {
    
  AVPacket input_ref = { 0 };
  if (av_packet_ref(&input_ref, packet) < 0){
		return false;
	}
	pktQueue.push(*packet);

	return true;
}

bool zm_packetqueue::popPacket(std::queue<AVPacket>& pktQueue, AVPacket* packet)
{
	if (pktQueue.empty())
	{
		return false;
	}

	*packet = pktQueue.front();
	pktQueue.pop();

	return true;
}

void zm_packetqueue::clearQueue(std::queue<AVPacket>& pktQueue)
{
	while(!pktQueue.empty())
	{
		pktQueue.pop();
	}
}

void zm_packetqueue::clearQueues()
{
  clearQueue(VideoQueue);
  clearQueue(AudioQueue);
}

bool zm_packetqueue::popAudioPacket(AVPacket* packet)
{
	return popPacket(AudioQueue, packet);
}

bool zm_packetqueue::popVideoPacket(AVPacket* packet)
{
	return popPacket(VideoQueue, packet);
}

