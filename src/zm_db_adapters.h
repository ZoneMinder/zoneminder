//
// ZoneMinder Core Interfaces, $Date$, $Revision$
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

#ifndef ZM_DB_ADAPTERS_H
#define ZM_DB_ADAPTERS_H

#include "zm_frame.h"

#include "soci.h"

namespace soci
{

// from_base: when converting base type -> custom type
// to_base:   when converting custom type -> base type

template <> struct type_conversion<FrameType>
{
    typedef int base_type;

    static void from_base(int i, indicator ind, FrameType & mi)
    {
        if (ind == i_null)
        {
            throw soci_error("Null value not allowed for this type");
        }
        mi = (FrameType)i;
    }

    static void to_base(const FrameType & mi, int & i, indicator & ind)
    {
        i = (int)mi;
        ind = i_ok;
    }
};

template <> struct type_conversion<Microseconds>
{
    typedef int64 base_type;

    static void from_base(int64 i, indicator ind, Microseconds & mi)
    {
        if (ind == i_null)
        {
            throw soci_error("Null value not allowed for this type");
        }
        
        mi = Microseconds(i);
    }

    static void to_base(const Microseconds & mi, int64 & i, indicator & ind)
    {
        i = static_cast<int64>(mi.count());
        ind = i_ok;
    }
};

template <> struct type_conversion<SystemTimePoint>
{
    typedef int64 base_type;

    static void from_base(int64 i, indicator ind, SystemTimePoint & mi)
    {
        if (ind == i_null)
        {
            throw soci_error("Null value not allowed for this type");
        }
        
        mi = static_cast<SystemTimePoint>(std::chrono::system_clock::from_time_t(i));
    }

    static void to_base(const SystemTimePoint & mi, int64 & i, indicator & ind)
    {
        i = static_cast<int64>(std::chrono::system_clock::to_time_t(mi));
        ind = i_ok;
    }
};

template<> struct type_conversion<Frame>
{
    typedef values base_type;
    static void from_base(values const & v, indicator & ind, Frame & p)
    {
        p.event_id = v.get<event_id_t>("event_id");
        p.frame_id = v.get<int>("frame_id");
        p.type = v.get<FrameType>("type");
        p.timestamp = v.get<SystemTimePoint>("timestamp");
        p.delta = v.get<Microseconds>("delta");
        p.score = v.get<int>("score");
    }
    static void to_base(const Frame & p, values & v, indicator & ind)
    {
        v.set("event_id", p.event_id);
        v.set("frame_id", p.frame_id);
        v.set("type", p.type);
        v.set("timestamp", p.timestamp);
        v.set("delta", p.delta);
        v.set("score", p.score);
        ind = i_ok;
    }
};
}

#endif // ZM_DB_H
