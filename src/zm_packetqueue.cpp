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

using namespace std;

zm_packetqueue::zm_packetqueue() {
}

zm_packetqueue::zm_packetqueue(const zm_packetqueue& orig) {
}

zm_packetqueue::~zm_packetqueue() {
}

bool zm_packetqueue::queueVideoPacket(AVPacket* packet){
  return queuePacket(m_VideoQueue, packet);
}

bool zm_packetqueue::queueAudioPacket(AVPacket* packet)
{
	return queuePacket(m_AudioQueue, packet);
}

bool zm_packetqueue::queuePacket(queue<AVPacket>& packetQueue, AVPacket* packet){
    
  AVPacket input_ref = { 0 };
  if (av_packet_ref(&input_ref, packet) < 0){
		return false;
	}
	packetQueue.push(*packet);

	return true;
}

bool zm_packetqueue::popPacket(queue<AVPacket>& packetQueue, AVPacket* packet)
{
	if (packetQueue.empty())
	{
		return false;
	}

	*packet = packetQueue.front();
	packetQueue.pop();

	return true;
}

void zm_packetqueue::clearQueue(std::queue<AVPacket>& packetQueue)
{
	while(!packetQueue.empty())
	{
		packetQueue.pop();
	}
}

void zm_packetqueue::clearQueues()
{
  clearQueue(m_VideoQueue);
  clearQueue(m_AudioQueue);
}

bool zm_packetqueue::popAudioPacket(AVPacket* packet)
{
	return popPacket(m_AudioQueue, packet);
}

bool zm_packetqueue::popVideoPacket(AVPacket* packet)
{
	return popPacket(m_VideoQueue, packet);
}

