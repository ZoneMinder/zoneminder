//
// ZoneMinder Zone Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_ZONE_H
#define ZM_ZONE_H

#include "zm_rgb.h"
#include "zm_coord.h"
#include "zm_poly.h"
#include "zm_image.h"
#include "zm_event.h"

class Monitor;

//
// This describes a 'zone', or an area of an image that has certain
// detection characteristics.
//
class Zone
{
protected:
  struct Range
  {
    int lo_x;
    int hi_x;
    int off_x;
  };

public:
  typedef enum { ACTIVE=1, INCLUSIVE, EXCLUSIVE, PRECLUSIVE, INACTIVE, PRIVACY } ZoneType;
  typedef enum { ALARMED_PIXELS=1, FILTERED_PIXELS, BLOBS } CheckMethod;

protected:
  // Inputs
  Monitor      *monitor;

  int        id;
  char      *label;
  ZoneType    type;
  Polygon      polygon;
  Rgb        alarm_rgb;
  CheckMethod    check_method;

  int        min_pixel_threshold;
  int        max_pixel_threshold;

  int        min_alarm_pixels;
  int        max_alarm_pixels;

  Coord      filter_box;
  int        min_filter_pixels;
  int        max_filter_pixels;

  int        min_blob_pixels;
  int        max_blob_pixels;
  int        min_blobs;
  int        max_blobs;

  int       overload_frames;
  int        extend_alarm_frames;

  // Outputs/Statistics
  bool      alarmed;
  bool      was_alarmed;
  int        pixel_diff;
  unsigned int      alarm_pixels;
  int        alarm_filter_pixels;
  int        alarm_blob_pixels;
  int        alarm_blobs;
  int        min_blob_size;
  int        max_blob_size;
  Box        alarm_box;
  Coord      alarm_centre;
  unsigned int  score;
  Image      *pg_image;
  Range      *ranges;
  Image      *image;

  int       overload_count;
  int       extend_alarm_count;
  char      diag_path[PATH_MAX];

protected:
  void Setup( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Polygon &p_polygon, const Rgb p_alarm_rgb, CheckMethod p_check_method, int p_min_pixel_threshold, int p_max_pixel_threshold, int p_min_alarm_pixels, int p_max_alarm_pixels, const Coord &p_filter_box, int p_min_filter_pixels, int p_max_filter_pixels, int p_min_blob_pixels, int p_max_blob_pixels, int p_min_blobs, int p_max_blobs, int p_overload_frames, int p_extend_alarm_frames );
  void std_alarmedpixels(Image* pdiff_image, const Image* ppoly_image, unsigned int* pixel_count, unsigned int* pixel_sum);
  
public:
  Zone( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Polygon &p_polygon, const Rgb p_alarm_rgb, CheckMethod p_check_method, int p_min_pixel_threshold=15, int p_max_pixel_threshold=0, int p_min_alarm_pixels=50, int p_max_alarm_pixels=75000, const Coord &p_filter_box=Coord( 3, 3 ), int p_min_filter_pixels=50, int p_max_filter_pixels=50000, int p_min_blob_pixels=10, int p_max_blob_pixels=0, int p_min_blobs=0, int p_max_blobs=0, int p_overload_frames=0, int p_extend_alarm_frames=0 )
  {
    Setup( p_monitor, p_id, p_label, p_type, p_polygon, p_alarm_rgb, p_check_method, p_min_pixel_threshold, p_max_pixel_threshold, p_min_alarm_pixels, p_max_alarm_pixels, p_filter_box, p_min_filter_pixels, p_max_filter_pixels, p_min_blob_pixels, p_max_blob_pixels, p_min_blobs, p_max_blobs, p_overload_frames, p_extend_alarm_frames );
  }
  Zone( Monitor *p_monitor, int p_id, const char *p_label, const Polygon &p_polygon, const Rgb p_alarm_rgb, CheckMethod p_check_method, int p_min_pixel_threshold=15, int p_max_pixel_threshold=0, int p_min_alarm_pixels=50, int p_max_alarm_pixels=75000, const Coord &p_filter_box=Coord( 3, 3 ), int p_min_filter_pixels=50, int p_max_filter_pixels=50000, int p_min_blob_pixels=10, int p_max_blob_pixels=0, int p_min_blobs=0, int p_max_blobs=0, int p_overload_frames=0, int p_extend_alarm_frames=0)
  {
    Setup( p_monitor, p_id, p_label, Zone::ACTIVE, p_polygon, p_alarm_rgb, p_check_method, p_min_pixel_threshold, p_max_pixel_threshold, p_min_alarm_pixels, p_max_alarm_pixels, p_filter_box, p_min_filter_pixels, p_max_filter_pixels, p_min_blob_pixels, p_max_blob_pixels, p_min_blobs, p_max_blobs, p_overload_frames, p_extend_alarm_frames );
  }
  Zone( Monitor *p_monitor, int p_id, const char *p_label, const Polygon &p_polygon )
  {
    Setup( p_monitor, p_id, p_label, Zone::INACTIVE, p_polygon, RGB_BLACK, (Zone::CheckMethod)0, 0, 0, 0, 0, Coord( 0, 0 ), 0, 0, 0, 0, 0, 0, 0, 0 );
  }
  Zone( Monitor *p_monitor, int p_id, const char *p_label, ZoneType p_type, const Polygon &p_polygon )
  {
    Setup( p_monitor, p_id, p_label, p_type, p_polygon, RGB_BLACK, (Zone::CheckMethod)0, 0, 0, 0, 0, Coord( 0, 0 ), 0, 0, 0, 0, 0, 0, 0, 0 );
  }

public:
  ~Zone();

  inline int Id() const { return( id ); }
  inline const char *Label() const { return( label ); }
  inline ZoneType Type() const { return( type ); }
  inline bool IsActive() const { return( type == ACTIVE ); }
  inline bool IsInclusive() const { return( type == INCLUSIVE ); }
  inline bool IsExclusive() const { return( type == EXCLUSIVE ); }
  inline bool IsPreclusive() const { return( type == PRECLUSIVE ); }
  inline bool IsInactive() const { return( type == INACTIVE ); }
  inline bool IsPrivacy() const { return( type == PRIVACY ); }
  inline const Image *AlarmImage() const { return( image ); }
  inline const Polygon &GetPolygon() const { return( polygon ); }
  inline bool Alarmed() const { return( alarmed ); }
	inline bool WasAlarmed() const { return( was_alarmed ); }
	inline void SetAlarm() { was_alarmed = alarmed; alarmed = true; }
	inline void ClearAlarm() { was_alarmed = alarmed; alarmed = false; }
  inline Coord GetAlarmCentre() const { return( alarm_centre ); }
  inline unsigned int Score() const { return( score ); }

  inline void ResetStats()
  {
    alarmed = false;
		was_alarmed = false;
    pixel_diff = 0;
    alarm_pixels = 0;
    alarm_filter_pixels = 0;
    alarm_blob_pixels = 0;
    alarm_blobs = 0;
    min_blob_size = 0;
    max_blob_size = 0;
    score = 0;
  }
  void RecordStats( const Event *event );
  bool CheckAlarms( const Image *delta_image );
  bool DumpSettings( char *output, bool verbose );

  static bool ParsePolygonString( const char *polygon_string, Polygon &polygon );
  static bool ParseZoneString( const char *zone_string, int &zone_id, int &colour, Polygon &polygon );
  static int Load( Monitor *monitor, Zone **&zones );
  //=================================================
  bool CheckOverloadCount();
  int GetOverloadCount();
  void SetOverloadCount(int nOverCount);
  int GetOverloadFrames();
  //=================================================
  bool CheckExtendAlarmCount();
  int GetExtendAlarmCount();
  void SetExtendAlarmCount(int nOverCount);
  int GetExtendAlarmFrames();
  void SetScore(unsigned int nScore);
  void SetAlarmImage(const Image* srcImage);

  inline const Image *getPgImage() const { return( pg_image ); }
  inline const Range *getRanges() const { return( ranges ); }
};

#endif // ZM_ZONE_H
