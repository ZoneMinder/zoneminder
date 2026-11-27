//
// ZoneMinder Amcrest/Dahua event stream parsing
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

#ifndef ZM_AMCREST_PARSE_H
#define ZM_AMCREST_PARSE_H

#include <string>

namespace zm {
namespace amcrest {

// The message in one part of the eventManager.cgi stream: "Heartbeat", a
// "Code=...;action=...;index=..." line, or failing those the first body line.
std::string GetCommand(const std::string &content);

// Takes the next message off the front of the stream buffer. Returns false
// when the buffer does not hold a complete message yet; the rest is kept.
bool NextEvent(std::string &buffer, std::string &command);

}  // namespace amcrest
}  // namespace zm

#endif  // ZM_AMCREST_PARSE_H
