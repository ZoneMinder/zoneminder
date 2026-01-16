#ifndef ZM_RTSP_SERVER_AUTHENTICATOR_H
#define ZM_RTSP_SERVER_AUTHENTICATOR_H

#include "zm_config.h"
#include "zm_user.h"

#include <string>
#include <sstream>

#include "xop/Authenticator.h"
#include "xop/RtspMessage.h"

#if HAVE_RTSP_SERVER

class ZM_RtspServer_Authenticator : public xop::Authenticator {
 public:
  ZM_RtspServer_Authenticator() {};
  ~ZM_RtspServer_Authenticator() {};

  bool Authenticate(std::shared_ptr<xop::RtspRequest> request) {

    if (!config.opt_use_auth) {
      Debug(1, "Not doing auth");
      return true;
    }
    std::string url = request->GetRtspUrl();
    Debug(1, "Doing auth %s", url.c_str());

    User *user = nullptr;

    size_t found = url.find("?");
    if (found == std::string::npos) return false;

    std::string queryString = url.substr(found+1, std::string::npos);

#if 0
    found = suffix_string.find("/");
    if ( found != std::string::npos )
      suffix_string = suffix_string.substr(0, found);
#endif

    Debug(1, "suffix %s", queryString.c_str());
    std::istringstream requestStream(queryString);
    QueryString query(requestStream);

    if (query.has("jwt_token")) {
      const QueryParameter *jwt_token = query.get("jwt_token");
      user = zmLoadTokenUser(jwt_token->firstValue(), false);
    } else if (query.has("token")) {
      const QueryParameter *jwt_token = query.get("token");
      user = zmLoadTokenUser(jwt_token->firstValue(), false);
    } else if (strcmp(config.auth_relay, "none") == 0) {
      if (query.has("username")) {
        std::string username = query.get("username")->firstValue();
        if (checkUser(username.c_str())) {
          user = zmLoadUser(username.c_str());
        } else {
          Debug(1, "Bad username %s", username.c_str());
        }
      }
    } else {
      if (query.has("auth")) {
        std::string auth_hash = query.get("auth")->firstValue();
        if (!auth_hash.empty()) {
          std::string username = query.has("username") ? query.get("username")->firstValue() : "";
          user = zmLoadAuthUser(auth_hash, username, config.auth_hash_ips);
        }
      }
      Debug(1, "Query has username ? %d", query.has("username"));
      if ((!user) and query.has("username") and query.has("password")) {
        std::string username = query.get("username")->firstValue();
        std::string password = query.get("password")->firstValue();
        Debug(1, "username %s password %s", username.c_str(), password.c_str());
        user = zmLoadUser(username.c_str(), password.c_str());
      }
    }  // end if query string

    if (user) {
      Debug(1, "Authenticated");
      delete user;
      return true;
    }
    return false;
  }

  size_t GetFailedResponse(
    std::shared_ptr<xop::RtspRequest> request,
    std::shared_ptr<char> buf,
    size_t size) {
    return request->BuildUnauthorizedRes(buf.get(), size);
  }

};  // end class ZM_RtspServer_Authenticator

#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_AUTHENTICATOR_H
