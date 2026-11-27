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
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_fifo_debug.h"

#include <cstdarg>
#include <cstring>
#include <string>
#include <vector>

// A debug message of 8192 bytes or more made the fifo debug writer advance past its buffer.
static size_t format_line(char *buf, size_t size, const char *fmt, ...) {
  va_list ap;
  va_start(ap, fmt);
  size_t n = zmFifoDbgFormat(buf, size, 0, fmt, ap);
  va_end(ap);
  return n;
}

static size_t format_hex(char *buf, size_t size, unsigned char *data, int len) {
  // zmFifoDbgFormat reads the data pointer and length from the va_list when hex is set.
  struct Wrap {
    static size_t call(char *b, size_t s, int hex, const char *f, ...) {
      va_list ap;
      va_start(ap, f);
      size_t n = zmFifoDbgFormat(b, s, hex, f, ap);
      va_end(ap);
      return n;
    }
  };
  return Wrap::call(buf, size, 1, "", data, len);
}

TEST_CASE("fifo debug line fits and keeps its newline", "[fifo_debug]") {
  std::vector<char> buf(8192 + 16, 'X');
  const size_t size = 8192;

  SECTION("short message") {
    size_t n = format_line(buf.data(), size, "hello %d", 5);
    REQUIRE(n == 8);
    REQUIRE(std::string(buf.data(), n) == "hello 5\n");
  }

  SECTION("message longer than the buffer is clamped") {
    std::string big(20000, 'a');
    size_t n = format_line(buf.data(), size, "%s", big.c_str());
    REQUIRE(n <= size - 1);
    REQUIRE(buf[n - 1] == '\n');
    REQUIRE(buf[n] == '\0');
    for (size_t i = size; i < buf.size(); i++) REQUIRE(buf[i] == 'X');
  }

  SECTION("message of exactly buffer size is clamped") {
    std::string big(size, 'a');
    size_t n = format_line(buf.data(), size, "%s", big.c_str());
    REQUIRE(n <= size - 1);
    REQUIRE(buf[n - 1] == '\n');
    for (size_t i = size; i < buf.size(); i++) REQUIRE(buf[i] == 'X');
  }

  SECTION("hex dump longer than the buffer is clamped") {
    std::vector<unsigned char> data(5000, 0xab);
    size_t n = format_hex(buf.data(), size, data.data(), static_cast<int>(data.size()));
    REQUIRE(n <= size - 1);
    REQUIRE(buf[n - 1] == '\n');
    for (size_t i = size; i < buf.size(); i++) REQUIRE(buf[i] == 'X');
  }
}
