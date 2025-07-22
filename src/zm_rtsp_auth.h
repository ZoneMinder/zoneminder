//
// ZoneMinder RTSP Authentication Class Interface, $Date$, $Revision$
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

#ifndef ZM_RTSP_AUTH_H
#define ZM_RTSP_AUTH_H

#include "zm_config.h"
#include <string>

namespace zm {

enum AuthMethod { AUTH_UNDEFINED = 0, AUTH_BASIC = 1, AUTH_DIGEST = 2 };
class Authenticator {
 public:
  Authenticator(const std::string &username, const std::string &password);
  virtual ~Authenticator();
  void reset();

  std::string realm() { return fRealm; }
  std::string nonce() { return fNonce; }
  std::string username() { return fUsername; }
  AuthMethod  auth_method() const { return fAuthMethod; }

  std::string computeDigestResponse(const std::string &cmd, const std::string &url);
  void authHandleHeader(const std::string &headerData);
  std::string getAuthHeader(const std::string &method, const std::string &path);
  void checkAuthResponse(const std::string &response);

 private:
  std::string password() { return fPassword; }
  AuthMethod fAuthMethod;
  std::string fRealm;
  std::string fNonce;
  std::string fCnonce;
  std::string fQop;
  std::string fUsername;
  std::string fPassword;
  std::string quote( const std::string &src );
  int nc;
};

} // namespace zm

#endif // ZM_RTSP_AUTH_H
