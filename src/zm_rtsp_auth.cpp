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

#include "zm.h"
#include "zm_utils.h"
#include "zm_rtsp_auth.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

namespace zm {

Authenticator::Authenticator( const std::string &username, const std::string &password) : 
 fCnonce("0a4f113b"),
 fUsername(username),
 fPassword(password)
  {
#ifdef HAVE_GCRYPT_H
  // Special initialisation for libgcrypt
  if ( !gcry_check_version(GCRYPT_VERSION) ) {
    Fatal("Unable to initialise libgcrypt");
  }
  gcry_control( GCRYCTL_DISABLE_SECMEM, 0 );
  gcry_control( GCRYCTL_INITIALIZATION_FINISHED, 0 );
#endif // HAVE_GCRYPT_H
  
  fAuthMethod = AUTH_UNDEFINED;
  nc = 1;
}

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

void Authenticator::authHandleHeader(std::string headerData) 
{
  const char* basic_match = "Basic ";
  const char* digest_match = "Digest ";
  size_t digest_match_len = strlen(digest_match);
  
  // Check if basic auth
  if ( strncasecmp(headerData.c_str(),basic_match,strlen(basic_match)) == 0 ) {
    fAuthMethod = AUTH_BASIC;
    Debug(2, "Set authMethod to Basic");
  } 
  // Check if digest auth
  else if (strncasecmp( headerData.c_str(),digest_match,digest_match_len ) == 0) {
    fAuthMethod = AUTH_DIGEST;
    Debug( 2, "Set authMethod to Digest");
    StringVector subparts = split(headerData.substr(digest_match_len, headerData.length() - digest_match_len), ",");
    // subparts are key="value"
    for ( size_t i = 0; i < subparts.size(); i++ ) {
      StringVector kvPair = split(trimSpaces(subparts[i]), "=");
      std::string key = trimSpaces(kvPair[0]);
      if ( key == "realm" ) {
        fRealm = trimSet(kvPair[1], "\"");
        continue;
      }
      if ( key == "nonce" ) {
        fNonce = trimSet(kvPair[1], "\"");
        continue;
      }
      if ( key == "qop" ) {
        fQop = trimSet(kvPair[1], "\"");
        continue;
      }
    }
    Debug(2, "Auth data completed. User: %s, realm: %s, nonce: %s, qop: %s",
				username().c_str(), fRealm.c_str(), fNonce.c_str(), fQop.c_str());
  }
}

std::string Authenticator::quote( const std::string &src ) {
  return replaceAll(replaceAll(src, "\\", "\\\\"), "\"", "\\\"");
}

std::string Authenticator::getAuthHeader(std::string method, std::string uri) {
  std::string result = "Authorization: ";
  if (fAuthMethod == AUTH_BASIC) {
    result += "Basic " + base64Encode( username() + ":" + password() );
  } else if (fAuthMethod == AUTH_DIGEST) {
    result += std::string("Digest ") + 
          "username=\"" + quote(username()) + "\", realm=\"" + quote(realm()) + "\", " +
          "nonce=\"" + quote(nonce()) + "\", uri=\"" + quote(uri) + "\"";
    if ( ! fQop.empty() ) {
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

std::string Authenticator::computeDigestResponse(std::string &method, std::string &uri) {
#if HAVE_DECL_MD5 || HAVE_DECL_GNUTLS_FINGERPRINT
  // The "response" field is computed as:
  //  md5(md5(<username>:<realm>:<password>):<nonce>:md5(<cmd>:<url>))
  size_t md5len = 16;
  unsigned char md5buf[md5len];
  char md5HexBuf[md5len*2+1];
  
  // Step 1: md5(<username>:<realm>:<password>)
  std::string ha1Data = username() + ":" + realm() + ":" + password();
  Debug( 2, "HA1 pre-md5: %s", ha1Data.c_str() );
#if HAVE_DECL_MD5
  MD5((unsigned char*)ha1Data.c_str(), ha1Data.length(), md5buf);
#elif HAVE_DECL_GNUTLS_FINGERPRINT
  gnutls_datum_t md5dataha1 = { (unsigned char*)ha1Data.c_str(), (unsigned int)ha1Data.length() };
  gnutls_fingerprint( GNUTLS_DIG_MD5, &md5dataha1, md5buf, &md5len );
#endif
  for ( unsigned int j = 0; j < md5len; j++ ) {
    sprintf(&md5HexBuf[2*j], "%02x", md5buf[j] );
  }
  md5HexBuf[md5len*2]='\0';
  std::string ha1Hash = md5HexBuf;
  
  // Step 2: md5(<cmd>:<url>)
  std::string ha2Data = method + ":" + uri;
  Debug( 2, "HA2 pre-md5: %s", ha2Data.c_str() );
#if HAVE_DECL_MD5
  MD5((unsigned char*)ha2Data.c_str(), ha2Data.length(), md5buf );
#elif HAVE_DECL_GNUTLS_FINGERPRINT
  gnutls_datum_t md5dataha2 = { (unsigned char*)ha2Data.c_str(), (unsigned int)ha2Data.length() };
  gnutls_fingerprint( GNUTLS_DIG_MD5, &md5dataha2, md5buf, &md5len );
#endif
  for ( unsigned int j = 0; j < md5len; j++ ) {
    sprintf( &md5HexBuf[2*j], "%02x", md5buf[j] );
  }
  md5HexBuf[md5len*2]='\0';
  std::string ha2Hash = md5HexBuf;

  // Step 3: md5(ha1:<nonce>:ha2)
  std::string digestData = ha1Hash + ":" + nonce();
  if ( ! fQop.empty() ) {
    digestData += ":" + stringtf("%08x", nc) + ":"+fCnonce + ":" + fQop;
    nc ++;
    // if qop was specified, then we have to include t and a cnonce and an nccount
  }
  digestData += ":" + ha2Hash;
  Debug( 2, "pre-md5: %s", digestData.c_str() );
#if HAVE_DECL_MD5
  MD5((unsigned char*)digestData.c_str(), digestData.length(), md5buf);
#elif HAVE_DECL_GNUTLS_FINGERPRINT
  gnutls_datum_t md5datadigest = { (unsigned char*)digestData.c_str(), (unsigned int)digestData.length() };
  gnutls_fingerprint( GNUTLS_DIG_MD5, &md5datadigest, md5buf, &md5len );
#endif
  for ( unsigned int j = 0; j < md5len; j++ ) {
    sprintf( &md5HexBuf[2*j], "%02x", md5buf[j] );
  }
  md5HexBuf[md5len*2]='\0';
   
  return md5HexBuf;
#else // HAVE_DECL_MD5
  Error("You need to build with gnutls or openssl installed to use digest authentication");
  return 0;
#endif // HAVE_DECL_MD5
}

void Authenticator::checkAuthResponse(std::string &response) {
  std::string authLine;
  StringVector lines = split(response, "\r\n");
  const char* authenticate_match = "WWW-Authenticate:";
  size_t authenticate_match_len = strlen(authenticate_match);

  for ( size_t i = 0; i < lines.size(); i++ ) {
    // stop at end of headers
    if (lines[i].length()==0)
      break;

    if (strncasecmp(lines[i].c_str(),authenticate_match,authenticate_match_len) == 0) {
      authLine = lines[i];
      Debug( 2, "Found auth line at %d", i);
      break;
    }
  }
  if (!authLine.empty()) {
    Debug( 2, "Analyze auth line %s", authLine.c_str());
    authHandleHeader( trimSpaces(authLine.substr(authenticate_match_len,authLine.length()-authenticate_match_len)) );
  } else {
    Debug( 2, "Didn't find auth line in %s", authLine.c_str());
  }
}

} // namespace zm
