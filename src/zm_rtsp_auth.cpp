//
// ZoneMinder RTSP Authentication Class Implementation, $Date$, $Revision$
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

#include "zm_rtsp_auth.h"

#include "zm_crypt.h"
#include "zm_logger.h"
#include "zm_utils.h"
#include <cstring>
#include <utility>

namespace zm {

Authenticator::Authenticator(const std::string &username, const std::string &password)
  : fAuthMethod(AUTH_UNDEFINED),
    fCnonce("0a4f113b"),
    fUsername(std::move(username)),
    fPassword(std::move(password)),
    nc(1) {}

Authenticator::~Authenticator() {
  reset();
}

void Authenticator::reset() {
  fRealm.clear();
  fNonce.clear();
  fUsername.clear();
  fPassword.clear();
  fAuthMethod = AUTH_UNDEFINED;
}

void Authenticator::authHandleHeader(const std::string &headerData) {
  const char* basic_match = "Basic ";
  const char* digest_match = "Digest ";
  size_t digest_match_len = strlen(digest_match);

  // Check if basic auth
  if ( strncasecmp(headerData.c_str(), basic_match, strlen(basic_match)) == 0 ) {
    fAuthMethod = AUTH_BASIC;
    Debug(2, "Set authMethod to Basic");
  }
  // Check if digest auth
  else if ( strncasecmp(headerData.c_str(), digest_match, digest_match_len) == 0) {
    fAuthMethod = AUTH_DIGEST;
    Debug(2, "Set authMethod to Digest");
    StringVector subparts = Split(headerData.substr(digest_match_len, headerData.length() - digest_match_len), ",");
    // subparts are key="value"
    for ( size_t i = 0; i < subparts.size(); i++ ) {
      StringVector kvPair = Split(TrimSpaces(subparts[i]), "=");
      std::string key = TrimSpaces(kvPair[0]);
      if ( key == "realm" ) {
        fRealm = Trim(kvPair[1], "\"");
        continue;
      }
      if ( key == "nonce" ) {
        fNonce = Trim(kvPair[1], "\"");
        continue;
      }
      if ( key == "qop" ) {
        fQop = Trim(kvPair[1], "\"");
        continue;
      }
    }
    Debug(2, "Auth data completed. User: %s, realm: %s, nonce: %s, qop: %s",
          username().c_str(), fRealm.c_str(), fNonce.c_str(), fQop.c_str());
  }
}  // end void Authenticator::authHandleHeader(std::string headerData)

std::string Authenticator::quote( const std::string &src ) {
  return ReplaceAll(ReplaceAll(src, "\\", "\\\\"), "\"", "\\\"");
}

std::string Authenticator::getAuthHeader(const std::string &method, const std::string &uri) {
  std::string result = "Authorization: ";
  if ( fAuthMethod == AUTH_BASIC ) {
    result += "Basic " + Base64Encode(username() + ":" + password());
  } else if ( fAuthMethod == AUTH_DIGEST ) {
    result += std::string("Digest ") +
              "username=\"" + quote(username()) + "\", realm=\"" + quote(realm()) + "\", " +
              "nonce=\"" + quote(nonce()) + "\", uri=\"" + quote(uri) + "\"";
    if ( !fQop.empty() ) {
      result += ", qop=" + fQop;
      result += ", nc=" + stringtf("%08x",nc);
      result += ", cnonce=\"" + fCnonce + "\"";
    }
    result += ", response=\"" + computeDigestResponse(method, uri) + "\"";
    result += ", algorithm=\"MD5\"";

    //Authorization: Digest username="zm",
    //            realm="NC-336PW-HD-1080P",
    //            nonce="de8859d97609a6fcc16eaba490dcfd80",
    //            uri="rtsp://10.192.16.8:554/live/0/h264.sdp",
    //            response="4092120557d3099a163bd51a0d59744d",
    //            algorithm=MD5,
    //            opaque="5ccc069c403ebaf9f0171e9517f40e41",
    //            qop="auth",
    //            cnonce="c8051140765877dc",
    //            nc=00000001

  }
  result += "\r\n";
  return result;
}

std::string Authenticator::computeDigestResponse(const std::string &method, const std::string &uri) {
  // The "response" field is computed as:
  //  md5(md5(<username>:<realm>:<password>):<nonce>:md5(<cmd>:<url>))

  // Step 1: md5(<username>:<realm>:<password>)
  std::string ha1Data = username() + ":" + realm() + ":" + password();
  Debug(2, "HA1 pre-md5: %s", ha1Data.c_str());

  zm::crypto::MD5::Digest md5_digest = zm::crypto::MD5::GetDigestOf(ha1Data);
  std::string ha1Hash = ByteArrayToHexString(md5_digest);

  // Step 2: md5(<cmd>:<url>)
  std::string ha2Data = method + ":" + uri;
  Debug(2, "HA2 pre-md5: %s", ha2Data.c_str());

  md5_digest = zm::crypto::MD5::GetDigestOf(ha2Data);
  std::string ha2Hash = ByteArrayToHexString(md5_digest);

  // Step 3: md5(ha1:<nonce>:ha2)
  std::string digestData = ha1Hash + ":" + nonce();
  if (!fQop.empty()) {
    digestData += ":" + stringtf("%08x", nc) + ":" + fCnonce + ":" + fQop;
    nc++;
    // if qop was specified, then we have to include t and a cnonce and an nccount
  }
  digestData += ":" + ha2Hash;
  Debug(2, "pre-md5: %s", digestData.c_str());

  md5_digest = zm::crypto::MD5::GetDigestOf(digestData);

  return ByteArrayToHexString(md5_digest);
}

void Authenticator::checkAuthResponse(const std::string &response) {
  std::string authLine;
  StringVector lines = Split(response, "\r\n");
  const char* authenticate_match = "WWW-Authenticate:";
  size_t authenticate_match_len = strlen(authenticate_match);

  for ( size_t i = 0; i < lines.size(); i++ ) {
    // stop at end of headers
    if ( lines[i].length() == 0 )
      break;

    if ( strncasecmp(lines[i].c_str(), authenticate_match, authenticate_match_len) == 0 ) {
      authLine = lines[i];
      Debug(2, "Found auth line at %zu:", i);
      //break;
    }
  }
  if ( !authLine.empty() ) {
    Debug(2, "Analyze auth line %s", authLine.c_str());
    authHandleHeader(TrimSpaces(authLine.substr(authenticate_match_len, authLine.length() - authenticate_match_len)));
  } else {
    Debug(2, "Didn't find auth line in %s", authLine.c_str());
  }
}  // end void Authenticator::checkAuthResponse(std::string &response)

} // namespace zm
