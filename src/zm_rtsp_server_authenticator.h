#ifndef ZM_RTSP_SERVER_AUTHENTICATOR_H
#define ZM_RTSP_SERVER_AUTHENTICATOR_H

#include "zm_config.h"
#include "zm_logger.h"
#include "zm_user.h"
#include "zm_utils.h"

#include <map>
#include <mutex>
#include <string>
#include <sstream>

#include "xop/Authenticator.h"
#include "xop/RtspMessage.h"

#if HAVE_RTSP_SERVER

class ZM_RtspServer_Authenticator : public xop::Authenticator {
 public:
  ZM_RtspServer_Authenticator() {};
  ~ZM_RtspServer_Authenticator() {};

  // The stream names being served and the monitor behind each, so a request can be
  // checked against the monitor it asks for. Replaced as monitors come and go.
  void SetStreams(const std::map<std::string, unsigned int> &streams) {
    std::lock_guard<std::mutex> lock(mutex_);
    streams_ = streams;
  }

  // The monitor served under the stream name in an rtsp url, 0 when there is none.
  // The name is the path before the query; a longer path (a track of the stream)
  // belongs to the longest name that is a prefix of it up to a '/'.
  unsigned int MonitorIdForUrl(const std::string &url) {
    std::string path = url.substr(0, url.find('?'));
    size_t scheme = path.find("://");
    if (scheme != std::string::npos) path = path.substr(scheme + 3);
    size_t slash = path.find('/');
    path = (slash == std::string::npos) ? "" : path.substr(slash + 1);

    std::lock_guard<std::mutex> lock(mutex_);
    unsigned int found_id = 0;
    size_t found_len = 0;
    for (const auto &stream : streams_) {
      const std::string &name = stream.first;
      if (name.empty() or name.size() < found_len) continue;
      if (path.compare(0, name.size(), name) != 0) continue;
      if (path.size() > name.size() and path[name.size()] != '/') continue;
      found_id = stream.second;
      found_len = name.size();
    }
    return found_id;
  }

  // The same rule as zms for a live view: the Stream permission and the monitor.
  static bool UserMayStream(User *user, int monitor_id) {
    if (user->getStream() < User::PERM_VIEW) {
      Warning("User %s may not view live streams", user->getUsername());
      return false;
    }
    if (!user->canAccess(monitor_id)) {
      Warning("User %s may not view monitor %d", user->getUsername(), monitor_id);
      return false;
    }
    return true;
  }

  bool Authenticate(std::shared_ptr<xop::RtspRequest> request) {

    if (!config.opt_use_auth) {
      Debug(1, "Not doing auth");
      return true;
    }
    std::string url = request->GetRtspUrl();

    User *user = nullptr;

    size_t found = url.find("?");
    // The query string carries the credentials (password, auth hash, token),
    // so only the part before it is logged.
    Debug(1, "Doing auth %s", url.substr(0, found).c_str());
    if (found == std::string::npos) return false;

    std::string queryString = url.substr(found+1, std::string::npos);

#if 0
    found = suffix_string.find("/");
    if ( found != std::string::npos )
      suffix_string = suffix_string.substr(0, found);
#endif

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
        Debug(1, "Checking password for username %s", username.c_str());
        user = zmLoadUser(username.c_str(), password.c_str());
      }
    }  // end if query string

    if (user) {
      Debug(1, "Authenticated");
      unsigned int monitor_id = MonitorIdForUrl(url);
      bool allowed = monitor_id and UserMayStream(user, monitor_id);
      delete user;
      return allowed;
    }
    return false;
  }

  size_t GetFailedResponse(
    std::shared_ptr<xop::RtspRequest> request,
    std::shared_ptr<char> buf,
    size_t size) {
    return request->BuildUnauthorizedRes(buf.get(), size);
  }


 private:
  std::mutex mutex_;
  std::map<std::string, unsigned int> streams_;
};  // end class ZM_RtspServer_Authenticator

#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_AUTHENTICATOR_H
