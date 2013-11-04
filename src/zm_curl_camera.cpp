//
// ZoneMinder cURL Camera Class Implementation, $Date: 2009-01-16 12:18:50 +0000 (Fri, 16 Jan 2009) $, $Revision: 2713 $
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#include "zm.h"

#if HAVE_LIBCURL

static FILE* curldebugfile = NULL; // Remove later

#include "zm_curl_camera.h"

cURLCamera::cURLCamera( int p_id, const std::string &p_path, const std::string &p_user, const std::string &p_pass, int p_width, int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    Camera( p_id, CURL_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    mPath( p_path ), mUser( p_user ), mPass ( p_pass )
{
	c = NULL;

	if ( capture )
	{
		Initialise();
		c = curl_easy_init();
		if(c == NULL) {
			Fatal("Failed getting easy handle from libcurl");
		}
	}
}

cURLCamera::~cURLCamera()
{
	if ( capture )
	{
		if(c != NULL) {
			curl_easy_cleanup(c);
			c = NULL;
		}
		Terminate();
	}
}

void cURLCamera::Initialise()
{
	ret = curl_global_init(CURL_GLOBAL_ALL);
	if(ret != CURLE_OK) {
		Fatal("libcurl initialization failed: ", curl_easy_strerror(ret));
	}

	Debug(2,"libcurl version: %s",curl_version());

	curldebugfile = fopen("/tmp/curl_debug.log","w"); // Remove later
}

void cURLCamera::Terminate()
{
	curl_global_cleanup();

	fclose(curldebugfile); // Remove later
}

int cURLCamera::PrimeCapture()
{
	Info( "Priming capture from %s", mPath.c_str() );

	/* Temporary */
	curl_easy_setopt(c, CURLOPT_VERBOSE, 1);
	curl_easy_setopt(c, CURLOPT_STDERR, curldebugfile);

	ret = curl_easy_setopt(c, CURLOPT_URL, mPath.c_str());
	if(ret != CURLE_OK)
		Fatal("Failed setting libcurl URL. error %d: ", curl_easy_strerror(ret));
	
	ret = curl_easy_setopt(c, CURLOPT_HEADERFUNCTION, &cURLCamera::header_callback);
	if(ret != CURLE_OK)
		Fatal("Failed setting libcurl header callback function. error %d: ", curl_easy_strerror(ret));

	ret = curl_easy_setopt(c, CURLOPT_WRITEFUNCTION, &cURLCamera::data_callback);
	if(ret != CURLE_OK)
		Fatal("Failed setting libcurl header callback function. error %d: ", curl_easy_strerror(ret));

	ret = curl_easy_setopt(c, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	if(ret != CURLE_OK)
		Warning("Failed setting libcurl acceptable http authenication methods. error %d: ", curl_easy_strerror(ret));

	return 0;
}

int cURLCamera::PreCapture()
{
    // Nothing to do here
    return( 0 );
}

int cURLCamera::Capture( Image &image )
{
	uint8_t* directbuffer;
	// bool frameComplete = false;
   
	/* Request a writeable buffer of the target image */
	directbuffer = image.WriteBuffer(width, height, colours, subpixelorder);
	if(directbuffer == NULL) {
		Error("Failed requesting writeable buffer for the captured image.");
		return (-1);
	}

	//success = curl_easy_perform(easyhandle);
    

    return (0);
}

int cURLCamera::PostCapture()
{
    // Nothing to do here
    return( 0 );
}

size_t cURLCamera::data_callback(void *buffer, size_t size, size_t nmemb, void *userdata) {
	return 0;
}

size_t cURLCamera::header_callback( void *buffer, size_t size, size_t nmemb, void *userdata) {
	return 0;
}

#endif // HAVE_LIBCURL
