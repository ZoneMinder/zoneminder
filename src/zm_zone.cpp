//
// ZoneMinder Zone Class Implementation, $Date$, $Revision$
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

#define __STDC_FORMAT_MACROS 1
#include <cinttypes>
#include "zm.h"
#include "zm_db.h"
#include "zm_zone.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_fifo.h"


void Zone::Setup( 
  Monitor *p_monitor,
  int p_id,
  const char *p_label,
  ZoneType p_type,
  const Polygon &p_polygon,
  const Rgb p_alarm_rgb,
  CheckMethod p_check_method,
  int p_min_pixel_threshold,
  int p_max_pixel_threshold,
  int p_min_alarm_pixels,
  int p_max_alarm_pixels,
  const Coord &p_filter_box,
  int p_min_filter_pixels,
  int p_max_filter_pixels,
  int p_min_blob_pixels,
  int p_max_blob_pixels,
  int p_min_blobs,
  int p_max_blobs,
  int p_overload_frames,
  int p_extend_alarm_frames
) {
  monitor = p_monitor;

  id = p_id;
  label = new char[strlen(p_label)+1];
  strcpy(label, p_label);
  type = p_type;
  polygon = p_polygon;
  alarm_rgb = p_alarm_rgb;
  check_method = p_check_method;
  min_pixel_threshold = p_min_pixel_threshold;
  max_pixel_threshold = p_max_pixel_threshold;
  min_alarm_pixels = p_min_alarm_pixels;
  max_alarm_pixels = p_max_alarm_pixels;
  filter_box = p_filter_box;
  min_filter_pixels = p_min_filter_pixels;
  max_filter_pixels = p_max_filter_pixels;
  min_blob_pixels = p_min_blob_pixels;
  max_blob_pixels = p_max_blob_pixels;
  min_blobs = p_min_blobs;
  max_blobs = p_max_blobs;
  overload_frames = p_overload_frames;
  extend_alarm_frames = p_extend_alarm_frames;

  //Debug( 1, "Initialised zone %d/%s - %d - %dx%d - Rgb:%06x, CM:%d, MnAT:%d, MxAT:%d, MnAP:%d, MxAP:%d, FB:%dx%d, MnFP:%d, MxFP:%d, MnBS:%d, MxBS:%d, MnB:%d, MxB:%d, OF: %d, AF: %d", id, label, type, polygon.Width(), polygon.Height(), alarm_rgb, check_method, min_pixel_threshold, max_pixel_threshold, min_alarm_pixels, max_alarm_pixels, filter_box.X(), filter_box.Y(), min_filter_pixels, max_filter_pixels, min_blob_pixels, max_blob_pixels, min_blobs, max_blobs, overload_frames, extend_alarm_frames );

  alarmed = false;
  was_alarmed = false;
  pixel_diff = 0;
  alarm_pixels = 0;
  alarm_filter_pixels = 0;
  alarm_blob_pixels = 0;
  alarm_blobs = 0;
  min_blob_size = 0;
  max_blob_size = 0;
  image = 0;
  score = 0;

  overload_count = 0;
  extend_alarm_count = 0;

  pg_image = new Image(monitor->Width(), monitor->Height(), 1, ZM_SUBPIX_ORDER_NONE);
  pg_image->Clear();
  pg_image->Fill(0xff, polygon);
  pg_image->Outline(0xff, polygon);

  ranges = new Range[monitor->Height()];
  for ( unsigned int y = 0; y < monitor->Height(); y++ ) {
    ranges[y].lo_x = -1;
    ranges[y].hi_x = 0;
    ranges[y].off_x = 0;
    const uint8_t *ppoly = pg_image->Buffer( 0, y );
    for ( unsigned int x = 0; x < monitor->Width(); x++, ppoly++ ) {
      if ( *ppoly ) {
        if ( ranges[y].lo_x == -1 ) {
          ranges[y].lo_x = x;
        }
        if ( (unsigned int)ranges[y].hi_x < x ) {
          ranges[y].hi_x = x;
        }
      }
    }
  }

  if ( config.record_diag_images ) {
    snprintf(diag_path, sizeof(diag_path),
        config.record_diag_images_fifo ? "%s/diagpipe-%d-poly.jpg" : "%s/diag-%d-poly.jpg",
        monitor->getStorage()->Path(), id);
    if ( config.record_diag_images_fifo )
      FifoStream::fifo_create_if_missing(diag_path);
    pg_image->WriteJpeg(diag_path, config.record_diag_images_fifo);
  } else {
    diag_path[0] = 0;
  }
} // end Zone::Setup

Zone::~Zone() {
  delete[] label;
  delete image;
  delete pg_image;
  delete[] ranges;
}

void Zone::RecordStats( const Event *event ) {
  static char sql[ZM_SQL_MED_BUFSIZ];
  db_mutex.lock();
  snprintf(sql, sizeof(sql),
      "INSERT INTO Stats SET MonitorId=%d, ZoneId=%d, EventId=%" PRIu64 ", FrameId=%d, PixelDiff=%d, AlarmPixels=%d, FilterPixels=%d, BlobPixels=%d, Blobs=%d, MinBlobSize=%d, MaxBlobSize=%d, MinX=%d, MinY=%d, MaxX=%d, MaxY=%d, Score=%d",
      monitor->Id(), id, event->Id(), event->Frames()+1, pixel_diff, alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, min_blob_size, max_blob_size, alarm_box.LoX(), alarm_box.LoY(), alarm_box.HiX(), alarm_box.HiY(), score
      );
  if ( mysql_query(&dbconn, sql) ) {
    Error("Can't insert event stats: %s", mysql_error(&dbconn));
  }
  db_mutex.unlock();
} // end void Zone::RecordStats( const Event *event )

bool Zone::CheckOverloadCount() {
  if ( overload_count ) {
    Debug(4, "In overload mode, %d frames of %d remaining", overload_count, overload_frames);
    overload_count--;
    return false;
  }
  return true;
} // end bool Zone::CheckOverloadCount()

void Zone::SetScore(unsigned int nScore) {
  score = nScore;
} // end void Zone::SetScore(unsigned int nScore)

void Zone::SetAlarmImage(const Image* srcImage) {
  delete image;
  image = new Image(*srcImage);
} // end void Zone::SetAlarmImage( const Image* srcImage )

int Zone::GetOverloadCount() {
  return overload_count;
} // end int Zone::GetOverloadCount()

void Zone::SetOverloadCount(int nOverCount) {
  overload_count = nOverCount;
} // end void Zone::SetOverloadCount(int nOverCount )

int Zone::GetOverloadFrames() {
  return overload_frames;
} // end int Zone::GetOverloadFrames

int Zone::GetExtendAlarmCount() {
  return extend_alarm_count;
} // end int Zone::GetExtendAlarmCount()

void Zone::SetExtendAlarmCount(int nExtendAlarmCount) {
  extend_alarm_count = nExtendAlarmCount;
} // end void Zone::SetExtendAlarmCount( int nExtendAlarmCount )

int Zone::GetExtendAlarmFrames() {
  return extend_alarm_frames;
} // end int Zone::GetExtendAlarmFrames()

bool Zone::CheckExtendAlarmCount() {
  Info("ExtendAlarm count: %d, ExtendAlarm frames: %d", extend_alarm_count, extend_alarm_frames);
  if ( extend_alarm_count ) {
    Debug(3, "In extend mode, %d frames of %d remaining", extend_alarm_count, extend_alarm_frames);
    extend_alarm_count--;
    return true;
  }
  return false;
} // end bool Zone::CheckExtendAlarmCount

bool Zone::CheckAlarms(const Image *delta_image) {
  ResetStats();

  if ( overload_count ) {
    Info("In overload mode, %d frames of %d remaining", overload_count, overload_frames);
    overload_count--;
    return false;
  }

  delete image;
  // Get the difference image
  Image *diff_image = image = new Image(*delta_image);
  int diff_width = diff_image->Width();
  uint8_t* diff_buff = (uint8_t*)diff_image->Buffer();
  uint8_t* pdiff;

  unsigned int pixel_diff_count = 0;

  int alarm_lo_x = 0;
  int alarm_hi_x = 0;
  int alarm_lo_y = 0;
  int alarm_hi_y = 0;

  int alarm_mid_x = -1;
  int alarm_mid_y = -1;

  unsigned int lo_y = polygon.LoY();
  unsigned int lo_x = polygon.LoX();
  unsigned int hi_x = polygon.HiX();
  unsigned int hi_y = polygon.HiY();

  Debug(4, "Checking alarms for zone %d/%s in lines %d -> %d", id, label, lo_y, hi_y);

  /* if(config.cpu_extensions && sseversion >= 20) {
     sse2_alarmedpixels(diff_image, pg_image, &alarm_pixels, &pixel_diff_count);
     } else {
     std_alarmedpixels(diff_image, pg_image, &alarm_pixels, &pixel_diff_count);
     } */
  std_alarmedpixels(diff_image, pg_image, &alarm_pixels, &pixel_diff_count);

  if ( config.record_diag_images )
    diff_image->WriteJpeg(diag_path, config.record_diag_images_fifo);

  if ( pixel_diff_count && alarm_pixels )
    pixel_diff = pixel_diff_count/alarm_pixels;

  Debug(5, "Got %d alarmed pixels, need %d -> %d, avg pixel diff %d",
      alarm_pixels, min_alarm_pixels, max_alarm_pixels, pixel_diff);

  if ( config.record_diag_images_fifo ) {
    FifoDebug(5, "{\"zone\":%d,\"type\":\"ALRM\",\"pixels\":%d,\"avg_diff\":%d}",
        id,alarm_pixels, pixel_diff);
  }

  if ( alarm_pixels ) {
    if ( min_alarm_pixels && (alarm_pixels < (unsigned int)min_alarm_pixels) ) {
      /* Not enough pixels alarmed */
      return false;
    } else if ( max_alarm_pixels && (alarm_pixels > (unsigned int)max_alarm_pixels) ) {
      /* Too many pixels alarmed */
      overload_count = overload_frames;
      return false;
    }
  } else {
    /* No alarmed pixels */
    return false;
  }

  if ( max_alarm_pixels != 0 )
    score = (100*alarm_pixels)/max_alarm_pixels;
  else
    score = (100*alarm_pixels)/polygon.Area();
  
  if ( score < 1 )
    score = 1; /* Fix for score of 0 when frame meets thresholds but alarmed area is not big enough */
  Debug(5, "Current score is %d", score);

  if ( check_method >= FILTERED_PIXELS ) {
    int bx = filter_box.X();
    int by = filter_box.Y();
    int bx1 = bx-1;
    int by1 = by-1;

    Debug(5, "Checking for filtered pixels");
    if ( bx > 1 || by > 1 ) {
      // Now remove any pixels smaller than our filter size
      unsigned char *cpdiff;
      int ldx, hdx, ldy, hdy;
      bool block;
      for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
        int lo_x = ranges[y].lo_x;
        int hi_x = ranges[y].hi_x;

        pdiff = (uint8_t*)diff_image->Buffer(lo_x, y);

        for ( int x = lo_x; x <= hi_x; x++, pdiff++ ) {
          if ( *pdiff == WHITE ) {
            // Check participation in an X block
            ldx = (x>=(lo_x+bx1))?-bx1:lo_x-x;
            hdx = (x<=(hi_x-bx1))?0:((hi_x-x)-bx1);
            ldy = (y>=(lo_y+by1))?-by1:lo_y-y;
            hdy = (y<=(hi_y-by1))?0:((hi_y-y)-by1);
            block = false;
            for ( int dy = ldy; !block && dy <= hdy; dy++ ) {
              for ( int dx = ldx; !block && dx <= hdx; dx++ ) {
                block = true;
                for ( int dy2 = 0; block && dy2 < by; dy2++ ) {
                  for ( int dx2 = 0; block && dx2 < bx; dx2++ ) {
                    cpdiff = diff_buff + (((y+dy+dy2)*diff_width) + (x+dx+dx2));
                    if ( !*cpdiff ) {
                      block = false;
                    }
                  }
                }
              }
            }
            if ( !block ) {
              *pdiff = BLACK;
              continue;
            }
            alarm_filter_pixels++;
          } // end if white
        } // end for x
      } // end foreach y line
    } else {
      alarm_filter_pixels = alarm_pixels;
    }

    if ( config.record_diag_images )
      diff_image->WriteJpeg(diag_path, config.record_diag_images_fifo);

    Debug(5, "Got %d filtered pixels, need %d -> %d",
        alarm_filter_pixels, min_filter_pixels, max_filter_pixels);

    if ( config.record_diag_images_fifo )
      FifoDebug(5, "{\"zone\":%d,\"type\":\"FILT\",\"pixels\":%d}", id, alarm_filter_pixels);

    if ( alarm_filter_pixels ) {
      if ( min_filter_pixels && (alarm_filter_pixels < min_filter_pixels) ) {
        /* Not enough pixels alarmed */
        return false;
      } else if ( max_filter_pixels && (alarm_filter_pixels > max_filter_pixels) ) {
        /* Too many pixels alarmed */
        overload_count = overload_frames;
        return false;
      }
    } else {
      /* No filtered pixels */
      return false;
    }

    if ( max_filter_pixels != 0 )
       score = (100*alarm_filter_pixels)/max_filter_pixels;
     else
       score = (100*alarm_filter_pixels)/polygon.Area();

    if ( score < 1 )
      score = 1; /* Fix for score of 0 when frame meets thresholds but alarmed area is not big enough */
    Debug(5, "Current score is %d", score);

    if ( check_method >= BLOBS ) {
      Debug(5, "Checking for blob pixels");
      typedef struct { unsigned char tag; int count; int lo_x; int hi_x; int lo_y; int hi_y; } BlobStats;
      BlobStats blob_stats[256];
      memset(blob_stats, 0, sizeof(BlobStats)*256);
      uint8_t *spdiff;
      uint8_t last_x, last_y;
      BlobStats *bsx, *bsy;
      BlobStats *bsm, *bss;
      for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
        int lo_x = ranges[y].lo_x;
        int hi_x = ranges[y].hi_x;

        pdiff = (uint8_t*)diff_image->Buffer( lo_x, y );
        for ( int x = lo_x; x <= hi_x; x++, pdiff++ ) {
          if ( *pdiff == WHITE ) {
            Debug(9, "Got white pixel at %d,%d (%p)", x, y, pdiff);
            //last_x = (x>lo_x)?*(pdiff-1):0;
            //last_y = (y>lo_y&&x>=last_lo_x&&x<=last_hi_x)?*(pdiff-diff_width):0;

            last_x = 0;
            if ( x > 0 ) {
              if ( (x-1) >= lo_x ) {
                last_x = *(pdiff-1);
              }
            }

            last_y = 0;
            if (y > 0 ) {
              if ( (y-1) >= lo_y && ranges[(y-1)].lo_x <= x && ranges[(y-1)].hi_x >= x ) {
                last_y = *(pdiff-diff_width);
              }
            }

            if ( last_x ) {
              Debug(9, "Left neighbour is %d", last_x);
              bsx = &blob_stats[last_x];
              if ( last_y ) {
                Debug(9, "Top neighbour is %d", last_y);
                bsy = &blob_stats[last_y];
                if ( last_x == last_y ) {
                  Debug(9, "Matching neighbours, setting to %d", last_x);
                  // Add to the blob from the x side (either side really)
                  *pdiff = last_x;
                  alarm_blob_pixels++;
                  bsx->count++;
                  if ( x > bsx->hi_x ) bsx->hi_x = x;
                  if ( (int)y > bsx->hi_y ) bsx->hi_y = y;
                } else {
                  // Aggregate blobs
                  bsm = bsx->count>=bsy->count?bsx:bsy;
                  bss = bsm==bsx?bsy:bsx;

                  Debug(9,
                      "Different neighbours, setting pixels of %d to %d\n"
                      "Master blob t:%d, c:%d, lx:%d, hx:%d, ly:%d, hy:%d\n"
                      "Slave blob t:%d, c:%d, lx:%d, hx:%d, ly:%d, hy:%d\n",
                      bss->tag, bsm->tag,
                      bsm->tag, bsm->count, bsm->lo_x, bsm->hi_x, bsm->lo_y, bsm->hi_y,
                      bss->tag, bss->count, bss->lo_x, bss->hi_x, bss->lo_y, bss->hi_y
                      );
                  // Now change all those pixels to the other setting
                  int changed = 0;
                  for ( int sy = bss->lo_y; sy <= bss->hi_y; sy++ ) {
                    int lo_sx = bss->lo_x>=ranges[sy].lo_x?bss->lo_x:ranges[sy].lo_x;
                    int hi_sx = bss->hi_x<=ranges[sy].hi_x?bss->hi_x:ranges[sy].hi_x;

                    Debug(9,
                        "Changing %d, %d->%d Range %d->%d",
                        sy, lo_sx, hi_sx, ranges[sy].lo_x, ranges[sy].hi_x
                        );
                    spdiff = diff_buff + ((diff_width * sy) + lo_sx);
                    for ( int sx = lo_sx; sx <= hi_sx; sx++, spdiff++ ) {
                      Debug(9, "Pixel at %d,%d (%p) is %d", sx, sy, spdiff, *spdiff);
                      if ( *spdiff == bss->tag ) {
                        Debug(9, "Setting pixel");
                        *spdiff = bsm->tag;
                        changed++;
                      }
                    }
                  }
                  *pdiff = bsm->tag;
                  alarm_blob_pixels++;
                  if ( !changed ) {
                    Info(
                        "Master blob t:%d, c:%d, lx:%d, hx:%d, ly:%d, hy:%d\n"
                        "Slave blob t:%d, c:%d, lx:%d, hx:%d, ly:%d, hy:%d",
                        bsm->tag, bsm->count, bsm->lo_x, bsm->hi_x, bsm->lo_y, bsm->hi_y,
                        bss->tag, bss->count, bss->lo_x, bss->hi_x, bss->lo_y, bss->hi_y
                        );
                    Error("No pixels changed, exiting");
                    exit(-1);
                  }

                  // Merge the slave blob into the master
                  bsm->count += bss->count+1;
                  if ( x > bsm->hi_x ) bsm->hi_x = x;
                  if ( (int)y > bsm->hi_y ) bsm->hi_y = y;
                  if ( bss->lo_x < bsm->lo_x ) bsm->lo_x = bss->lo_x;
                  if ( bss->lo_y < bsm->lo_y ) bsm->lo_y = bss->lo_y;
                  if ( bss->hi_x > bsm->hi_x ) bsm->hi_x = bss->hi_x;
                  if ( bss->hi_y > bsm->hi_y ) bsm->hi_y = bss->hi_y;

                  alarm_blobs--;

                  Debug(6, "Merging blob %d with %d at %d,%d, %d current blobs",
                      bss->tag, bsm->tag, x, y, alarm_blobs);

                  // Clear out the old blob
                  bss->tag = 0;
                  bss->count = 0;
                  bss->lo_x = 0;
                  bss->lo_y = 0;
                  bss->hi_x = 0;
                  bss->hi_y = 0;
                }
              } else {
                Debug(9, "Setting to left neighbour %d", last_x);
                // Add to the blob from the x side 
                *pdiff = last_x;
                alarm_blob_pixels++;
                bsx->count++;
                if ( x > bsx->hi_x ) bsx->hi_x = x;
                if ( (int)y > bsx->hi_y ) bsx->hi_y = y;
              }
            } else {
              if ( last_y ) {
                Debug(9, "Top neighbour is %d", last_y);

                // Add to the blob from the y side
                BlobStats *bsy = &blob_stats[last_y];

                *pdiff = last_y;
                alarm_blob_pixels++;
                bsy->count++;
                if ( x > bsy->hi_x ) bsy->hi_x = x;
                if ( (int)y > bsy->hi_y ) bsy->hi_y = y;
              } else {
                // Create a new blob
                int i;
                for ( i = (WHITE-1); i > 0; i-- ) {
                  BlobStats *bs = &blob_stats[i];
                  // See if we can recycle one first, only if it's at least two rows up
                  if ( bs->count && bs->hi_y < (int)(y-1) ) {
                    if (
                        (min_blob_pixels && bs->count < min_blob_pixels)
                        ||
                        (max_blob_pixels && bs->count > max_blob_pixels)
                       ) {
                      if ( config.create_analysis_images || config.record_diag_images ) {
                        for ( int sy = bs->lo_y; sy <= bs->hi_y; sy++ ) {
                          spdiff = diff_buff + ((diff_width * sy) + bs->lo_x);
                          for ( int sx = bs->lo_x; sx <= bs->hi_x; sx++, spdiff++ ) {
                            if ( *spdiff == bs->tag ) {
                              *spdiff = BLACK;
                            }
                          }
                        }
                      }
                      alarm_blobs--;
                      alarm_blob_pixels -= bs->count;

                      Debug(6, "Eliminated blob %d, %d pixels (%d,%d - %d,%d), %d current blobs",
                          i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y, alarm_blobs);

                      bs->tag = 0;
                      bs->count = 0;
                      bs->lo_x = 0;
                      bs->lo_y = 0;
                      bs->hi_x = 0;
                      bs->hi_y = 0;
                    }
                  }
                  if ( !bs->count ) {
                    Debug(9, "Creating new blob %d", i);
                    *pdiff = i;
                    alarm_blob_pixels++;
                    bs->tag = i;
                    bs->count++;
                    bs->lo_x = bs->hi_x = x;
                    bs->lo_y = bs->hi_y = y;
                    alarm_blobs++;

                    Debug(6, "Created blob %d at %d,%d, %d current blobs", bs->tag, x, y, alarm_blobs);
                    break;
                  }
                }
                if ( i == 0 ) {
                  Warning("Max blob count reached. Unable to allocate new blobs so terminating. Zone settings may be too sensitive.");
                  x = hi_x+1;
                  y = hi_y+1;
                }
              }
            }
          }
        }
      }

      if ( config.record_diag_images )
        diff_image->WriteJpeg(diag_path, config.record_diag_images_fifo);

      if ( !alarm_blobs ) {
        return false;
      }

      Debug(5, "Got %d raw blob pixels, %d raw blobs, need %d -> %d, %d -> %d",
          alarm_blob_pixels, alarm_blobs, min_blob_pixels, max_blob_pixels, min_blobs, max_blobs);

      if ( config.record_diag_images_fifo ) {
        FifoDebug(5, "{\"zone\":%d,\"type\":\"RBLB\",\"pixels\":%d,\"blobs\":%d}",
            id, alarm_blob_pixels, alarm_blobs);
      }

      // Now eliminate blobs under the threshold
      for ( int i = 1; i < WHITE; i++ ) {
        BlobStats *bs = &blob_stats[i];
        if ( bs->count ) {
          if ( (min_blob_pixels && bs->count < min_blob_pixels) || (max_blob_pixels && bs->count > max_blob_pixels) ) {
            if ( config.create_analysis_images || config.record_diag_images ) {
              for ( int sy = bs->lo_y; sy <= bs->hi_y; sy++ ) {
                spdiff = diff_buff + ((diff_width * sy) + bs->lo_x);
                for ( int sx = bs->lo_x; sx <= bs->hi_x; sx++, spdiff++ ) {
                  if ( *spdiff == bs->tag ) {
                    *spdiff = BLACK;
                  }
                }
              }
            }
            alarm_blobs--;
            alarm_blob_pixels -= bs->count;

            Debug(6, "Eliminated blob %d, %d pixels (%d,%d - %d,%d), %d current blobs",
                i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y, alarm_blobs);

            bs->tag = 0;
            bs->count = 0;
            bs->lo_x = 0;
            bs->lo_y = 0;
            bs->hi_x = 0;
            bs->hi_y = 0;
          } else {
            Debug(6, "Preserved blob %d, %d pixels (%d,%d - %d,%d), %d current blobs",
                i, bs->count, bs->lo_x, bs->lo_y, bs->hi_x, bs->hi_y, alarm_blobs);
            if ( !min_blob_size || bs->count < min_blob_size ) min_blob_size = bs->count;
            if ( !max_blob_size || bs->count > max_blob_size ) max_blob_size = bs->count;
          }
        } // end if bs_count
      } // end for i < WHITE

      if ( config.record_diag_images )
        diff_image->WriteJpeg(diag_path, config.record_diag_images_fifo);

      Debug(5, "Got %d blob pixels, %d blobs, need %d -> %d, %d -> %d",
          alarm_blob_pixels, alarm_blobs, min_blob_pixels, max_blob_pixels, min_blobs, max_blobs);

      if ( config.record_diag_images_fifo ) {
        FifoDebug(5, "{\"zone\":%d,\"type\":\"FBLB\",\"pixels\":%d,\"blobs\":%d}",
            id, alarm_blob_pixels, alarm_blobs);
      }

      if ( alarm_blobs ) {
        if ( min_blobs && (alarm_blobs < min_blobs) ) {
          /* Not enough pixels alarmed */
          return false;
        } else if ( max_blobs && (alarm_blobs > max_blobs) ) {
          /* Too many pixels alarmed */
          overload_count = overload_frames;
          return false;
        }
      } else {
        /* No blobs */
        return false;
      }
      
      if ( max_blob_pixels != 0 )
        score = (100*alarm_blob_pixels)/(max_blob_pixels);
      else 
        score = (100*alarm_blob_pixels)/polygon.Area();
      
      if ( score < 1 )
        score = 1; /* Fix for score of 0 when frame meets thresholds but alarmed area is not big enough */
      Debug(5, "Current score is %d", score);

      alarm_lo_x = polygon.HiX()+1;
      alarm_hi_x = polygon.LoX()-1;
      alarm_lo_y = polygon.HiY()+1;
      alarm_hi_y = polygon.LoY()-1;

      for ( int i = 1; i < WHITE; i++ ) {
        BlobStats *bs = &blob_stats[i];
        if ( bs->count ) {
          if ( bs->count == max_blob_size ) {
            if ( config.weighted_alarm_centres ) {
              unsigned long x_total = 0;
              unsigned long y_total = 0;

              for ( int sy = bs->lo_y; sy <= bs->hi_y; sy++ ) {
                spdiff = diff_buff + ((diff_width * sy) + bs->lo_x);
                for ( int sx = bs->lo_x; sx <= bs->hi_x; sx++, spdiff++ ) {
                  if ( *spdiff == bs->tag ) {
                    x_total += sx;
                    y_total += sy;
                  }
                }
              }
              alarm_mid_x = int(round(x_total/bs->count));
              alarm_mid_y = int(round(y_total/bs->count));
            } else {
              alarm_mid_x = int((bs->hi_x+bs->lo_x+1)/2);
              alarm_mid_y = int((bs->hi_y+bs->lo_y+1)/2);
            }
          }

          if ( alarm_lo_x > bs->lo_x ) alarm_lo_x = bs->lo_x;
          if ( alarm_lo_y > bs->lo_y ) alarm_lo_y = bs->lo_y;
          if ( alarm_hi_x < bs->hi_x ) alarm_hi_x = bs->hi_x;
          if ( alarm_hi_y < bs->hi_y ) alarm_hi_y = bs->hi_y;
        } // end if bs->count
      } // end for i < WHITE
    } else {
      alarm_mid_x = int((alarm_hi_x+alarm_lo_x+1)/2);
      alarm_mid_y = int((alarm_hi_y+alarm_lo_y+1)/2);
    }
  }

  if ( type == INCLUSIVE ) {
    // score >>= 1;
    score /= 2;
  } else if ( type == EXCLUSIVE ) {
    // score <<= 1;
    score *= 2;
  }

  Debug(5, "Adjusted score is %d", score);

  // Now outline the changed region
  if ( score ) {
    alarm_box = Box(Coord(alarm_lo_x, alarm_lo_y), Coord(alarm_hi_x, alarm_hi_y));

    //if ( monitor->followMotion() )
    if ( true ) {
      alarm_centre = Coord(alarm_mid_x, alarm_mid_y);
    } else {
      alarm_centre = alarm_box.Centre();
    }

    if ( (type < PRECLUSIVE) && (check_method >= BLOBS) && config.create_analysis_images ) {

      // First mask out anything we don't want
      for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
        pdiff = diff_buff + ((diff_width * y) + lo_x);

        int lo_x2 = ranges[y].lo_x;
        int hi_x2 = ranges[y].hi_x;

        int lo_gap = lo_x2-lo_x;
        if ( lo_gap > 0 ) {
          if ( lo_gap == 1 ) {
            *pdiff++ = BLACK;
          } else {
            memset(pdiff, BLACK, lo_gap);
            pdiff += lo_gap;
          }
        }

        const uint8_t* ppoly = pg_image->Buffer(lo_x2, y);
        for ( int x = lo_x2; x <= hi_x2; x++, pdiff++, ppoly++ ) {
          if ( !*ppoly ) {
            *pdiff = BLACK;
          }
        }

        int hi_gap = hi_x-hi_x2;
        if ( hi_gap > 0 ) {
          if ( hi_gap == 1 ) {
            *pdiff = BLACK;
          } else {
            memset(pdiff, BLACK, hi_gap);
          }
        }
      } // end for y

      if ( monitor->Colours() == ZM_COLOUR_GRAY8 ) {
        image = diff_image->HighlightEdges(alarm_rgb, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, &polygon.Extent());
      } else {
        image = diff_image->HighlightEdges(alarm_rgb, monitor->Colours(), monitor->SubpixelOrder(), &polygon.Extent());
      }

      // Only need to delete this when 'image' becomes detached and points somewhere else
      delete diff_image;
    } else {
      delete image;
      image = 0;
    }

    Debug(1, "%s: Pixel Diff: %d, Alarm Pixels: %d, Filter Pixels: %d, Blob Pixels: %d, Blobs: %d, Score: %d",
        Label(), pixel_diff, alarm_pixels, alarm_filter_pixels, alarm_blob_pixels, alarm_blobs, score);
  }
  return true;
}

bool Zone::ParsePolygonString(const char *poly_string, Polygon &polygon) {
  Debug(3, "Parsing polygon string '%s'", poly_string);

  char *str_ptr = new char[strlen(poly_string)+1];
  char *str = str_ptr;
  strcpy(str, poly_string);

  char *ws;
  int n_coords = 0;
  int max_n_coords = strlen(str)/4;
  Coord *coords = new Coord[max_n_coords];
  while( true ) {
    if ( *str == '\0' ) {
      break;
    }
    ws = strchr(str, ' ');
    if ( ws ) {
      *ws = '\0';
    }
    char *cp = strchr(str, ',');
    if ( !cp ) {
      Error("Bogus coordinate %s found in polygon string", str);
      delete[] coords;
      delete[] str_ptr;
      return false;
    } else {
      *cp = '\0';
      char *xp = str;
      char *yp = cp+1;

      int x = atoi(xp);
      int y = atoi(yp);

      Debug(3, "Got coordinate %d,%d from polygon string", x, y);
#if 0
      if ( x < 0 )
        x = 0;
      else if ( x >= width )
        x = width-1;
      if ( y < 0 )
        y = 0;
      else if ( y >= height )
        y = height-1;
#endif
      coords[n_coords++] = Coord( x, y );
    }
    if ( ws )
      str = ws+1;
    else
      break;
  }
  polygon = Polygon(n_coords, coords);

  Debug(3, "Successfully parsed polygon string");
  //printf( "Area: %d\n", pg.Area() );
  //printf( "Centre: %d,%d\n", pg.Centre().X(), pg.Centre().Y() );

  delete[] coords;
  delete[] str_ptr;

  return true;
}

bool Zone::ParseZoneString(const char *zone_string, int &zone_id, int &colour, Polygon &polygon) {
  Debug(3, "Parsing zone string '%s'", zone_string);

  char *str_ptr = new char[strlen(zone_string)+1];
  char *str = str_ptr;
  strcpy(str, zone_string);

  char *ws = strchr(str, ' ');
  if ( !ws ) {
    Debug(3, "No initial whitespace found in zone string '%s', finishing", str);
  }
  zone_id = strtol(str, 0, 10);
  Debug(3, "Got zone %d from zone string", zone_id);
  if ( !ws ) {
    delete[] str_ptr;
    return true;
  }

  *ws = '\0';
  str = ws+1;

  ws = strchr(str, ' ');
  if ( !ws ) {
    Debug(3, "No secondary whitespace found in zone string '%s', finishing", zone_string);
  }
  colour = strtol(str, 0, 16);
  Debug(3, "Got colour %06x from zone string", colour);
  if ( !ws ) {
    delete[] str_ptr;
    return true;
  }
  *ws = '\0';
  str = ws+1;

  bool result = ParsePolygonString(str, polygon);

  //printf( "Area: %d\n", pg.Area() );
  //printf( "Centre: %d,%d\n", pg.Centre().X(), pg.Centre().Y() );

  delete[] str_ptr;

  return result;
}

int Zone::Load(Monitor *monitor, Zone **&zones) {
  static char sql[ZM_SQL_MED_BUFSIZ];

  db_mutex.lock();
  snprintf(sql, sizeof(sql), "select Id,Name,Type+0,Units,Coords,AlarmRGB,CheckMethod+0,MinPixelThreshold,MaxPixelThreshold,MinAlarmPixels,MaxAlarmPixels,FilterX,FilterY,MinFilterPixels,MaxFilterPixels,MinBlobPixels,MaxBlobPixels,MinBlobs,MaxBlobs,OverloadFrames,ExtendAlarmFrames from Zones where MonitorId = %d order by Type, Id", monitor->Id());
  if ( mysql_query(&dbconn, sql) ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
    db_mutex.unlock();
    return 0;
  }

  MYSQL_RES *result = mysql_store_result( &dbconn );
  if ( !result ) {
    Error("Can't use query result: %s", mysql_error(&dbconn));
    db_mutex.unlock();
    return 0;
  }
  db_mutex.unlock();
  int n_zones = mysql_num_rows(result);
  Debug(1, "Got %d zones for monitor %s", n_zones, monitor->Name());
  delete[] zones;
  zones = new Zone *[n_zones];
  for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
    int col = 0;

    int Id = atoi(dbrow[col++]);
    const char *Name = dbrow[col++];
    int Type = atoi(dbrow[col++]);
    const char *Units = dbrow[col++];
    const char *Coords = dbrow[col++];
    int AlarmRGB = dbrow[col]?atoi(dbrow[col]):0; col++;
    int CheckMethod = atoi(dbrow[col++]);
    int MinPixelThreshold = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MaxPixelThreshold = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MinAlarmPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MaxAlarmPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
    int FilterX = dbrow[col]?atoi(dbrow[col]):0; col++;
    int FilterY = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MinFilterPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MaxFilterPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MinBlobPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MaxBlobPixels = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MinBlobs = dbrow[col]?atoi(dbrow[col]):0; col++;
    int MaxBlobs = dbrow[col]?atoi(dbrow[col]):0; col++;
    int OverloadFrames = dbrow[col]?atoi(dbrow[col]):0; col++;
    int ExtendAlarmFrames = dbrow[col]?atoi(dbrow[col]):0; col++;

    /* HTML colour code is actually BGR in memory, we want RGB */
    AlarmRGB = rgb_convert(AlarmRGB, ZM_SUBPIX_ORDER_BGR);

    Debug(5, "Parsing polygon %s", Coords);
    Polygon polygon;
    if ( !ParsePolygonString(Coords, polygon) ) {
      Error("Unable to parse polygon string '%s' for zone %d/%s for monitor %s, ignoring", Coords, Id, Name, monitor->Name());
      n_zones -= 1;
      continue;
    }

    if ( polygon.LoX() < 0 || polygon.HiX() >= (int)monitor->Width() 
        || polygon.LoY() < 0 || polygon.HiY() >= (int)monitor->Height() ) {
      Error("Zone %d/%s for monitor %s extends outside of image dimensions, (%d,%d), (%d,%d), ignoring",
          Id, Name, monitor->Name(), polygon.LoX(), polygon.LoY(), polygon.HiX(), polygon.HiY());
      n_zones -= 1;
      continue;
    }

    if ( false && !strcmp( Units, "Percent" ) ) {
      MinAlarmPixels = (MinAlarmPixels*polygon.Area())/100;
      MaxAlarmPixels = (MaxAlarmPixels*polygon.Area())/100;
      MinFilterPixels = (MinFilterPixels*polygon.Area())/100;
      MaxFilterPixels = (MaxFilterPixels*polygon.Area())/100;
      MinBlobPixels = (MinBlobPixels*polygon.Area())/100;
      MaxBlobPixels = (MaxBlobPixels*polygon.Area())/100;
    }

    if ( atoi(dbrow[2]) == Zone::INACTIVE ) {
      zones[i] = new Zone(monitor, Id, Name, polygon);
    } else if ( atoi(dbrow[2]) == Zone::PRIVACY ) {
      zones[i] = new Zone(monitor, Id, Name, (Zone::ZoneType)Type, polygon);
    }
    zones[i] = new Zone(monitor, Id, Name, (Zone::ZoneType)Type, polygon, AlarmRGB, (Zone::CheckMethod)CheckMethod, MinPixelThreshold, MaxPixelThreshold, MinAlarmPixels, MaxAlarmPixels, Coord( FilterX, FilterY ), MinFilterPixels, MaxFilterPixels, MinBlobPixels, MaxBlobPixels, MinBlobs, MaxBlobs, OverloadFrames, ExtendAlarmFrames);
  } // end foreach row
  mysql_free_result(result);
  return n_zones;
} // end int Zone::Load(Monitor *monitor, Zone **&zones)

bool Zone::DumpSettings(char *output, bool /*verbose*/) {
  output[0] = 0;

  sprintf( output+strlen(output), "  Id : %d\n", id );
  sprintf( output+strlen(output), "  Label : %s\n", label );
  sprintf( output+strlen(output), "  Type: %d - %s\n", type,
      type==ACTIVE?"Active":(
        type==INCLUSIVE?"Inclusive":(
          type==EXCLUSIVE?"Exclusive":(
            type==PRECLUSIVE?"Preclusive":(
              type==INACTIVE?"Inactive":(
                type==PRIVACY?"Privacy":"Unknown"
                ))))));
  sprintf( output+strlen(output), "  Shape : %d points\n", polygon.getNumCoords() );
  for ( int i = 0; i < polygon.getNumCoords(); i++ ) {
    sprintf( output+strlen(output), "  %i: %d,%d\n", i, polygon.getCoord( i ).X(), polygon.getCoord( i ).Y() );
  }
  sprintf( output+strlen(output), "  Alarm RGB : %06x\n", alarm_rgb );
  sprintf( output+strlen(output), "  Check Method: %d - %s\n", check_method,
      check_method==ALARMED_PIXELS?"Alarmed Pixels":(
        check_method==FILTERED_PIXELS?"FilteredPixels":(
          check_method==BLOBS?"Blobs":"Unknown"
          )));
  sprintf( output+strlen(output), "  Min Pixel Threshold : %d\n", min_pixel_threshold );
  sprintf( output+strlen(output), "  Max Pixel Threshold : %d\n", max_pixel_threshold );
  sprintf( output+strlen(output), "  Min Alarm Pixels : %d\n", min_alarm_pixels );
  sprintf( output+strlen(output), "  Max Alarm Pixels : %d\n", max_alarm_pixels );
  sprintf( output+strlen(output), "  Filter Box : %d,%d\n", filter_box.X(), filter_box.Y() );
  sprintf( output+strlen(output), "  Min Filter Pixels : %d\n", min_filter_pixels );
  sprintf( output+strlen(output), "  Max Filter Pixels : %d\n", max_filter_pixels );
  sprintf( output+strlen(output), "  Min Blob Pixels : %d\n", min_blob_pixels );
  sprintf( output+strlen(output), "  Max Blob Pixels : %d\n", max_blob_pixels );
  sprintf( output+strlen(output), "  Min Blobs : %d\n", min_blobs );
  sprintf( output+strlen(output), "  Max Blobs : %d\n", max_blobs );
  return( true );
}

void Zone::std_alarmedpixels(Image* pdiff_image, const Image* ppoly_image, unsigned int* pixel_count, unsigned int* pixel_sum) {
  uint32_t pixelsalarmed = 0;
  uint32_t pixelsdifference = 0;
  uint8_t calc_max_pixel_threshold = 255;
  unsigned int lo_y;
  unsigned int hi_y;

  if ( max_pixel_threshold )
    calc_max_pixel_threshold = max_pixel_threshold;

  lo_y = polygon.LoY();
  hi_y = polygon.HiY();
  for ( unsigned int y = lo_y; y <= hi_y; y++ ) {
    unsigned int lo_x = ranges[y].lo_x;
    unsigned int hi_x = ranges[y].hi_x;

    Debug(7, "Checking line %d from %d -> %d", y, lo_x, hi_x);
    uint8_t *pdiff = (uint8_t*)pdiff_image->Buffer(lo_x, y);
    const uint8_t *ppoly = ppoly_image->Buffer(lo_x, y);

    for ( unsigned int x = lo_x; x <= hi_x; x++, pdiff++, ppoly++ ) {
      if ( *ppoly && (*pdiff > min_pixel_threshold) && (*pdiff <= calc_max_pixel_threshold) ) {
        pixelsalarmed++;
        pixelsdifference += *pdiff;
        *pdiff = WHITE;
      } else {
        *pdiff = BLACK;
      }
    }
  }

  /* Store the results */
  *pixel_count = pixelsalarmed;
  *pixel_sum = pixelsdifference;
  Debug(7, "STORED pixelsalarmed(%d), pixelsdifference(%d)", pixelsalarmed, pixelsdifference);
}
