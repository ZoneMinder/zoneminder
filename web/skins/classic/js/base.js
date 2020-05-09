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
/* eslint-disable key-spacing */
var popupSizes = {
  'bandwidth':    {'width': 300, 'height': 200},
  'console':      {'width': 750, 'height': 312},
  'control':      {'width': 480, 'height': 480},
  'controlcaps':  {'width': 780, 'height': 320},
  'controlcap':   {'width': 600, 'height': 500},
  'cycle':        {'addWidth': 32, 'minWidth': 384, 'addHeight': 62},
  'device':       {'width': 260, 'height': 150},
  'devices':      {'width': 400, 'height': 240},
  'donate':       {'width': 500, 'height': 480},
  'download':     {'width': 350, 'height': 315},
  'event':        {'addWidth': 108, 'minWidth': 496, 'addHeight': 230, 'minHeight': 540},
  'eventdetail':  {'width': 600, 'height': 420},
  'events':       {'width': 1220, 'height': 780},
  'export':       {'width': 500, 'height': 640},
  'filter':       {'width': 900, 'height': 700},
  'frame':        {'addWidth': 32, 'minWidth': 384, 'addHeight': 200},
  'frames':       {'addWidth': 600, 'addHeight': 600},
  'function':     {'width': 350, 'height': 260},
  'group':        {'width': 760, 'height': 600},
  'groups':       {'width': 540, 'height': 420},
  'image':        {'addWidth': 48, 'addHeight': 80},
  'log':          {'width': 1180, 'height': 720},
  'login':        {'width': 720, 'height': 480},
  'logout':       {'width': 260, 'height': 150},
  'monitor':      {'width': 800, 'height': 780},
  'monitorpreset':{'width': 440, 'height': 210},
  'monitorprobe': {'width': 500, 'height': 275},
  'monitorselect':{'width': 160, 'height': 200},
  'montage':      {'width': -1, 'height': -1},
  'onvifprobe':   {'width': 700, 'height': 550},
  'optionhelp':   {'width': 400, 'height': 400},
  'options':      {'width': 1000, 'height': 660},
  'preset':       {'width': 300, 'height': 220},
  'server':       {'width': 600, 'height': 405},
  'settings':     {'width': 220, 'height': 235},
  'shutdown':     {'width': 400, 'height': 400},
  'state':        {'width': 400, 'height': 170},
  'stats':        {'width': 840, 'height': 200},
  'storage':      {'width': 600, 'height': 425},
  'timeline':     {'width': 760, 'height': 540},
  'user':         {'width': 460, 'height': 720},
  'version':      {'width': 360, 'height': 210},
  'video':        {'width': 420, 'height': 360},
  'videoview':    {'addWidth': 48, 'addHeight': 80},
  'watch':        {'addWidth': 96, 'minWidth': 420, 'addHeight': 384},
  'zone':         {'addWidth': 520, 'addHeight': 260, 'minHeight': 600},
  'zones':        {'addWidth': 72, 'addHeight': 232}
};
/* eslint-enable key-spacing */
