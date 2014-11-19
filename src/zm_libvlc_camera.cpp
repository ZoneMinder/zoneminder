/*
 * ZoneMinder Libvlc Camera Class Implementation, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/  

#include "zm.h"
#include "zm_libvlc_camera.h"

#if HAVE_LIBVLC

// Do all the buffer checking work here to avoid unnecessary locking 
void* LibvlcLockBuffer(void* opaque, void** planes)
{
    LibvlcPrivateData* data = (LibvlcPrivateData*)opaque;
    data->mutex.lock();
    
    uint8_t* buffer = data->buffer;
    data->buffer = data->prevBuffer;
    data->prevBuffer = buffer;
    
    *planes = data->buffer;
    return NULL;
}

void LibvlcUnlockBuffer(void* opaque, void* picture, void *const *planes)
{
    LibvlcPrivateData* data = (LibvlcPrivateData*)opaque;
    
    bool newFrame = false;
    for(uint32_t i = 0; i < data->bufferSize; i++)
    {
        if(data->buffer[i] != data->prevBuffer[i])
        {
            newFrame = true;
            break;
        }
    }
    data->mutex.unlock();
    
    time_t now;
    time(&now);
    // Return frames slightly faster than 1fps (if time() supports greater than one second resolution)
    if(newFrame || difftime(now, data->prevTime) >= 0.8)
    {
        data->prevTime = now;
        data->newImage.updateValueSignal(true);
    }
}

LibvlcCamera::LibvlcCamera( int p_id, const std::string &p_path, const std::string &p_method, const std::string &p_options, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    Camera( p_id, LIBVLC_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    mPath( p_path ),
    mMethod( p_method ),
    mOptions( p_options )
{	
	mLibvlcInstance = NULL;
    mLibvlcMedia = NULL;
	mLibvlcMediaPlayer = NULL;
    mLibvlcData.buffer = NULL;
    mLibvlcData.prevBuffer = NULL;

	/* Has to be located inside the constructor so other components such as zma will receive correct colours and subpixel order */
	if(colours == ZM_COLOUR_RGB32) {
		subpixelorder = ZM_SUBPIX_ORDER_BGRA;
        mTargetChroma = "RV32";
        mBpp = 4;
	} else if(colours == ZM_COLOUR_RGB24) {
        subpixelorder = ZM_SUBPIX_ORDER_BGR;
        mTargetChroma = "RV24";
        mBpp = 3;
	} else if(colours == ZM_COLOUR_GRAY8) {
		subpixelorder = ZM_SUBPIX_ORDER_NONE;
        mTargetChroma = "GREY";
        mBpp = 1;
	} else {
		Panic("Unexpected colours: %d",colours);
	}
    
	if ( capture )
	{
		Initialise();
	}
}

LibvlcCamera::~LibvlcCamera()
{
	if ( capture )
	{
		Terminate();
	}
    if(mLibvlcMediaPlayer != NULL)
    {
        libvlc_media_player_release(mLibvlcMediaPlayer);
        mLibvlcMediaPlayer = NULL;
    }
    if(mLibvlcMedia != NULL)
    {
        libvlc_media_release(mLibvlcMedia);
        mLibvlcMedia = NULL;
    }
    if(mLibvlcInstance != NULL)
    {
        libvlc_release(mLibvlcInstance);
        mLibvlcInstance = NULL;
    }
    if (mOptArgV != NULL)
    {
    	delete[] mOptArgV;
    }
}

void LibvlcCamera::Initialise()
{
}

void LibvlcCamera::Terminate()
{
    libvlc_media_player_stop(mLibvlcMediaPlayer);
    if(mLibvlcData.buffer != NULL)
    {
        zm_freealigned(mLibvlcData.buffer);
    }
    if(mLibvlcData.prevBuffer != NULL)
    {
        zm_freealigned(mLibvlcData.prevBuffer);
    }
}

int LibvlcCamera::PrimeCapture()
{
    Info("Priming capture from %s", mPath.c_str());
    
    StringVector opVect = split(Options(), ",");
    
    // Set transport method as specified by method field, rtpUni is default
    if ( Method() == "rtpMulti" )
    	opVect.push_back("--rtsp-mcast");
    else if ( Method() == "rtpRtsp" )
        opVect.push_back("--rtsp-tcp");
    else if ( Method() == "rtpRtspHttp" )
        opVect.push_back("--rtsp-http");

    if (opVect.size() > 0) 
    {
    	mOptArgV = new char*[opVect.size()];
    	Debug(2, "Number of Options: %d",opVect.size());
    	for (size_t i=0; i< opVect.size(); i++) {
    		opVect[i] = trimSpaces(opVect[i]);
    		mOptArgV[i] = (char *)opVect[i].c_str();
    		Debug(2, "set option %d to '%s'", i,  opVect[i].c_str());
    	}
    }

    mLibvlcInstance = libvlc_new (opVect.size(), (const char* const*)mOptArgV);
    if(mLibvlcInstance == NULL)
        Fatal("Unable to create libvlc instance due to: %s", libvlc_errmsg());
     
    mLibvlcMedia = libvlc_media_new_location(mLibvlcInstance, mPath.c_str());
    if(mLibvlcMedia == NULL)
        Fatal("Unable to open input %s due to: %s", mPath.c_str(), libvlc_errmsg());
	
    mLibvlcMediaPlayer = libvlc_media_player_new_from_media(mLibvlcMedia);
    if(mLibvlcMediaPlayer == NULL)
        Fatal("Unable to create player for %s due to: %s", mPath.c_str(), libvlc_errmsg());

	libvlc_video_set_format(mLibvlcMediaPlayer, mTargetChroma.c_str(), width, height, width * mBpp);
    libvlc_video_set_callbacks(mLibvlcMediaPlayer, &LibvlcLockBuffer, &LibvlcUnlockBuffer, NULL, &mLibvlcData);

    mLibvlcData.bufferSize = width * height * mBpp;
    // Libvlc wants 32 byte alignment for images (should in theory do this for all image lines)
    mLibvlcData.buffer = (uint8_t*)zm_mallocaligned(32, mLibvlcData.bufferSize);
    mLibvlcData.prevBuffer = (uint8_t*)zm_mallocaligned(32, mLibvlcData.bufferSize);
    
    mLibvlcData.newImage.setValueImmediate(false);

    libvlc_media_player_play(mLibvlcMediaPlayer);
    
    return(0);
}

int LibvlcCamera::PreCapture()
{    
    return(0);
}

// Should not return -1 as cancels capture. Always wait for image if available.
int LibvlcCamera::Capture( Image &image )
{   
    while(!mLibvlcData.newImage.getValueImmediate())
        mLibvlcData.newImage.getUpdatedValue(1);

    mLibvlcData.mutex.lock();
    image.Assign(width, height, colours, subpixelorder, mLibvlcData.buffer, width * height * mBpp);
    mLibvlcData.newImage.setValueImmediate(false);
    mLibvlcData.mutex.unlock();
    
    return (0);
}

int LibvlcCamera::PostCapture()
{
    return(0);
}

#endif // HAVE_LIBVLC
