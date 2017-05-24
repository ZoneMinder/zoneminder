//
// ZoneMinder base static javascript file, $Date$, $Revision$
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

//
// This file should only contain static JavaScript and no php.
// Use skin.js.php for JavaScript that need pre-processing
//

// Javascript window sizes
var popupSizes = {
    'bandwidth':    { 'width': 300, 'height': 230 },
    'console':      { 'width': 750, 'height': 310 },
    'control':      { 'width': 380, 'height': 480 },
    'controlcaps':  { 'width': 780, 'height': 320 },
    'controlcap':   { 'width': 400, 'height': 400 },
    'cycle':        { 'addWidth': 72, 'minWidth': 390, 'addHeight': 152 },
    'device':       { 'width': 260, 'height': 150 },
    'devices':      { 'width': 400, 'height': 240 },
    'donate':       { 'width': 500, 'height': 280 },
    'event':        { 'addWidth': 108, 'minWidth': 496, 'addHeight': 290, minHeight: 540 },
    'eventdetail':  { 'width': 600, 'height': 330 },
    'events':       { 'width': 1280, 'height': 780 },
    'export':       { 'width': 400, 'height': 340 },
    'filter':       { 'width': 720, 'height': 400 },
    'filtersave':   { 'width': 610, 'height': 120 },
    'frame':        { 'addWidth': 92, 'minWidth': 384, 'addHeight': 300 },
    'frames':       { 'width': 500, 'height': 600 },
    'function':     { 'width': 300, 'height': 140 },
    'group':        { 'width': 360, 'height': 300 },
    'groups':       { 'width': 540, 'height': 420 },
    'image':        { 'addWidth': 48, 'addHeight': 80 },
    'log':          { 'width': 1560, 'height': 800 },
    'login':        { 'width': 720, 'height': 480 },
    'logout':       { 'width': 260, 'height': 150 },
    'monitor':      { 'width': 550, 'height': 700 },
    'monitorpreset':{ 'width': 440, 'height': 310 },
    'monitorprobe': { 'width': 500, 'height': 275 },
    'monitorselect':{ 'width': 160, 'height': 200 },
    'montage':      { 'width': -1, 'height': -1 },
    'montagereview':{ 'width': 1000, 'height': 900 },
    'onvifprobe':   { 'width': 500, 'height': 500 },
    'optionhelp':   { 'width': 400, 'height': 320 },
    'options':      { 'width': 1290, 'height': 800 },
    'preset':       { 'width': 300, 'height': 220 },
    'settings':     { 'width': 220, 'height': 235 },
    'state':        { 'width': 400, 'height': 230 },
    'stats':        { 'width': 840, 'height': 200 },
    'timeline':     { 'width': 760, 'height': 620 },
    'user':         { 'width': 360, 'height': 420 },
    'version':      { 'width': 360, 'height': 270 },
    'video':        { 'width': 420, 'height': 360 },
    'videoview':    { 'addWidth': 48, 'addHeight': 80 },
    'watch':        { 'addWidth': 96, 'minWidth': 420, 'addHeight': 450 },
    'zone':         { 'addWidth': 620, 'addHeight': 440, 'minHeight': 600 },
    'zones':        { 'addWidth': 72, 'addHeight': 252 }
};
