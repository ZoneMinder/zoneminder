//
// ZoneMinder Monitor Class Implementation, $Date$, $Revision$
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

#include "zm_monitor.h"

#include <sys/stat.h>

#if ZM_MEM_MAPPED
#include <sys/mman.h>
#include <fcntl.h>
#include <unistd.h>
#else // ZM_MEM_MAPPED
#include <sys/ipc.h>
#include <sys/shm.h>
#endif // ZM_MEM_MAPPED

Monitor::MonitorLink::MonitorLink(unsigned int p_id, const char *p_name) :
  id(p_id),
  shared_data(nullptr),
  trigger_data(nullptr),
  video_store_data(nullptr)
{
  strncpy(name, p_name, sizeof(name)-1);

#if ZM_MEM_MAPPED
  map_fd = -1;
  mem_file = stringtf("%s/zm.mmap.%u", staticConfig.PATH_MAP.c_str(), id);
#else // ZM_MEM_MAPPED
  shm_id = 0;
#endif // ZM_MEM_MAPPED
  mem_size = 0;
  mem_ptr = nullptr;

  last_event_id = 0;
  last_state = IDLE;

  last_connect_time = 0;
  connected = false;
}

Monitor::MonitorLink::~MonitorLink() {
  disconnect();
}

bool Monitor::MonitorLink::connect() {
  SystemTimePoint now = std::chrono::system_clock::now();
  if (!last_connect_time || (now - std::chrono::system_clock::from_time_t(last_connect_time)) > Seconds(60)) {
    last_connect_time = std::chrono::system_clock::to_time_t(now);

    mem_size = sizeof(SharedData) + sizeof(TriggerData);

    Debug(1, "link.mem.size=%jd", static_cast<intmax_t>(mem_size));
#if ZM_MEM_MAPPED
    map_fd = open(mem_file.c_str(), O_RDWR, (mode_t)0600);
    if (map_fd < 0) {
      Debug(3, "Can't open linked memory map file %s: %s", mem_file.c_str(), strerror(errno));
      disconnect();
      return false;
    }
    while (map_fd <= 2) {
      int new_map_fd = dup(map_fd);
      Warning("Got one of the stdio fds for our mmap handle. map_fd was %d, new one is %d", map_fd, new_map_fd);
      close(map_fd);
      map_fd = new_map_fd;
    }

    struct stat map_stat;
    if (fstat(map_fd, &map_stat) < 0) {
      Error("Can't stat linked memory map file %s: %s", mem_file.c_str(), strerror(errno));
      disconnect();
      return false;
    }

    if (map_stat.st_size == 0) {
      Error("Linked memory map file %s is empty: %s", mem_file.c_str(), strerror(errno));
      disconnect();
      return false;
    } else if (map_stat.st_size < mem_size) {
      Error("Got unexpected memory map file size %ld, expected %jd", map_stat.st_size, static_cast<intmax_t>(mem_size));
      disconnect();
      return false;
    }

    mem_ptr = (unsigned char *)mmap(nullptr, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0);
    if (mem_ptr == MAP_FAILED) {
      Error("Can't map file %s (%jd bytes) to memory: %s", mem_file.c_str(), static_cast<intmax_t>(mem_size), strerror(errno));
      disconnect();
      return false;
    }
#else // ZM_MEM_MAPPED
    shm_id = shmget((config.shm_key&0xffff0000)|id, mem_size, 0700);
    if (shm_id < 0) {
      Debug(3, "Can't shmget link memory: %s", strerror(errno));
      connected = false;
      return false;
    }
    mem_ptr = (unsigned char *)shmat(shm_id, 0, 0);
    if ((int)mem_ptr == -1) {
      Debug(3, "Can't shmat link memory: %s", strerror(errno));
      connected = false;
      return false;
    }
#endif // ZM_MEM_MAPPED

    shared_data = (SharedData *)mem_ptr;
    trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));

    if (!shared_data->valid) {
      Debug(3, "Linked memory not initialised by capture daemon");
      disconnect();
      return false;
    }

    last_state = shared_data->state;
    last_event_id = shared_data->last_event_id;
    connected = true;

    return true;
  }
  return false;
}  // end bool Monitor::MonitorLink::connect()

bool Monitor::MonitorLink::disconnect() {
  if (connected) {
    connected = false;

#if ZM_MEM_MAPPED
    if (mem_ptr > (void *)0) {
      msync(mem_ptr, mem_size, MS_ASYNC);
      munmap(mem_ptr, mem_size);
    }
    if (map_fd >= 0)
      close(map_fd);

    map_fd = -1;
#else // ZM_MEM_MAPPED
    struct shmid_ds shm_data;
    if (shmctl(shm_id, IPC_STAT, &shm_data) < 0) {
      Debug(3, "Can't shmctl: %s", strerror(errno));
      return false;
    }

    shm_id = 0;

    if (shm_data.shm_nattch <= 1) {
      if (shmctl(shm_id, IPC_RMID, 0) < 0) {
        Debug(3, "Can't shmctl: %s", strerror(errno));
        return false;
      }
    }

    if (shmdt(mem_ptr) < 0) {
      Debug(3, "Can't shmdt: %s", strerror(errno));
      return false;
    }
#endif // ZM_MEM_MAPPED
    mem_size = 0;
    mem_ptr = nullptr;
  }
  return true;
}

bool Monitor::MonitorLink::isAlarmed() {
  if (!connected) {
    return false;
  }
  return( shared_data->state == ALARM );
}

bool Monitor::MonitorLink::inAlarm() {
  if (!connected) {
    return false;
  }
  return( shared_data->state == ALARM || shared_data->state == ALERT );
}

bool Monitor::MonitorLink::hasAlarmed() {
  if (shared_data->state == ALARM) {
    return true;
  }
  last_event_id = shared_data->last_event_id;
  return false;
}
