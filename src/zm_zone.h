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

#include "zm_box.h"
#include "zm_define.h"
#include "zm_config.h"
#include "zm_poly.h"
#include "zm_rgb.h"
#include "zm_zone_stats.h"
#include "zm_vector2.h"

#include "soci/soci.h"

#include <algorithm>
#include <string>
#include <vector>

class Event;
class Image;
class Monitor;

//
// This describes a 'zone', or an area of an image that has certain
// detection characteristics.
//

class Zone {
  protected:
    struct Range {
      int lo_x;
      int hi_x;
      int off_x;
    };
    typedef struct {
      unsigned char tag;
      int count;
      int lo_x;
      int hi_x;
      int lo_y;
      int hi_y;
    } BlobStats;
  public:
    typedef enum { ACTIVE=1, INCLUSIVE, EXCLUSIVE, PRECLUSIVE, INACTIVE, PRIVACY } ZoneType;
    typedef enum { ALARMED_PIXELS=1, FILTERED_PIXELS, BLOBS } CheckMethod;
    typedef enum { PIXELS=1, PERCENT } Units;

  protected:
    // Inputs
    const std::shared_ptr<Monitor> monitor;

    unsigned int         id;
    std::string label;
    ZoneType    type;
    Polygon     polygon;
    Rgb         alarm_rgb;
    CheckMethod    check_method;

    int         min_pixel_threshold;
    int         max_pixel_threshold;

    int         min_alarm_pixels;
    int         max_alarm_pixels;

    Vector2       filter_box;
    int         min_filter_pixels;
    int         max_filter_pixels;

    BlobStats   blob_stats[256];
    int         min_blob_pixels;
    int         max_blob_pixels;
    int         min_blobs;
    int         max_blobs;

    int         overload_frames;
    int         extend_alarm_frames;

    // Outputs/Statistics
    bool        alarmed;
    bool        was_alarmed;
    ZoneStats   stats;
    Image      *pg_image;
    Range      *ranges;
    Image      *image;

    int         overload_count;
    int         extend_alarm_count;
    std::string diag_path;

  protected:
    void Setup(
        ZoneType p_type,
        const Polygon &p_polygon,
        const Rgb p_alarm_rgb,
        CheckMethod p_check_method,
        int p_min_pixel_threshold,
        int p_max_pixel_threshold,
        int p_min_alarm_pixels,
        int p_max_alarm_pixels,
        const Vector2 &p_filter_box,
        int p_min_filter_pixels,
        int p_max_filter_pixels,
        int p_min_blob_pixels,
        int p_max_blob_pixels,
        int p_min_blobs,
        int p_max_blobs,
        int p_overload_frames,
        int p_extend_alarm_frames);

    void std_alarmedpixels(Image* pdiff_image, const Image* ppoly_image, unsigned int* pixel_count, unsigned int* pixel_sum);

  public:
    Zone(
        const std::shared_ptr<Monitor> &p_monitor,
        unsigned int p_id,
        const char *p_label,
        ZoneType p_type,
        const Polygon &p_polygon,
        const Rgb p_alarm_rgb,
        CheckMethod p_check_method,
        int p_min_pixel_threshold=15,
        int p_max_pixel_threshold=0,
        int p_min_alarm_pixels=50,
        int p_max_alarm_pixels=75000,
        const Vector2 &p_filter_box = Vector2(3, 3),
        int p_min_filter_pixels=50,
        int p_max_filter_pixels=50000,
        int p_min_blob_pixels=10,
        int p_max_blob_pixels=0,
        int p_min_blobs=0,
        int p_max_blobs=0,
        int p_overload_frames=0,
        int p_extend_alarm_frames=0)
          :
            monitor(p_monitor),
            id(p_id),
            label(p_label),
            blob_stats{},
            stats(p_id)
    {
      Setup(p_type, p_polygon, p_alarm_rgb, p_check_method, p_min_pixel_threshold, p_max_pixel_threshold, p_min_alarm_pixels, p_max_alarm_pixels, p_filter_box, p_min_filter_pixels, p_max_filter_pixels, p_min_blob_pixels, p_max_blob_pixels, p_min_blobs, p_max_blobs, p_overload_frames, p_extend_alarm_frames );
    }

    Zone(const std::shared_ptr<Monitor>&p_monitor, unsigned int p_id, const char *p_label, const Polygon &p_polygon)
      :
        monitor(p_monitor),
        id(p_id),
        label(p_label),
        blob_stats{},
        stats(p_id)
    {
      Setup(Zone::INACTIVE, p_polygon, kRGBBlack, (Zone::CheckMethod)0, 0, 0, 0, 0, Vector2(0, 0), 0, 0, 0, 0, 0, 0, 0, 0);
    }
    Zone(const std::shared_ptr<Monitor>&p_monitor, unsigned int p_id, const char *p_label, ZoneType p_type, const Polygon &p_polygon)
      :
        monitor(p_monitor),
        id(p_id),
        label(p_label),
        blob_stats{},
        stats(p_id)
    {
      Setup(p_type, p_polygon, kRGBBlack, (Zone::CheckMethod)0, 0, 0, 0, 0, Vector2(0, 0), 0, 0, 0, 0, 0, 0, 0, 0 );
    }

    Zone(const Zone &z);
    ~Zone();

    inline unsigned int Id() const { return id; }
    inline const char *Label() const { return label.c_str(); }
    const std::string &Name() const { return label; }
    inline ZoneType Type() const { return type; }
    inline bool IsActive() const { return( type == ACTIVE ); }
    inline bool IsInclusive() const { return( type == INCLUSIVE ); }
    inline bool IsExclusive() const { return( type == EXCLUSIVE ); }
    inline bool IsPreclusive() const { return( type == PRECLUSIVE ); }
    inline bool IsInactive() const { return( type == INACTIVE ); }
    inline bool IsPrivacy() const { return( type == PRIVACY ); }
    inline const Image *AlarmImage() const { return image; }
    inline const Polygon &GetPolygon() const { return polygon; }
    inline bool Alarmed() const { return alarmed; }
    inline bool WasAlarmed() const { return was_alarmed; }
    inline void SetAlarm() { was_alarmed = alarmed; alarmed = true; }
    inline void ClearAlarm() { was_alarmed = alarmed; alarmed = false; }
    inline Vector2 GetAlarmCentre() const { return stats.alarm_centre_; }
    inline unsigned int Score() const { return stats.score_; }

    inline void ResetStats() {
      alarmed = false;
      was_alarmed = false;
      stats.Reset();
    }
    void RecordStats( const Event *event );
    ZoneStats const &GetStats() const {
      stats.DumpToLog("GetStats");
      return stats;
    };

    bool CheckAlarms(const Image *delta_image);
    bool DumpSettings(char *output, bool verbose) const;

    static bool ParsePolygonString( const char *polygon_string, Polygon &polygon );
    static bool ParseZoneString( const char *zone_string, unsigned int &zone_id, int &colour, Polygon &polygon );
    static std::vector<Zone> Load(const std::shared_ptr<Monitor> &monitor);
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

    inline const Image *getPgImage() const { return pg_image; }
    inline const Range *getRanges() const { return ranges; }
};

namespace soci {
  // Database conversion specialization 
  // needed to be here because of issues with forward
  // declarations of various types, see zm_db_adapters.h

  template<> struct type_conversion<Zone::ZoneType>
  {
      typedef std::string base_type;
      static void from_base(const std::string & v, indicator & ind, Zone::ZoneType & p)
      {
          if (ind == i_null)
              throw soci_error("Null value not allowed for this type");

          if( v.compare("Active") == 0 )
            p = Zone::ZoneType::ACTIVE;
          else if( v.compare("Inclusive") == 0 )
            p = Zone::ZoneType::INCLUSIVE;
          else if( v.compare("Exclusive") == 0 )
            p = Zone::ZoneType::EXCLUSIVE;
          else if( v.compare("Preclusive") == 0 )
            p = Zone::ZoneType::PRECLUSIVE;
          else if( v.compare("Inactive") == 0 )
            p = Zone::ZoneType::INACTIVE;
          else if( v.compare("Privacy") == 0 )
            p = Zone::ZoneType::PRIVACY;
          else
            throw soci_error("Value not allowed for this type");
      }
      static void to_base(Zone::ZoneType & p, std::string & v, indicator & ind)
      {
          switch( p ) {
            case Zone::ZoneType::ACTIVE:
              v = "Active";
              ind = i_ok;
              return;
            case Zone::ZoneType::INCLUSIVE:
              v = "Inclusive";
              ind = i_ok;
              return;
            case Zone::ZoneType::EXCLUSIVE:
              v = "Exclusive";
              ind = i_ok;
              return;
            case Zone::ZoneType::PRECLUSIVE:
              v = "Preclusive";
              ind = i_ok;
              return;
            case Zone::ZoneType::INACTIVE:
              v = "Inactive";
              ind = i_ok;
              return;
            case Zone::ZoneType::PRIVACY:
              v = "Privacy";
              ind = i_ok;
              return;
          }
          throw soci_error("Value not allowed for this type");
      }
  };

  template<> struct type_conversion<Zone::CheckMethod>
  {
      typedef std::string base_type;
      static void from_base(const std::string & v, indicator & ind, Zone::CheckMethod & p)
      {
          if (ind == i_null)
              throw soci_error("Null value not allowed for this type");

          if( v.compare("AlarmedPixels") == 0 )
            p = Zone::CheckMethod::ALARMED_PIXELS;
          else if( v.compare("FilteredPixels") == 0 )
            p = Zone::CheckMethod::FILTERED_PIXELS;
          else if( v.compare("Blobs") == 0 )
            p = Zone::CheckMethod::BLOBS;
          else
            throw soci_error("Value not allowed for this type");
      }
      static void to_base(Zone::CheckMethod & p, std::string & v, indicator & ind)
      {
          switch( p ) {
            case Zone::CheckMethod::ALARMED_PIXELS:
              v = "AlarmedPixels";
              ind = i_ok;
              return;
            case Zone::CheckMethod::FILTERED_PIXELS:
              v = "FilteredPixels";
              ind = i_ok;
              return;
            case Zone::CheckMethod::BLOBS:
              v = "Blobs";
              ind = i_ok;
              return;
          }
          throw soci_error("Value not allowed for this type");
      }
  };

  template<> struct type_conversion<Zone::Units>
  {
      typedef std::string base_type;
      static void from_base(const std::string & v, indicator & ind, Zone::Units & p)
      {
          if (ind == i_null)
              throw soci_error("Null value not allowed for this type");

          if( v.compare("Pixels") == 0 )
            p = Zone::Units::PIXELS;
          else if( v.compare("Percent") == 0 )
            p = Zone::Units::PERCENT;
          else
            throw soci_error("Value not allowed for this type");
      }
      static void to_base(Zone::Units & p, std::string & v, indicator & ind)
      {
          switch( p ) {
            case Zone::Units::PIXELS:
              v = "Pixels";
              ind = i_ok;
              return;
            case Zone::Units::PERCENT:
              v = "Percent";
              ind = i_ok;
              return;
          }
          throw soci_error("Value not allowed for this type");
      }
  };


}

#endif // ZM_ZONE_H
