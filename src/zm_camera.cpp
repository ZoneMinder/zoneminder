//
// ZoneMinder Camera Class Implementation, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
#include "zm_camera.h"

Camera::Camera( SourceType p_type, int p_width, int p_height, int p_palette, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) : type( p_type ), width( p_width), height( p_height ), palette( p_palette ), brightness( p_brightness ), contrast( p_contrast ), hue( p_hue ), colour( p_colour ), capture( p_capture )
{
	colours = (palette==VIDEO_PALETTE_GREY?1:3);
}

Camera::~Camera()
{
}

