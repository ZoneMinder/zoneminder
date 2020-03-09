//
// ZoneMinder Stream Class Implementation, $Date$, $Revision$
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

#include <sys/un.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <sys/file.h>

#include "zm.h"
#include "zm_mpeg.h"
#include "zm_monitor.h"

#include "zm_stream.h"

StreamBase::~StreamBase() {
#if HAVE_LIBAVCODEC
  if ( vid_stream ) {
    delete vid_stream;
    vid_stream = NULL;
  }
#endif
  closeComms();
}

bool StreamBase::loadMonitor(int monitor_id) {
  if ( !(monitor = Monitor::Load(monitor_id, false, Monitor::QUERY)) ) {
    Error("Unable to load monitor id %d for streaming", monitor_id);
    return false;
  }
  if ( monitor->GetFunction() == Monitor::NONE ) {
    Error("Monitor %d has function NONE. Will not be able to connect to it.", monitor_id);
    return false;
  }

  if ( !monitor->connect() ) {
    Error("Unable to connect to monitor id %d for streaming", monitor_id);
    return false;
  }

  return true;
}

bool StreamBase::checkInitialised() {
  if ( !monitor ) {
    Fatal("Cannot stream, not initialised");
    return false;
  }
  return true;
}

void StreamBase::updateFrameRate(double fps) {
  frame_mod = 1;
  if ( (fps < 0) || !fps || isinf(fps) ) {
    Debug(1, "Zero or negative fps %f in updateFrameRate. Setting frame_mod=1 and effective_fps=0.0", fps);
    effective_fps = 0.0;
    base_fps = 0.0;
    return;
  }
  base_fps = fps;
  effective_fps = (base_fps*abs(replay_rate))/ZM_RATE_BASE;
  frame_mod = 1;
  Debug(3, "FPS:%.2f, MaxFPS:%.2f, BaseFPS:%.2f, EffectiveFPS:%.2f, FrameMod:%d, replay_rate(%d)",
      fps, maxfps, base_fps, effective_fps, frame_mod, replay_rate);
  // Min frame repeat?
  // We want to keep the frame skip easy... problem is ... if effective = 31 and max = 30 then we end up with 15.5 fps.  
  while ( effective_fps > maxfps ) {
    effective_fps /= 2.0;
    frame_mod *= 2;
    Debug(3, "Changing fps to be < max %.2f EffectiveFPS:%.2f, FrameMod:%d",
        maxfps, effective_fps, frame_mod);
  }
} // void StreamBase::updateFrameRate(double fps)

bool StreamBase::checkCommandQueue() {
  if ( sd >= 0 ) {
    CmdMsg msg;
    memset(&msg, 0, sizeof(msg));
    int nbytes = recvfrom(sd, &msg, sizeof(msg), MSG_DONTWAIT, 0, 0);
    if ( nbytes < 0 ) {
      if ( errno != EAGAIN ) {
        Error("recvfrom(), errno = %d, error = %s", errno, strerror(errno));
        return false;
      }
    }
    //else if ( (nbytes != sizeof(msg)) )
    //{
      //Error( "Partial message received, expected %d bytes, got %d", sizeof(msg), nbytes );
    //}
    else {
      Debug(2, "Message length is (%d)", nbytes);
      processCommand(&msg);
      return true;
    }
  } else {
    Warning("No sd in checkCommandQueue, comms not open?");
  }
  return false;
}

Image *StreamBase::prepareImage(Image *image) {

  // Do not bother to scale zoomed in images, just crop them and let the browser scale
  // Works in FF2 but breaks FF3 which doesn't like image sizes changing in mid stream.
  bool optimisedScaling = false;

  bool image_copied = false;

  int mag = (scale * zoom) / ZM_SCALE_BASE;
  int act_mag = optimisedScaling?(mag > ZM_SCALE_BASE?ZM_SCALE_BASE:mag):mag;

  int last_mag = (last_scale * last_zoom) / ZM_SCALE_BASE;
  int last_act_mag = last_mag > ZM_SCALE_BASE?ZM_SCALE_BASE:last_mag;
  int base_image_width = image->Width(), base_image_height = image->Height();
  int virt_image_width = (base_image_width * mag) / ZM_SCALE_BASE, virt_image_height = (base_image_height * mag) / ZM_SCALE_BASE;
  int last_virt_image_width = (base_image_width * last_mag) / ZM_SCALE_BASE, last_virt_image_height = (base_image_height * last_mag) / ZM_SCALE_BASE;
  int act_image_width = (base_image_width * act_mag ) / ZM_SCALE_BASE, act_image_height = (base_image_height * act_mag ) / ZM_SCALE_BASE;
  int last_act_image_width = (base_image_width * last_act_mag ) / ZM_SCALE_BASE, last_act_image_height = (base_image_height * last_act_mag ) / ZM_SCALE_BASE;
  int disp_image_width = (image->Width() * scale) / ZM_SCALE_BASE, disp_image_height = (image->Height() * scale) / ZM_SCALE_BASE;
  int last_disp_image_width = (image->Width() * last_scale) / ZM_SCALE_BASE, last_disp_image_height = (image->Height() * last_scale) / ZM_SCALE_BASE;
  int send_image_width = (disp_image_width * act_mag ) / mag, send_image_height = (disp_image_height * act_mag ) / mag;
  int last_send_image_width = (last_disp_image_width * last_act_mag ) / last_mag, last_send_image_height = (last_disp_image_height * last_act_mag ) / last_mag;

  Debug(3,
      "Scaling by %d, zooming by %d = magnifying by %d(%d)\n"
      "Last scaling by %d, zooming by %d = magnifying by %d(%d)\n"
      "Base image width = %d, height = %d\n"
      "Virtual image width = %d, height = %d\n"
      "Last virtual image width = %d, height = %d\n"
      "Actual image width = %d, height = %d\n"
      "Last actual image width = %d, height = %d\n"
      "Display image width = %d, height = %d\n"
      "Last display image width = %d, height = %d\n"
      "Send image width = %d, height = %d\n"
      "Last send image width = %d, height = %d\n",
      scale, zoom, mag, act_mag,
      last_scale, last_zoom, last_mag, last_act_mag,
      base_image_width, base_image_height,
      virt_image_width, virt_image_height,
      last_virt_image_width, last_virt_image_height,
      act_image_width, act_image_height,
      last_act_image_width, last_act_image_height,
      disp_image_width, disp_image_height,
      last_disp_image_width, last_disp_image_height,
      send_image_width, send_image_height,
      last_send_image_width, last_send_image_height
      );

  if ( ( mag != ZM_SCALE_BASE ) && (act_mag != ZM_SCALE_BASE) ) {
    Debug(3, "Magnifying by %d", mag);
    static Image copy_image;
    copy_image.Assign(*image);
    image = &copy_image;
    image_copied = true;
    image->Scale(mag);
  }

  Debug(3, "Real image width = %d, height = %d", image->Width(), image->Height());

  if ( disp_image_width < virt_image_width || disp_image_height < virt_image_height ) {
    static Box last_crop;

    if ( mag != last_mag || x != last_x || y != last_y ) {
      Debug(3, "Got click at %d,%d x %d", x, y, mag);

      if ( !(last_disp_image_width < last_virt_image_width || last_disp_image_height < last_virt_image_height) )
        last_crop = Box();

      // Recalculate crop parameters, as %ges
      int click_x = (last_crop.LoX() * 100 ) / last_act_image_width; // Initial crop offset from last image
      click_x += ( x * 100 ) / last_virt_image_width;
      int click_y = (last_crop.LoY() * 100 ) / last_act_image_height; // Initial crop offset from last image
      click_y += ( y * 100 ) / last_virt_image_height;
      Debug(3, "Got adjusted click at %d%%,%d%%", click_x, click_y);

      // Convert the click locations to the current image pixels
      click_x = ( click_x * act_image_width ) / 100;
      click_y = ( click_y * act_image_height ) / 100;
      Debug(3, "Got readjusted click at %d,%d", click_x, click_y);

      int lo_x = click_x - (send_image_width/2);
      if ( lo_x < 0 )
        lo_x = 0;
      int hi_x = lo_x + (send_image_width-1);
      if ( hi_x >= act_image_width ) {
        hi_x = act_image_width - 1;
        lo_x = hi_x - (send_image_width - 1);
      }

      int lo_y = click_y - (send_image_height/2);
      if ( lo_y < 0 ) lo_y = 0;
      int hi_y = lo_y + (send_image_height-1);
      if ( hi_y >= act_image_height ) {
        hi_y = act_image_height - 1;
        lo_y = hi_y - (send_image_height - 1);
      }
      last_crop = Box( lo_x, lo_y, hi_x, hi_y );
    }  // end if ( mag != last_mag || x != last_x || y != last_y )

    Debug(3, "Cropping to %d,%d -> %d,%d", last_crop.LoX(), last_crop.LoY(), last_crop.HiX(), last_crop.HiY());
    if ( !image_copied ) {
      static Image copy_image;
      copy_image.Assign(*image);
      image = &copy_image;
      image_copied = true;
    }
    image->Crop(last_crop);
  }  // end if difference in image vs displayed dimensions

  last_scale = scale;
  last_zoom = zoom;
  last_x = x;
  last_y = y;

  return image;
}  // end Image *StreamBase::prepareImage(Image *image)

bool StreamBase::sendTextFrame(const char *frame_text) {
  Debug(2, "Sending %dx%d * %d text frame '%s'",
      monitor->Width(), monitor->Height(), scale, frame_text);

  Image image(monitor->Width(), monitor->Height(), monitor->Colours(), monitor->SubpixelOrder());
  image.Annotate(frame_text, image.centreCoord(frame_text));

  if ( scale != 100 ) {
    image.Scale(scale);
  }
#if HAVE_LIBAVCODEC
  if ( type == STREAM_MPEG ) {
    if ( !vid_stream ) {
      vid_stream = new VideoStream("pipe:", format, bitrate, effective_fps, image.Colours(), image.SubpixelOrder(), image.Width(), image.Height());
      fprintf(stdout, "Content-type: %s\r\n\r\n", vid_stream->MimeType());
      vid_stream->OpenStream();
    }
    /* double pts = */ vid_stream->EncodeFrame( image.Buffer(), image.Size() );
  } else
#endif // HAVE_LIBAVCODEC
  {
    static unsigned char buffer[ZM_MAX_IMAGE_SIZE];
    int n_bytes = 0;

    image.EncodeJpeg(buffer, &n_bytes);

    fputs("--ZoneMinderFrame\r\nContent-Type: image/jpeg\r\n", stdout);
    fprintf(stdout, "Content-Length: %d\r\n\r\n", n_bytes);
    if ( fwrite(buffer, n_bytes, 1, stdout) != 1 ) {
      Error("Unable to send stream text frame: %s", strerror(errno));
      return false;
    }
    fputs("\r\n\r\n",stdout);
    fflush(stdout);
  }
  last_frame_sent = TV_2_FLOAT(now);
  return true;
}

void StreamBase::openComms() {
  if ( connkey > 0 ) {

    // Have to mkdir because systemd is now chrooting and the dir may not exist
    if ( mkdir(staticConfig.PATH_SOCKS.c_str(), 0755) ) {
      if ( errno != EEXIST ) {
        Error("Can't mkdir ZM_PATH_SOCKS %s: %s.", staticConfig.PATH_SOCKS.c_str(), strerror(errno));
      }
    }

    unsigned int length = snprintf(
        sock_path_lock,
        sizeof(sock_path_lock),
        "%s/zms-%06d.lock",
        staticConfig.PATH_SOCKS.c_str(),
        connkey
        );
    if ( length >= sizeof(sock_path_lock) ) {
      Warning("Socket lock path was truncated.");
    }
    Debug(1, "Trying to open the lock on %s", sock_path_lock);

    // Under systemd, we get chrooted to something like /tmp/systemd-apache-blh/ so the dir may not exist.
    if ( mkdir(staticConfig.PATH_SOCKS.c_str(), 0755) ) {
      if ( errno != EEXIST ) {
        Error("Can't mkdir %s: %s", staticConfig.PATH_SOCKS.c_str(), strerror(errno));
        return;
      } else {
        Debug(3, "SOCKS dir %s already exists", staticConfig.PATH_SOCKS.c_str() );
      }
    } else {
      Debug(3, "Success making SOCKS dir %s", staticConfig.PATH_SOCKS.c_str() );
    }

    lock_fd = open(sock_path_lock, O_CREAT|O_WRONLY, S_IRUSR | S_IWUSR);
    if ( lock_fd <= 0 ) {
      Error("Unable to open sock lock file %s: %s", sock_path_lock, strerror(errno));
      lock_fd = 0;
    } else if ( flock(lock_fd, LOCK_EX) != 0 ) {
      Error("Unable to lock sock lock file %s: %s", sock_path_lock, strerror(errno));
      close(lock_fd);
      lock_fd = 0;
    } else {
      Debug(1, "We have obtained a lock on %s fd: %d", sock_path_lock, lock_fd);
    }

    sd = socket(AF_UNIX, SOCK_DGRAM, 0);
    if ( sd < 0 ) {
      Fatal("Can't create socket: %s", strerror(errno));
    } else {
      Debug(3, "Have socket %d", sd);
    }

    length = snprintf(
        loc_sock_path,
        sizeof(loc_sock_path),
        "%s/zms-%06ds.sock",
        staticConfig.PATH_SOCKS.c_str(),
        connkey
        );
    if ( length >= sizeof(loc_sock_path) ) {
      Warning("Socket path was truncated.");
      length = sizeof(loc_sock_path)-1;
    }
    // Unlink before bind, in case it already exists
    unlink(loc_sock_path);
    if ( sizeof(loc_addr.sun_path) < length ) {
      Error("Not enough space %d in loc_addr.sun_path for socket file %s", sizeof(loc_addr.sun_path), loc_sock_path);
    }

    strncpy(loc_addr.sun_path, loc_sock_path, sizeof(loc_addr.sun_path));
    loc_addr.sun_family = AF_UNIX;
    Debug(3, "Binding to %s", loc_sock_path);
    if ( ::bind(sd, (struct sockaddr *)&loc_addr, strlen(loc_addr.sun_path)+sizeof(loc_addr.sun_family)+1) < 0 ) {
      Fatal("Can't bind: %s", strerror(errno));
    }

    snprintf(rem_sock_path, sizeof(rem_sock_path), "%s/zms-%06dw.sock", staticConfig.PATH_SOCKS.c_str(), connkey);
    strncpy(rem_addr.sun_path, rem_sock_path, sizeof(rem_addr.sun_path));
    rem_addr.sun_family = AF_UNIX;

    gettimeofday(&last_comm_update, NULL);
  } // end if connKey > 0
  Debug(3, "comms open at %s", loc_sock_path);
} // end void StreamBase::openComms()

void StreamBase::closeComms() {
  if ( connkey > 0 ) {
    if ( sd >= 0 ) {
      close(sd);
      sd = -1;
    }
    if ( loc_sock_path[0] ) {
      unlink(loc_sock_path);
    }
    if ( lock_fd > 0 ) {
      close(lock_fd); //close it rather than unlock it incase it got deleted.
      // You cannot unlink the lockfile.  You have to leave a mess around.  SUCKS
      //unlink(sock_path_lock);
    }
  }
} // end void StreamBase::closeComms
