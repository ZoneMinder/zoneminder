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

#include "zm_stream.h"

#include "zm_box.h"
#include "zm_monitor.h"
#include <cmath>
#include <sys/file.h>
#include <sys/socket.h>
#include <sys/stat.h>
#include <unistd.h>

constexpr Seconds StreamBase::MAX_STREAM_DELAY;
constexpr Milliseconds StreamBase::MAX_SLEEP;

StreamBase::~StreamBase() {
  delete vid_stream;
  delete temp_img_buffer;
  closeComms();
}

bool StreamBase::loadMonitor(int p_monitor_id) {
  monitor_id = p_monitor_id;

  if ( !(monitor or (monitor = Monitor::Load(monitor_id, false, Monitor::QUERY))) ) {
    Error("Unable to load monitor id %d for streaming", monitor_id);
    return false;
  }

  if (monitor->Capturing() == Monitor::CAPTURING_NONE) {
    Info("Monitor %d has capturing == NONE. Will not be able to connect to it.", monitor_id);
    return false;
  }

  if (monitor->isConnected()) {
    monitor->disconnect();
  }

  if (!monitor->connect()) {
    Error("Unable to connect to monitor id %d for streaming", monitor_id);
    monitor->disconnect();
    return false;
  }

  return true;
}

bool StreamBase::checkInitialised() {
  if (!monitor) {
    Error("Cannot stream, not initialised");
    return false;
  }
  if (monitor->Capturing() == Monitor::CAPTURING_NONE) {
    Info("Monitor %d has capturing == NONE. Will not be able to connect to it.", monitor_id);
    return false;
  }
  if (!monitor->ShmValid()) {
    Error("Monitor shm is not connected");
    return false;
  }
  if ((monitor->GetType() == Monitor::FFMPEG) and (monitor->Decoding() == Monitor::DECODING_NONE) ) {
    Debug(1, "Monitor is not decoding.");
    return false;
  }
  return true;
}

void StreamBase::updateFrameRate(double fps) {
  frame_mod = 1;
  if ( (fps < 0) || !fps || std::isinf(fps) ) {
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
  if (maxfps > 0.0) {
    // Min frame repeat?
    // We want to keep the frame skip easy... problem is ... if effective = 31 and max = 30 then we end up with 15.5 fps.  
    while (effective_fps > maxfps) {
      effective_fps /= 2.0;
      frame_mod *= 2;
      Debug(3, "Changing fps to be < max %.2f EffectiveFPS:%.2f, FrameMod:%d",
          maxfps, effective_fps, frame_mod);
    }
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
  } else if ( connkey ) {
    Warning("No sd in checkCommandQueue, comms not open for connkey %06d?", connkey);
  } else {
    // Perfectly valid if only getting a snapshot
    Debug(1, "No sd in checkCommandQueue, comms not open.");
  }
  return false;
}  // end bool StreamBase::checkCommandQueue()

Image *StreamBase::prepareImage(Image *image) {
  /* zooming should happen before scaling to preserve quality
   * scale is relative to base dimensions, and represents the rough ration between desired view size and base dimensions
   */

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
      "Send image width = %d, height = %d\n",
      scale, zoom, mag, act_mag,
      last_scale, last_zoom, last_mag, last_act_mag,
      base_image_width, base_image_height,
      virt_image_width, virt_image_height,
      last_virt_image_width, last_virt_image_height,
      act_image_width, act_image_height,
      last_act_image_width, last_act_image_height,
      disp_image_width, disp_image_height,
      last_disp_image_width, last_disp_image_height,
      send_image_width, send_image_height
      );

  Debug(3, "Real image width = %d, height = %d", image->Width(), image->Height());

  if ( disp_image_width < virt_image_width || disp_image_height < virt_image_height ) {

    /* x and y are scaled by web UI to base dimensions units. 
     * So if we have scaled the image above, then we need to adjust the click to the new dimensions */

    if (zoom) {
      /* When zooming, we blow up the image by the amount 150 for first zoom, right? 150%, then cut out a base sized chunk
       * However if we have zoomed before, then we are zooming into the previous cutout
       * The box stored in last_crop should be in base_image units, So we need to turn x,y into percentages, then apply to last_crop
       *
       */
      if (!last_crop.Hi().x_ or last_crop.Hi().y_) last_crop = Box({0, 0}, {base_image_width, base_image_height});

      int x_percent = x * ZM_SCALE_BASE / base_image_width;
      int y_percent = y * ZM_SCALE_BASE / base_image_height;
      Debug(2, "click percent %dx%d => %dx%d", x, y, x_percent, y_percent);

      int crop_x = last_crop.Lo().x_ + (x_percent * last_crop.Width() / ZM_SCALE_BASE);
      int crop_y = last_crop.Lo().y_ + (y_percent * last_crop.Height() / ZM_SCALE_BASE);
      Debug(2, "crop click %dx%d => %dx%d out of %dx%d", x, y, crop_x, crop_y, last_crop.Width(), last_crop.Height());

      int zoom_image_width = base_image_width * zoom / ZM_SCALE_BASE,
          zoom_image_height = base_image_height * zoom / ZM_SCALE_BASE,
          click_x = crop_x * zoom / ZM_SCALE_BASE,
          click_y = crop_y * zoom / ZM_SCALE_BASE;
      Debug(2, "adjusted click %dx%d => %dx%d out of %dx%d", x, y, click_x, click_y, zoom_image_width, zoom_image_height);

      // These can go out of image. Resulting size will be less than base image. That's ok.
      int lo_x = click_x - (base_image_width/2);
      int hi_x = lo_x + base_image_width;
      int lo_y = click_y - (base_image_height/2);
      int hi_y = lo_y + base_image_height;

      int amount_to_shrink_y = 0;
      if (lo_x < 0) {
        amount_to_shrink_y = ((-1 * lo_x) / base_image_width) * base_image_height;
        lo_x = 0;
      } else if (hi_x >= zoom_image_width) {
        amount_to_shrink_y = ((hi_x - zoom_image_width) / base_image_width) * base_image_height;
        hi_x = zoom_image_width - 1;
      }
      Debug(1, "Shrinking y by %d from %d->%d to %d->%d", amount_to_shrink_y, lo_y, hi_y, lo_y+amount_to_shrink_y/2, hi_y-amount_to_shrink_y/2);
      if (amount_to_shrink_y) {
        lo_y += amount_to_shrink_y/2;
        hi_y -= amount_to_shrink_y/2;
      }

      int amount_to_shrink_x = 0;
      if (lo_y < 0) {
        amount_to_shrink_x = ((-1 * lo_y) / base_image_height) * base_image_width;
        lo_y = 0;
      } else if (hi_y >= zoom_image_height) {
        amount_to_shrink_x = ((hi_y - zoom_image_height) / base_image_height) * base_image_width;
        hi_y = zoom_image_height - 1;
      }
      Debug(1, "Shrinking y by %d from %d->%d to %d->%d", amount_to_shrink_x, lo_x, hi_x, lo_x+amount_to_shrink_x/2, hi_x-amount_to_shrink_x/2);
      if (amount_to_shrink_x) {
        lo_x += amount_to_shrink_x/2;
        hi_x -= amount_to_shrink_x/2;
      }

      Debug(3, "Cropping to %d,%d -> %d,%d %dx%din blown up image", lo_x, lo_y, hi_x, hi_y, hi_x-lo_x, hi_y-lo_y);
      // Scaled back to base_image dimensions
      last_crop = Box({lo_x*ZM_SCALE_BASE/zoom, lo_y*ZM_SCALE_BASE/zoom}, {hi_x*ZM_SCALE_BASE/zoom, hi_y*ZM_SCALE_BASE/zoom});

      Debug(3, "Cropping to %d,%d -> %d,%d", last_crop.Lo().x_, last_crop.Lo().y_, last_crop.Hi().x_, last_crop.Hi().y_);
      if ( !image_copied ) {
        static Image copy_image;
        copy_image.Assign(*image);
        image = &copy_image;
        image_copied = true;
      }
      image->Crop(last_crop);
      image->Scale(disp_image_width, disp_image_height);
    } else
    //}  // end if ( mag != last_mag || x != last_x || y != last_y )

    if ( ( mag != ZM_SCALE_BASE ) && (act_mag != ZM_SCALE_BASE) ) {
      Debug(3, "Magnifying by %d from %dx%d", mag, image->Width(), image->Height());
      static Image copy_image;
      copy_image.Assign(*image);
      image = &copy_image;
      image_copied = true;
      image->Scale(mag);
      Debug(3, "Magnifying by %d to %dx%d", mag, image->Width(), image->Height());
    }
  }  // end if difference in image vs displayed dimensions
      Debug(3, "Sending %dx%d", image->Width(), image->Height());

  last_scale = scale;
  last_zoom = zoom;
  last_x = x;
  last_y = y;

  return image;
}  // end Image *StreamBase::prepareImage(Image *image)

bool StreamBase::sendTextFrame(const char *frame_text) {
  int width = 640;
  int height = 480;
  int colours = ZM_COLOUR_RGB32;
  int subpixelorder = ZM_SUBPIX_ORDER_RGBA;
  int labelsize = 2;

  if (monitor) {
    width = monitor->Width();
    height = monitor->Height();
    colours = monitor->Colours();
    subpixelorder = monitor->SubpixelOrder();
    labelsize = monitor->LabelSize();
  }
  Debug(2, "Sending %dx%dx%dx%d * %d scale text frame '%s'",
      width, height, colours, subpixelorder, scale, frame_text);

  Image image(width, height, colours, subpixelorder);
  image.Clear();
  image.Annotate(frame_text, image.centreCoord(frame_text, labelsize), labelsize);

  if (scale != 100) {
    image.Scale(scale);
    Debug(2, "Scaled to %dx%d", image.Width(), image.Height());
  }
  if (type == STREAM_MPEG) {
    if (!vid_stream) {
      vid_stream = new VideoStream("pipe:", format, bitrate, effective_fps, image.Colours(), image.SubpixelOrder(), image.Width(), image.Height());
      fprintf(stdout, "Content-Type: %s\r\n\r\n", vid_stream->MimeType());
      vid_stream->OpenStream();
    }
    /* double pts = */ vid_stream->EncodeFrame(image.Buffer(), image.Size());
  } else {
    static unsigned char buffer[ZM_MAX_IMAGE_SIZE];
    int n_bytes = 0;

    image.EncodeJpeg(buffer, &n_bytes);
    Debug(4, "Encoded to %d bytes", n_bytes);

    if (0 > fputs("--" BOUNDARY "\r\nContent-Type: image/jpeg\r\n", stdout)) {
      Debug(1, "Error sending  --" BOUNDARY "\r\nContent-Type: image/jpeg\r\n");
      return false;
    }
    if (0 > fprintf(stdout, "Content-Length: %d\r\n\r\n", n_bytes)) {
      Debug(1, "Error sending Content-Length: %d\r\n\r\n", n_bytes);
      return false;
    }
    int rc = fwrite(buffer, n_bytes, 1, stdout);
    if (rc != 1) {
      Error("Unable to send stream text frame: %d %s", rc, strerror(errno));
      return false;
    }
    fputs("\r\n\r\n", stdout);
    fflush(stdout);
  }
  last_frame_sent = now;
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
      Error("Not enough space %zu in loc_addr.sun_path for socket file %s", sizeof(loc_addr.sun_path), loc_sock_path);
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

    last_comm_update = std::chrono::steady_clock::now();
    Debug(3, "comms open at %s", loc_sock_path);
  } // end if connKey > 0
} // end void StreamBase::openComms()

void StreamBase::closeComms() {
  if ( connkey > 0 ) {
    if ( sd >= 0 ) {
      close(sd);
      sd = -1;
    }
    // Can't delete any files because another zms might have come along and opened them and is waiting on the lock.
    if ( lock_fd > 0 ) {
      close(lock_fd); //close it rather than unlock it incase it got deleted.
    }
  }
} // end void StreamBase::closeComms
