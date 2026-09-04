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
#include "zm_monitor_onvif.h"

// gSOAP's SOAP_FAULT. Passed in rather than included so this links without the
// gSOAP headers; the value only has to match what the call site passes.
static constexpr int kSoapFault = 12;

TEST_CASE("ONVIFIsAuthError", "[onvif]") {
  SECTION("HTTP 401 is an auth failure even with no fault text") {
    // gSOAP reports a transport-level rejection by returning the HTTP status,
    // and there is no SOAP envelope to carry a reason. Cameras that demand
    // WWW-Authenticate answer this way.
    REQUIRE(ONVIFIsAuthError(401, kSoapFault, nullptr, nullptr));
    REQUIRE(ONVIFIsAuthError(401, kSoapFault, "", ""));
  }

  SECTION("SOAP fault naming the reason in the fault string") {
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, "Sender not authorized", nullptr));
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, "NotAuthorized", nullptr));
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, "The action requires authorization", nullptr));
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, "Unauthorized", nullptr));
  }

  SECTION("SOAP fault naming the reason only in the detail") {
    // Several cameras leave the fault string generic and put the real reason in
    // the detail element, which is why matching the string alone missed them.
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, "Sender", "NotAuthorized"));
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, nullptr, "not authorized"));
    REQUIRE(ONVIFIsAuthError(kSoapFault, kSoapFault, "", "Unauthorized"));
  }

  SECTION("Failures that are not about authentication") {
    REQUIRE_FALSE(ONVIFIsAuthError(kSoapFault, kSoapFault, "Invalid argument", nullptr));
    REQUIRE_FALSE(ONVIFIsAuthError(kSoapFault, kSoapFault, "ter:InvalidArgVal", "ter:NoSuchTopic"));
    REQUIRE_FALSE(ONVIFIsAuthError(kSoapFault, kSoapFault, nullptr, nullptr));
  }

  SECTION("Other HTTP statuses are not auth failures") {
    // 404 and 500 mean the request failed for some other reason, and must not
    // be routed into the auth handling, which suppresses repeats.
    REQUIRE_FALSE(ONVIFIsAuthError(404, kSoapFault, nullptr, nullptr));
    REQUIRE_FALSE(ONVIFIsAuthError(500, kSoapFault, "Internal Error", nullptr));
    // 403 is a refusal, but of an authenticated request; it is not fixed by
    // re-authenticating, so it keeps the generic error path.
    REQUIRE_FALSE(ONVIFIsAuthError(403, kSoapFault, nullptr, nullptr));
  }

  SECTION("Auth wording in a non-fault, non-401 result does not count") {
    // The reason text is only meaningful when the camera actually returned a
    // SOAP fault; otherwise it is whatever happened to be left in the context.
    REQUIRE_FALSE(ONVIFIsAuthError(28, kSoapFault, "NotAuthorized", "not authorized"));
  }
}
