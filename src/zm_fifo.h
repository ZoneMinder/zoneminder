#ifndef ZM_FIFO_H
#define ZM_FIFO_H

#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <limits.h>
#include <time.h>
#include <sys/time.h>
#include <sys/stat.h>
#include <sys/types.h>

#include "zm.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_stream.h"


#define zmFifoDbgPrintf(level,params...)	{\
	zmFifoDbgOutput( 0, __FILE__, __LINE__, level, ##params );\
				}

#ifndef ZM_DBG_OFF
#define FifoDebug(level,params...)	zmFifoDbgPrintf(level,##params)
#else
#define FifoDebug(level,params...)
#endif
void zmFifoDbgOutput( int hex, const char * const file, const int line, const int level, const char *fstring, ... ) __attribute__ ((format(printf, 5, 6)));
int zmFifoDbgInit(Monitor * monitor);

class FifoStream : public StreamBase
{

private:
	char * stream_path;
	int fd;
	int total_read;
	int bytes_read;
	unsigned int frame_count;
	static void file_create_if_missing(const char * path, bool is_fifo, bool delete_fake_fifo=true);

protected:
	typedef enum { MJPEG, RAW } StreamType;
	StreamType	stream_type;
	bool sendMJEGFrames(  );
	bool sendRAWFrames(  );
	void processCommand( const CmdMsg *msg ) {};

public:
	FifoStream(){


	}
	static void fifo_create_if_missing(const char * path,bool delete_fake_fifo=true);
	void setStreamStart( const char * path );
	void setStreamStart( int monitor_id, const char * format );
	
	void runStream();
};
#endif
