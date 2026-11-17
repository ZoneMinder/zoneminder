#include <algorithm>    // find

#include "zm_uri.h"

Uri::Uri(const std::string &uri) {
  if (uri.empty()) return;

  typedef std::string::const_iterator iterator_t;

  iterator_t uriEnd = uri.end();

  // get query start
  iterator_t queryStart = std::find(uri.begin(), uriEnd, '?');

  // protocol
  iterator_t protocolStart = uri.begin();
  iterator_t protocolEnd = std::find(protocolStart, uriEnd, ':');            //"://");

  if (protocolEnd != uriEnd) {
    std::string prot = &*(protocolEnd);
    if ((prot.length() > 3) && (prot.substr(0, 3) == "://")) {
      Protocol = std::string(protocolStart, protocolEnd);
      protocolEnd += 3;   //      ://
    } else {
      protocolEnd = uri.begin();  // no protocol
    }
  } else {
    protocolEnd = uri.begin();  // no protocol
  }

  // host
  iterator_t hostStart = protocolEnd;
  iterator_t pathStart = std::find(hostStart, queryStart, '/');  // a / in the query is not the path
  iterator_t authorityEnd = (pathStart != uriEnd) ? pathStart : queryStart;

  // skip user:password@
  for (iterator_t it = hostStart; it != authorityEnd; ++it) {
    if (*it == '@') hostStart = it + 1;
  }

  iterator_t hostEnd = std::find(hostStart, authorityEnd, ':');  // check for port

  Host = std::string(hostStart, hostEnd);

  // port
  if ((hostEnd != uriEnd) && ((&*(hostEnd))[0] == ':')) {
    // we have a port
    hostEnd++;
    iterator_t portEnd = (pathStart != uriEnd) ? pathStart : queryStart;
    Port = std::string(hostEnd, portEnd);
  }

  if (pathStart != uriEnd) Path = std::string(pathStart, queryStart);
  if (queryStart != uriEnd) QueryString = std::string(queryStart, uri.end());
}
