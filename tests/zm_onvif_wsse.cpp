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

#include <ctime>
#include <string>

#include "soapH.h"
#include "plugin/wsseapi.h"

// Regression test for the WS-Security Created race in ONVIF::set_credentials().
//
// gSOAP's soap_wsse_add_Timestamp() and soap_wsse_add_UsernameTokenDigest()
// each call time(NULL) on their own. When those two calls straddle a one-second
// boundary, the wsu:Timestamp Created and the UsernameToken Created end up one
// second apart, and Hikvision (and some other cameras) reject the request as
// NotAuthorized. set_credentials() avoids this by capturing a single time(NULL)
// and forcing both Created values to it (re-stamping the Timestamp and using the
// _at variant for the token digest).
TEST_CASE("ONVIF WS-Security Created timestamps are pinned together") {
  SECTION("Timestamp Created matches UsernameToken Created (digest auth)") {
    struct soap *soap = soap_new();
    REQUIRE(soap != nullptr);
    soap_register_plugin(soap, soap_wsse);

    const int validity = 60;

    // Reproduce the exact sequence set_credentials() uses for digest auth.
    soap_wsse_delete_Security(soap);
    time_t wsse_now = time(nullptr);
    soap_wsse_add_Timestamp(soap, "Time", validity);

    _wsse__Security *security = soap_wsse_add_Security(soap);
    REQUIRE(security != nullptr);
    REQUIRE(security->wsu__Timestamp != nullptr);
    security->wsu__Timestamp->Created = soap_strdup(soap, soap_dateTime2s(soap, wsse_now));
    if (security->wsu__Timestamp->Expires) {
      security->wsu__Timestamp->Expires =
          soap_strdup(soap, soap_dateTime2s(soap, wsse_now + validity));
    }

    soap_wsse_add_UsernameTokenDigest_at(soap, "Auth", "admin", "password", wsse_now);

    REQUIRE(security->wsu__Timestamp->Created != nullptr);
    REQUIRE(security->UsernameToken != nullptr);
    REQUIRE(security->UsernameToken->wsu__Created != nullptr);

    // The whole point of the fix: these two must always be identical, so the
    // camera never sees the off-by-one second that triggers NotAuthorized.
    REQUIRE(std::string(security->wsu__Timestamp->Created) ==
            std::string(security->UsernameToken->wsu__Created));

    // And the pinned value is exactly the one we captured.
    REQUIRE(std::string(security->wsu__Timestamp->Created) ==
            std::string(soap_dateTime2s(soap, wsse_now)));

    soap_wsse_delete_Security(soap);
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
  }

  SECTION("A one-second gap really does change the Created string") {
    // Documents why the un-pinned (racy) code fails: two time(NULL) results one
    // second apart serialise to different Created values, which is precisely the
    // mismatch Hikvision rejected in the captured SOAP logs.
    struct soap *soap = soap_new();
    REQUIRE(soap != nullptr);

    time_t now = time(nullptr);
    std::string a(soap_dateTime2s(soap, now));
    std::string b(soap_dateTime2s(soap, now + 1));
    REQUIRE(a != b);

    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
  }
}
