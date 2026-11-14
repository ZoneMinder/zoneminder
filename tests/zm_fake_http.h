/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// A minimal HTTP server on a loopback port that records every request, for
// tests of the code that talks to go2rtc, Janus and RTSP2Web.

#ifndef ZM_TESTS_FAKE_HTTP_H
#define ZM_TESTS_FAKE_HTTP_H

#include <arpa/inet.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <unistd.h>

#include <atomic>
#include <functional>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

class FakeHttpServer {
 public:
  struct Request {
    std::string method;
    std::string target;
    std::string body;
  };
  using Handler = std::function<std::string(const Request &)>;

  explicit FakeHttpServer(Handler handler) : handler_(std::move(handler)) {
    listen_fd_ = socket(AF_INET, SOCK_STREAM, 0);
    int one = 1;
    setsockopt(listen_fd_, SOL_SOCKET, SO_REUSEADDR, &one, sizeof(one));
    sockaddr_in addr = {};
    addr.sin_family = AF_INET;
    addr.sin_addr.s_addr = htonl(INADDR_LOOPBACK);
    addr.sin_port = 0;
    bind(listen_fd_, reinterpret_cast<sockaddr *>(&addr), sizeof(addr));
    socklen_t len = sizeof(addr);
    getsockname(listen_fd_, reinterpret_cast<sockaddr *>(&addr), &len);
    port_ = ntohs(addr.sin_port);
    listen(listen_fd_, 16);
    thread_ = std::thread([this] { Run(); });
  }

  ~FakeHttpServer() {
    stop_ = true;
    shutdown(listen_fd_, SHUT_RDWR);
    close(listen_fd_);
    thread_.join();
  }

  int Port() const { return port_; }
  std::string Url() const { return "127.0.0.1:" + std::to_string(port_); }

  std::vector<Request> Requests() {
    std::lock_guard<std::mutex> lock(mutex_);
    return requests_;
  }

  size_t Count(const std::string &method, const std::string &target_prefix) {
    size_t n = 0;
    for (const Request &r : Requests()) {
      if (r.method == method && r.target.compare(0, target_prefix.size(), target_prefix) == 0) n++;
    }
    return n;
  }

 private:
  void Run() {
    while (!stop_) {
      int fd = accept(listen_fd_, nullptr, nullptr);
      if (fd < 0) break;
      timeval tv = {5, 0};
      setsockopt(fd, SOL_SOCKET, SO_RCVTIMEO, &tv, sizeof(tv));
      Serve(fd);
      close(fd);
    }
  }

  void Serve(int fd) {
    std::string data;
    size_t header_end = std::string::npos;
    char buf[4096];
    while ((header_end = data.find("\r\n\r\n")) == std::string::npos) {
      ssize_t n = recv(fd, buf, sizeof(buf), 0);
      if (n <= 0) return;
      data.append(buf, n);
    }
    Request req;
    size_t sp1 = data.find(' ');
    size_t sp2 = data.find(' ', sp1 + 1);
    req.method = data.substr(0, sp1);
    req.target = data.substr(sp1 + 1, sp2 - sp1 - 1);

    size_t content_length = 0;
    std::string headers = data.substr(0, header_end);
    for (char &c : headers) c = tolower(c);
    size_t cl = headers.find("content-length:");
    if (cl != std::string::npos) content_length = strtoul(headers.c_str() + cl + 15, nullptr, 10);
    req.body = data.substr(header_end + 4);
    while (req.body.size() < content_length) {
      ssize_t n = recv(fd, buf, sizeof(buf), 0);
      if (n <= 0) break;
      req.body.append(buf, n);
    }

    std::string reply = handler_(req);
    {
      std::lock_guard<std::mutex> lock(mutex_);
      requests_.push_back(req);
    }
    std::string response = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: " +
                           std::to_string(reply.size()) + "\r\nConnection: close\r\n\r\n" + reply;
    send(fd, response.data(), response.size(), MSG_NOSIGNAL);
  }

  Handler handler_;
  int listen_fd_;
  int port_;
  std::atomic<bool> stop_{false};
  std::thread thread_;
  std::mutex mutex_;
  std::vector<Request> requests_;
};

#endif  // ZM_TESTS_FAKE_HTTP_H
