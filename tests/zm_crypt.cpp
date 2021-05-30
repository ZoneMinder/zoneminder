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

#include "zm_crypt.h"

TEST_CASE("JWT validation") {
  std::string key = "testsecret";

  SECTION("Valid token") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoiYWNjZXNzIiwiaWF0IjoxMjM0fQ.94WPmBAVl_83KCI9B3Jq9sNpoOdi0Hm1dR4sc6MCPUA";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "johndoe");
    REQUIRE(result.second == 1234);
  }

  SECTION("Invalid signature") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoiYWNjZXNzIiwiaWF0IjoxMjM0fQ.DhviT6RkDLmbXh5F9zM4l0VbWNPCuKptF6fORv1lBlA";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Missing user claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidHlwZSI6ImFjY2VzcyIsImlhdCI6MTIzNH0.mfi3ZHnqUAPUh5ECxDIkAM9WW9a8HbKrP73LC3yYJmw";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Missing type claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJpYXQiOjEyMzR9.D4Irs1gHfzO4psRY2xsOdClTg-Sp1kM__mmfNLs7CII";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Wrong type claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoid3JvbmciLCJpYXQiOjEyMzR9.I1Gd50J6mck05vzc_kzjaH4RNjLBaFGpOnie6-PbX28";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }

  SECTION("Missing iat claim") {
    std::string token =
        "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJab25lTWluZGVyIiwidXNlciI6ImpvaG5kb2UiLCJ0eXBlIjoid3JvbmcifQ.8iUFOUKJAK5vU8JWKm8D0EOEhm1rJoIulCO11O_Tsp0";
    std::pair<std::string, unsigned int> result = verifyToken(token, key);

    REQUIRE(result.first == "");
    REQUIRE(result.second == 0);
  }
}

TEST_CASE("zm::crypto::MD5") {
  using namespace zm::crypto;
  MD5 md5;

  REQUIRE(md5.GetDigest() == MD5::Digest());

  SECTION("hash from const char*") {
    md5.UpdateData("abcdefghijklmnopqrstuvwxyz");
    md5.Finalize();

    REQUIRE(md5.GetDigest() == MD5::Digest{0xc3, 0xfc, 0xd3, 0xd7, 0x61, 0x92, 0xe4, 0x00, 0x7d, 0xfb, 0x49, 0x6c, 0xca,
                                           0x67, 0xe1, 0x3b});
  }

  SECTION("hash from std::string") {
    md5.UpdateData(std::string("abcdefghijklmnopqrstuvwxyz"));
    md5.Finalize();

    REQUIRE(md5.GetDigest() == MD5::Digest{0xc3, 0xfc, 0xd3, 0xd7, 0x61, 0x92, 0xe4, 0x00, 0x7d, 0xfb, 0x49, 0x6c, 0xca,
                                           0x67, 0xe1, 0x3b});
  }
}

TEST_CASE("zm::crypto::MD5::GetDigestOf") {
  using namespace zm::crypto;
  std::array<uint8, 3> data = {'a', 'b', 'c'};

  SECTION("data and len") {
    MD5::Digest digest = MD5::GetDigestOf(reinterpret_cast<const uint8 *>(data.data()), data.size());

    REQUIRE(digest == MD5::Digest{0x90, 0x01, 0x50, 0x98, 0x3c, 0xd2, 0x4f, 0xb0, 0xd6, 0x96, 0x3f, 0x7d, 0x28, 0xe1,
                                  0x7f, 0x72});
  }

  SECTION("container") {
    MD5::Digest digest = MD5::GetDigestOf(data);

    REQUIRE(digest == MD5::Digest{0x90, 0x01, 0x50, 0x98, 0x3c, 0xd2, 0x4f, 0xb0, 0xd6, 0x96, 0x3f, 0x7d, 0x28, 0xe1,
                                  0x7f, 0x72});
  }

  SECTION("multiple containers") {
    MD5::Digest digest = MD5::GetDigestOf(data, data);

    REQUIRE(digest == MD5::Digest{0x44, 0x0a, 0xc8, 0x58, 0x92, 0xca, 0x43, 0xad, 0x26, 0xd4, 0x4c, 0x7a, 0xd9, 0xd4,
                                  0x7d, 0x3e});
  }
}

TEST_CASE("zm::crypto::SHA1::GetDigestOf") {
  using namespace zm::crypto;
  std::array<uint8, 3> data = {'a', 'b', 'c'};

  SHA1::Digest digest = SHA1::GetDigestOf(data);

  REQUIRE(digest == SHA1::Digest{0xa9, 0x99, 0x3e, 0x36, 0x47, 0x06, 0x81, 0x6a, 0xba, 0x3e, 0x25, 0x71, 0x78, 0x50,
                                 0xc2, 0x6c, 0x9c, 0xd0, 0xd8, 0x9d});
}
