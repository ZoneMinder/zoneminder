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

#include "zm_catch2.h"

#include <memory>

#include "zm_comms.h"
#include <array>
#include <cstdlib>
#include <string>
#include <arpa/inet.h>
#include <fcntl.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/un.h>
#include <unistd.h>

namespace {

// InetSocket::bind() resolves with AF_UNSPEC, so the socket that actually got
// bound may be AF_INET6. sockaddr_storage is the only buffer guaranteed to be
// big enough for either, and the port sits at a different offset in each.
int getBoundPort(int sd) {
  sockaddr_storage addr = {};
  socklen_t addr_len = sizeof(addr);
  REQUIRE(::getsockname(sd, reinterpret_cast<sockaddr *>(&addr), &addr_len) == 0);

  int port = 0;
  if (addr.ss_family == AF_INET) {
    port = ntohs(reinterpret_cast<const sockaddr_in *>(&addr)->sin_port);
  } else if (addr.ss_family == AF_INET6) {
    port = ntohs(reinterpret_cast<const sockaddr_in6 *>(&addr)->sin6_port);
  } else {
    FAIL("unexpected address family " << addr.ss_family);
  }

  REQUIRE(port > 0);
  return port;
}

// mkstemp is what reserves the name, so the template has to be the socket path
// itself. Reserving zm.unittest.XXXXXX and then appending .sock would hand back
// a name nothing had claimed, which two concurrent runs could still collide on.
std::string makeUnixSocketPath() {
  char path_template[] = "/tmp/zm.unittest.XXXXXX.sock";
  const int fd = ::mkstemps(path_template, 5);
  REQUIRE(fd >= 0);
  REQUIRE(::close(fd) == 0);
  // bind() needs the path free, and this process holds the only claim on it.
  REQUIRE(::unlink(path_template) == 0);
  return std::string(path_template);
}

// A port nothing is listening on. Binding then closing leaves the number
// unused, which is far safer than hardcoding one and hoping the host agrees.
int getUnusedPort() {
  const int sd = ::socket(AF_INET, SOCK_STREAM, 0);
  REQUIRE(sd >= 0);

  sockaddr_in addr = {};
  addr.sin_family = AF_INET;
  addr.sin_addr.s_addr = htonl(INADDR_LOOPBACK);
  addr.sin_port = 0;
  REQUIRE(::bind(sd, reinterpret_cast<sockaddr *>(&addr), sizeof(addr)) == 0);

  const int port = getBoundPort(sd);
  REQUIRE(::close(sd) == 0);
  return port;
}

}  // namespace

TEST_CASE("ZM::Pipe basics") {
  zm::Pipe pipe;

  SECTION("setBlocking on non-opened") {
    REQUIRE(pipe.setBlocking(true) == false);
    REQUIRE(pipe.setBlocking(false) == false);
  }

  REQUIRE(pipe.open() == true);

  REQUIRE(pipe.isOpen() == true);
  REQUIRE(pipe.isClosed() == false);
  REQUIRE(pipe.getReadDesc() != -1);
  REQUIRE(pipe.getWriteDesc() != -1);

  SECTION("double open") {
    REQUIRE(pipe.open() == true); // is this expected?
  }

  SECTION("close") {
    REQUIRE(pipe.close() == true);

    REQUIRE(pipe.isOpen() == false);
    REQUIRE(pipe.isClosed() == true);
    REQUIRE(pipe.getReadDesc() == -1);
    REQUIRE(pipe.getWriteDesc() == -1);

    SECTION("double close") {
      REQUIRE(pipe.close() == true);
    }

    SECTION("setBlocking on closed") {
      REQUIRE(pipe.setBlocking(true) == false);
      REQUIRE(pipe.setBlocking(false) == false);
    }
  }

  SECTION("setBlocking") {
    REQUIRE(pipe.setBlocking(true) == true);
    REQUIRE(pipe.setBlocking(false) == true);
  }
}

TEST_CASE("ZM::Pipe read/write") {
  zm::Pipe pipe;

  std::array<char, 3> msg = {'a', 'b', 'c'};
  std::array<char, msg.size()> rcv{};

  SECTION("read/write on non-opened pipe") {
    REQUIRE(pipe.write(msg.data(), msg.size()) == -1);
    REQUIRE(pipe.read(rcv.data(), rcv.size()) == -1);
  }

  SECTION("read/write on opened pipe") {
    REQUIRE(pipe.open() == true);

    REQUIRE(pipe.write(msg.data(), msg.size()) == msg.size());
    REQUIRE(pipe.read(rcv.data(), rcv.size()) == msg.size());

    REQUIRE(rcv == msg);
  }

  SECTION("read/write on closed pipe") {
    REQUIRE(pipe.open() == true);
    REQUIRE(pipe.close() == true);

    REQUIRE(pipe.write(msg.data(), msg.size()) == -1);
    REQUIRE(pipe.read(rcv.data(), rcv.size()) == -1);
  }
}

TEST_CASE("ZM::SockAddrInet") {
  zm::SockAddrInet addr;
  REQUIRE(addr.getAddrSize() == sizeof(sockaddr_in));

  SECTION("resolve") {
    addr.resolve(80, "");
    REQUIRE(addr.getDomain() == AF_INET);

    SECTION("newSockAddr from resolved addr") {
      // newSockAddr hands ownership to the caller; Socket deletes the ones it
      // holds in its destructor. Held in a unique_ptr rather than deleted at
      // the end of the section because a failing REQUIRE throws past it.
      std::unique_ptr<zm::SockAddr> addr2(zm::SockAddr::newSockAddr(&addr));
      REQUIRE(addr2->getDomain() == AF_INET);
      REQUIRE(addr2->getAddrSize() == sizeof(sockaddr_in));
    }
  }
}

TEST_CASE("ZM::SockAddrUnix") {
  zm::SockAddrUnix addr;
  REQUIRE(addr.getAddrSize() == sizeof(sockaddr_un));

  SECTION("resovle") {
    addr.resolve("/", "");
    REQUIRE(addr.getDomain() == AF_UNIX);

    SECTION("newSockAddr from resolved addr") {
      // newSockAddr hands ownership to the caller; Socket deletes the ones it
      // holds in its destructor. Held in a unique_ptr rather than deleted at
      // the end of the section because a failing REQUIRE throws past it.
      std::unique_ptr<zm::SockAddr> addr2(zm::SockAddr::newSockAddr(&addr));
      REQUIRE(addr2->getDomain() == AF_UNIX);
      REQUIRE(addr2->getAddrSize() == sizeof(sockaddr_un));
    }
  }
}

TEST_CASE("ZM::SockAddrUnix refuses a path longer than sun_path") {
  // A path that does not fit in sun_path must be rejected, not truncated to a different file.
  std::string longPath = "/tmp/" + std::string(sizeof(sockaddr_un::sun_path), 'a');
  zm::SockAddrUnix addr;
  REQUIRE_FALSE(addr.resolve(longPath.c_str(), ""));

  // A path of exactly sizeof(sun_path)-1 characters still fits.
  std::string fitting = "/tmp/" + std::string(sizeof(sockaddr_un::sun_path) - 1 - 5, 'b');
  REQUIRE(fitting.size() == sizeof(sockaddr_un::sun_path) - 1);
  REQUIRE(addr.resolve(fitting.c_str(), ""));

  // bind on an over-long path must fail and must not create the truncated file.
  zm::TcpUnixServer server;
  REQUIRE_FALSE(server.bind(longPath.c_str()));
  std::string truncated = longPath.substr(0, sizeof(sockaddr_un::sun_path) - 1);
  struct stat st;
  bool exists = ::stat(truncated.c_str(), &st) == 0;
  if (exists) ::unlink(truncated.c_str());
  REQUIRE_FALSE(exists);
}

TEST_CASE("ZM::UdpInetSocket basics") {
  zm::UdpInetSocket socket;
  REQUIRE(socket.isClosed() == true);
  REQUIRE(socket.isOpen() == false);
  REQUIRE(socket.isConnected() == false);
  REQUIRE(socket.isDisconnected() == false);

  SECTION("bind with host and port") {
    REQUIRE(socket.bind(nullptr, "0") == true);
    REQUIRE(socket.isOpen() == true);
    REQUIRE(socket.isDisconnected() == true);
    REQUIRE(socket.isClosed() == false);
    REQUIRE(socket.isConnected() == false);

    SECTION("close") {
      REQUIRE(socket.close() == true);
      REQUIRE(socket.isClosed() == true);
      REQUIRE(socket.isOpen() == false);
      REQUIRE(socket.isConnected() == false);
      REQUIRE(socket.isDisconnected() == false);
    }
  }

  SECTION("bind with port") {
    REQUIRE(socket.bind("0") == true);
  }

  SECTION("bind with host and port number") {
    REQUIRE(socket.bind(nullptr, 0) == true);
  }

  SECTION("bind with port number") {
    REQUIRE(socket.bind(0) == true);
  }
}

TEST_CASE("ZM::UdpInetSocket send/recv") {
  zm::UdpInetSocket srv_socket;
  zm::UdpInetSocket client_socket;

  std::array<char, 3> msg = {'a', 'b', 'c'};
  std::array<char, msg.size()> rcv{};

  SECTION("send/recv on unbound socket") {
    REQUIRE(client_socket.send(msg.data(), msg.size()) == -1);
    REQUIRE(srv_socket.recv(rcv.data(), rcv.size()) == -1);
  }

  SECTION("send/recv") {
    REQUIRE(srv_socket.bind("127.0.0.1", "0") == true);
    REQUIRE(srv_socket.isOpen() == true);
    const int port = getBoundPort(srv_socket.getReadDesc());
    const std::string port_str = std::to_string(port);

    REQUIRE(client_socket.connect("127.0.0.1", port_str.c_str()) == true);
    REQUIRE(client_socket.isConnected() == true);

    REQUIRE(client_socket.send(msg.data(), msg.size()) == msg.size());
    REQUIRE(srv_socket.recv(rcv.data(), rcv.size()) == msg.size());

    REQUIRE(rcv == msg);
  }
}

TEST_CASE("ZM::UdpUnixSocket basics") {
  const std::string sock_path = makeUnixSocketPath();

  zm::UdpUnixSocket socket;
  REQUIRE(socket.isClosed() == true);
  REQUIRE(socket.isOpen() == false);
  REQUIRE(socket.isConnected() == false);
  REQUIRE(socket.isDisconnected() == false);

  SECTION("bind") {
    REQUIRE(socket.bind(sock_path.c_str()) == true);
    REQUIRE(socket.isOpen() == true);
    REQUIRE(socket.isDisconnected() == true);
    REQUIRE(socket.isClosed() == false);
    REQUIRE(socket.isConnected() == false);

    SECTION("close") {
      REQUIRE(socket.close() == true);
      REQUIRE(socket.isClosed() == true);
      REQUIRE(socket.isOpen() == false);
      REQUIRE(socket.isConnected() == false);
      REQUIRE(socket.isDisconnected() == false);
    }
  }

  SECTION("connect to unbound socket") {
    REQUIRE(socket.connect(sock_path.c_str()) == false);
  }
}

TEST_CASE("ZM::UdpUnixSocket send/recv") {
  const std::string sock_path = makeUnixSocketPath();

  zm::UdpUnixSocket srv_socket;
  zm::UdpUnixSocket client_socket;

  SECTION("send/recv byte buffer") {
    std::array<char, 3> msg = {'a', 'b', 'c'};
    std::array<char, msg.size()> rcv{};

    SECTION("on unbound socket") {
      REQUIRE(client_socket.send(msg.data(), msg.size()) == -1);
      REQUIRE(srv_socket.recv(rcv.data(), rcv.size()) == -1);
    }

    SECTION("on bound socket") {
      REQUIRE(srv_socket.bind(sock_path.c_str()) == true);
      REQUIRE(srv_socket.isOpen() == true);

      REQUIRE(client_socket.connect(sock_path.c_str()) == true);
      REQUIRE(client_socket.isConnected() == true);

      REQUIRE(client_socket.send(msg.data(), msg.size()) == msg.size());
      REQUIRE(srv_socket.recv(rcv.data(), rcv.size()) == msg.size());

      REQUIRE(rcv == msg);
    }
  }

  SECTION("send/recv string") {
    std::string msg = "abc";
    std::string rcv;
    rcv.reserve(msg.length());

    REQUIRE(srv_socket.bind(sock_path.c_str()) == true);
    REQUIRE(srv_socket.isOpen() == true);

    REQUIRE(client_socket.connect(sock_path.c_str()) == true);
    REQUIRE(client_socket.isConnected() == true);

    REQUIRE(client_socket.send(msg) == static_cast<ssize_t>(msg.size()));
    REQUIRE(srv_socket.recv(rcv) == static_cast<ssize_t>(msg.size()));

    REQUIRE(rcv == msg);
  }
}

TEST_CASE("ZM::TcpInetClient basics") {
  zm::TcpInetClient client;
  REQUIRE(client.isClosed() == true);
  REQUIRE(client.isOpen() == false);
  REQUIRE(client.isConnected() == false);
  REQUIRE(client.isDisconnected() == false);

  REQUIRE(client.connect("127.0.0.1", getUnusedPort()) == false);
  REQUIRE(client.isClosed() == true);
  REQUIRE(client.isOpen() == false);
  REQUIRE(client.isConnected() == false);
  REQUIRE(client.isDisconnected() == false);
  // The socket it tried is closed, so it must not keep the number: the
  // destructor would close it again, and by then it may belong to a log file.
  REQUIRE(client.getDesc() == -1);
}

TEST_CASE("ZM::TcpInetServer basics", "[notCI]") {
  zm::TcpInetServer server;
  REQUIRE(server.isClosed() == true);
  REQUIRE(server.isOpen() == false);
  REQUIRE(server.isConnected() == false);
  REQUIRE(server.isDisconnected() == false);

  REQUIRE(server.bind(0) == true);
  REQUIRE(server.isOpen() == true);
  REQUIRE(server.isClosed() == false);
  REQUIRE(server.isConnected() == false);
  REQUIRE(server.isDisconnected() == true);
  REQUIRE(server.isListening() == false);

  REQUIRE(server.listen() == true);
  REQUIRE(server.isListening() == true);

  SECTION("close") {
    REQUIRE(server.close() == true);
    REQUIRE(server.isClosed() == true);
    REQUIRE(server.isOpen() == false);
    REQUIRE(server.isConnected() == false);
    REQUIRE(server.isDisconnected() == false);
  }
}

TEST_CASE("ZM::TcpInetClient/Server send/recv", "[notCI]") {
  zm::TcpInetServer server;
  zm::TcpInetClient client;

  std::array<char, 3> msg = {'a', 'b', 'c'};
  std::array<char, msg.size()> rcv{};

  SECTION("send/recv on unbound socket") {
    REQUIRE(client.send(msg.data(), msg.size()) == -1);
    REQUIRE(server.recv(rcv.data(), rcv.size()) == -1);
  }

  SECTION("send/recv") {
    REQUIRE(server.bind(0) == true);
    REQUIRE(server.isOpen() == true);
    const int port = getBoundPort(server.getReadDesc());
    REQUIRE(server.listen() == true);

    REQUIRE(client.connect("127.0.0.1", port) == true);
    REQUIRE(client.isConnected() == true);

    REQUIRE(server.accept() == true);

    REQUIRE(client.send(msg.data(), msg.size()) == msg.size());
    REQUIRE(server.recv(rcv.data(), rcv.size()) == msg.size());

    REQUIRE(rcv == msg);
  }
}

TEST_CASE("ZM::Select deleteReader") {
  zm::Pipe pipe_a;
  zm::Pipe pipe_b;

  REQUIRE(pipe_a.open() == true);
  REQUIRE(pipe_b.open() == true);

  zm::Select select;
  select.setTimeout(Microseconds(1000000));
  REQUIRE(select.addReader(&pipe_a) == true);
  REQUIRE(select.addReader(&pipe_b) == true);

  const char byte = 'x';
  REQUIRE(pipe_b.write(&byte, 1) == 1);

  SECTION("delete closed reader") {
    REQUIRE(pipe_a.close() == true);
    REQUIRE(pipe_a.isOpen() == false);
    REQUIRE(pipe_a.getReadDesc() == -1);

    REQUIRE(select.deleteReader(&pipe_a) == true);

    REQUIRE(select.wait() == 1);

    const zm::Select::CommsList &readable = select.getReadable();
    REQUIRE(readable.size() == 1);
    REQUIRE(readable[0] == &pipe_b);

    SECTION("delete open reader with highest fd") {
      REQUIRE(pipe_a.getReadDesc() == -1);
      REQUIRE(select.deleteReader(&pipe_b) == true);

      REQUIRE(select.wait() == 0);
      REQUIRE(select.getReadable().empty());
    }
  }
}

// accept() on a listener whose queue is empty must fail without tearing the
// listener down: a non-blocking descriptor makes ::accept() return EAGAIN,
// which is one of the transient errors that leave the socket usable.
TEST_CASE("ZM::TcpUnixServer accept transient failure", "[zm_comms_accept]") {
  const std::string sock_path = makeUnixSocketPath();

  zm::TcpUnixServer server;
  REQUIRE(server.bind(sock_path.c_str()) == true);
  REQUIRE(server.listen() == true);
  REQUIRE(server.isListening() == true);

  const int listen_sd = server.getReadDesc();
  REQUIRE(listen_sd >= 0);
  REQUIRE(::fcntl(listen_sd, F_SETFL, ::fcntl(listen_sd, F_GETFL) | O_NONBLOCK) != -1);

  const auto listenerStillOpen = [&]() {
    REQUIRE(server.isOpen() == true);
    REQUIRE(server.isListening() == true);
    REQUIRE(server.getReadDesc() == listen_sd);
    // The state flags could lie about the descriptor, the kernel cannot.
    REQUIRE(::fcntl(listen_sd, F_GETFD) != -1);
  };

  SECTION("accept() with no pending connection") {
    REQUIRE(server.accept() == false);
    listenerStillOpen();
  }

  SECTION("accept(newSocket) with no pending connection") {
    zm::TcpUnixSocket *newSocket = reinterpret_cast<zm::TcpUnixSocket *>(1);
    REQUIRE(server.accept(newSocket) == false);
    REQUIRE(newSocket == nullptr);
    listenerStillOpen();
  }

  SECTION("listener still usable after transient failure") {
    zm::TcpUnixSocket *newSocket = nullptr;
    REQUIRE(server.accept(newSocket) == false);

    zm::TcpUnixClient client;
    REQUIRE(client.connect(sock_path.c_str()) == true);
    REQUIRE(client.isConnected() == true);

    // The accepted socket is owned by the caller; held in a unique_ptr so a
    // failing REQUIRE does not leak it.
    zm::TcpUnixSocket *raw = nullptr;
    REQUIRE(server.accept(raw) == true);
    std::unique_ptr<zm::TcpUnixSocket> accepted(raw);

    REQUIRE(accepted != nullptr);
    REQUIRE(accepted->isConnected() == true);
    REQUIRE(server.isListening() == true);
  }
}
