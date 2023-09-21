#include "jwt-cpp/jwt.h"
#include <gtest/gtest.h>

TEST(JwksTest, OneKeyParse) {
	std::string public_key = R"({
    "alg": "RS256",
    "kty": "RSA",
    "use": "sig",
    "x5c": [
      "MIIC+DCCAeCgAwIBAgIJBIGjYW6hFpn2MA0GCSqGSIb3DQEBBQUAMCMxITAfBgNVBAMTGGN1c3RvbWVyLWRlbW9zLmF1dGgwLmNvbTAeFw0xNjExMjIyMjIyMDVaFw0zMDA4MDEyMjIyMDVaMCMxITAfBgNVBAMTGGN1c3RvbWVyLWRlbW9zLmF1dGgwLmNvbTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAMnjZc5bm/eGIHq09N9HKHahM7Y31P0ul+A2wwP4lSpIwFrWHzxw88/7Dwk9QMc+orGXX95R6av4GF+Es/nG3uK45ooMVMa/hYCh0Mtx3gnSuoTavQEkLzCvSwTqVwzZ+5noukWVqJuMKNwjL77GNcPLY7Xy2/skMCT5bR8UoWaufooQvYq6SyPcRAU4BtdquZRiBT4U5f+4pwNTxSvey7ki50yc1tG49Per/0zA4O6Tlpv8x7Red6m1bCNHt7+Z5nSl3RX/QYyAEUX1a28VcYmR41Osy+o2OUCXYdUAphDaHo4/8rbKTJhlu8jEcc1KoMXAKjgaVZtG/v5ltx6AXY0CAwEAAaMvMC0wDAYDVR0TBAUwAwEB/zAdBgNVHQ4EFgQUQxFG602h1cG+pnyvJoy9pGJJoCswDQYJKoZIhvcNAQEFBQADggEBAGvtCbzGNBUJPLICth3mLsX0Z4z8T8iu4tyoiuAshP/Ry/ZBnFnXmhD8vwgMZ2lTgUWwlrvlgN+fAtYKnwFO2G3BOCFw96Nm8So9sjTda9CCZ3dhoH57F/hVMBB0K6xhklAc0b5ZxUpCIN92v/w+xZoz1XQBHe8ZbRHaP1HpRM4M7DJk2G5cgUCyu3UBvYS41sHvzrxQ3z7vIePRA4WF4bEkfX12gvny0RsPkrbVMXX1Rj9t6V7QXrbPYBAO+43JvDGYawxYVvLhz+BJ45x50GFQmHszfY3BR9TPK8xmMmQwtIvLu1PMttNCs7niCYkSiUv2sc2mlq1i3IashGkkgmo="
    ],
    "n": "yeNlzlub94YgerT030codqEztjfU_S6X4DbDA_iVKkjAWtYfPHDzz_sPCT1Axz6isZdf3lHpq_gYX4Sz-cbe4rjmigxUxr-FgKHQy3HeCdK6hNq9ASQvMK9LBOpXDNn7mei6RZWom4wo3CMvvsY1w8tjtfLb-yQwJPltHxShZq5-ihC9irpLI9xEBTgG12q5lGIFPhTl_7inA1PFK97LuSLnTJzW0bj096v_TMDg7pOWm_zHtF53qbVsI0e3v5nmdKXdFf9BjIARRfVrbxVxiZHjU6zL6jY5QJdh1QCmENoejj_ytspMmGW7yMRxzUqgxcAqOBpVm0b-_mW3HoBdjQ",
    "e": "AQAB",
    "kid": "123456789",
    "x5t": "NjVBRjY5MDlCMUIwNzU4RTA2QzZFMDQ4QzQ2MDAyQjVDNjk1RTM2Qg"
  })";
	ASSERT_THROW(jwt::parse_jwk("__not_json__"), jwt::error::invalid_json_exception);
	ASSERT_THROW(jwt::parse_jwk(R"##(["not","an","object"])##"), std::bad_cast);

	auto jwk = jwt::parse_jwk(public_key);

	ASSERT_TRUE(jwk.has_algorithm());
	ASSERT_TRUE(jwk.has_key_id());
	ASSERT_TRUE(jwk.has_x5c());
	ASSERT_FALSE(jwk.has_jwk_claim("foo"));

	ASSERT_EQ("RS256", jwk.get_algorithm());
	ASSERT_EQ("123456789", jwk.get_key_id());
}

TEST(JwksTest, MultiKeysParse) {
	std::string public_key = R"({
	"keys": [{
			"kid": "internal-gateway-jwt",
			"use": "sig",
			"x5c": ["MIIG1TCCBL2gAwIBAgIIFvMVGp6t\/cMwDQYJKoZIhvcNAQELBQAwZjELMAkGA1UEBhMCR0IxIDAeBgNVBAoMF1N0YW5kYXJkIENoYXJ0ZXJlZCBCYW5rMTUwMwYDVQQDDCxTdGFuZGFyZCBDaGFydGVyZWQgQmFuayBTaWduaW5nIENBIEcxIC0gU0hBMjAeFw0xODEwMTAxMTI2MzVaFw0yMjEwMTAxMTI2MzVaMIG9MQswCQYDVQQGEwJTRzESMBAGA1UECAwJU2luZ2Fwb3JlMRIwEAYDVQQHDAlTaW5nYXBvcmUxIDAeBgNVBAoMF1N0YW5kYXJkIENoYXJ0ZXJlZCBCYW5rMRwwGgYDVQQLDBNGb3VuZGF0aW9uIFNlcnZpY2VzMSgwJgYDVQQDDB9pbnRlcm5hbC1nYXRld2F5LWp3dC5hcGkuc2MubmV0MRwwGgYJKoZIhvcNAQkBFg1BUElQU1NAc2MuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArVWBoIi3IJ4nOWXu7\/SDxczqMou1B+c4c2FdQrOXrK31HxAaz4WEtma9BLXFdFHJ5mCCPIvdUcVxxnCynqhMOkZ\/a7acQbUD9cDzI8isMB9JL7VooDw0CctxHxffjqQQVIEhC2Q7zsM1pQayR7cl+pbBlvHIoRxq2n1B0fFvfoiosjf4kDiCpgHdM+v5Hw9aVYmUbroHxmQWqhB0iRTJQPPLZqqQVC50A1Q\/96gkwoODyotc46Uy9wYEpdGrtDG\/thWay3fmMsjpWR0U25xFIrxTrfCGBblYpD7juukWWml2E9rtE2rHgUxbymxXjEw7xrMwcGrhOGyqwoBqJy1JVwIDAQABo4ICLTCCAikwZAYIKwYBBQUHAQEEWDBWMFQGCCsGAQUFBzABhkhodHRwOi8vY29yZW9jc3AuZ2xvYmFsLnN0YW5kYXJkY2hhcnRlcmVkLmNvbS9lamJjYS9wdWJsaWN3ZWIvc3RhdHVzL29jc3AwHQYDVR0OBBYEFIinW4BNDeVEFcuLf8YjZjtySoW9MAwGA1UdEwEB\/wQCMAAwHwYDVR0jBBgwFoAUfNZMoZi33nKrcmVU3TFVQnuEi\/4wggFCBgNVHR8EggE5MIIBNTCCATGggcKggb+GgbxodHRwOi8vY29yZWNybC5nbG9iYWwuc3RhbmRhcmRjaGFydGVyZWQuY29tL2VqYmNhL3B1YmxpY3dlYi93ZWJkaXN0L2NlcnRkaXN0P2NtZD1jcmwmaXNzdWVyPUNOPVN0YW5kYXJkJTIwQ2hhcnRlcmVkJTIwQmFuayUyMFNpZ25pbmclMjBDQSUyMEcxJTIwLSUyMFNIQTIsTz1TdGFuZGFyZCUyMENoYXJ0ZXJlZCUyMEJhbmssQz1HQqJqpGgwZjE1MDMGA1UEAwwsU3RhbmRhcmQgQ2hhcnRlcmVkIEJhbmsgU2lnbmluZyBDQSBHMSAtIFNIQTIxIDAeBgNVBAoMF1N0YW5kYXJkIENoYXJ0ZXJlZCBCYW5rMQswCQYDVQQGEwJHQjAOBgNVHQ8BAf8EBAMCBsAwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMEMA0GCSqGSIb3DQEBCwUAA4ICAQBtsoRlDHuOTDChcWdfdVUtRgP0U0ijDSeJi8vULN1rgYnqqJc4PdJno50aiu9MGlxY02O7HW7ZVD6QEG\/pqHmZ0sbWpb\/fumMgZSjP65IcGuS53zgcNtLYnyXyEv+v5T\/CK3bk4Li6tUW3ScJPUwVWwP1E0\/u6aBSb5k\/h4lTwS1o88ybS5pJOg6XutXByp991QQrrs7tp7fKNynjNZbFuG3J1e09X+zTfJOpjaDUofQTkt8IyMRI6Cs4wI1eZA+dAIL8B0n8ze1mRl1FOJqgdZrAQjoqZkCTnc0Il5VY\/dUXxGVg6D9e5pfck3FWT107K9\/5EZoxytpqYXFCjMXi5hx4YjK17OUgm82mZhvqkNdzF8Yq2vFuB3LPfyelESq99xFLykvinrVm1NtZKeDTT1Jq\/VvZt6stO\/tovq1RfJJcznpYcwOzxlnhGR6E+hxuBx7aDJzGf0JaoRxQILH1B2XV9WDI3HPYQsP7XtriX+QUJ\/aly28QkV48RmaGYCsly43YZu1MKudSsw+dhnbZzRsg\/aes3dzGW2x137bQPtux7k2LCSpsTXgedhOys28YoGlsoe8kUv0myAU4Stt+I3mrwO3BKUn+tJggvlDiiiyT1tg2HiklyU\/2FxQkZRMeB0eRrXTpg3l9x2mpF+dDFxOMKszxwD2kgoEZgo6o58A=="],
			"n": "nr9UsxnPVd21iuiGcIJ_Qli2XVlAZe5VbELA1hO2-L4k5gI4fjHZ3ysUcautLpbOYogOQgsnlpsLrCmvNDvBDVzVp2nMbpguJlt12vHSP1fRJJpipGQ8qU-VaXsC4OjOQf3H9ojAU5Vfnl5gZ7kVCd8g4M29l-IRyNpxE-Ccxc2Y7molsCHT6GHLMMBVsd11JIOXMICJf4hz2YYkQ1t7C8SaB2RFRPuGO5Mn6mfAnwdmRera4TBz6_pIPPCgCbN8KOdJItWkr9F7Tjv_0nhh-ZVlQvbQ9PXHyKTj00g3IYUlbZIWHm0Ley__fzNZk2dyAAVjNA2QSzTZJc33MQx1pQ",
			"e": "AQAB",
			"x5t": "-qC0akuyiHT7GcV5a8O5nrFsKVWM9da7lzq6DLrj09I",
			"alg": "RS256",
			"kty": "RSA"
		},
		{
			"kid": "internal-0",
			"use": "sig",
			"x5c": ["MIIG1TCCBL2gAwIBAgIIFvMVGp6t\/cMwDQYJKoZIhvcNAQELBQAwZjELMAkGA1UEBhMCR0IxIDAeBgNVBAoMF1N0YW5kYXJkIENoYXJ0ZXJlZCBCYW5rMTUwMwYDVQQDDCxTdGFuZGFyZCBDaGFydGVyZWQgQmFuayBTaWduaW5nIENBIEcxIC0gU0hBMjAeFw0xODEwMTAxMTI2MzVaFw0yMjEwMTAxMTI2MzVaMIG9MQswCQYDVQQGEwJTRzESMBAGA1UECAwJU2luZ2Fwb3JlMRIwEAYDVQQHDAlTaW5nYXBvcmUxIDAeBgNVBAoMF1N0YW5kYXJkIENoYXJ0ZXJlZCBCYW5rMRwwGgYDVQQLDBNGb3VuZGF0aW9uIFNlcnZpY2VzMSgwJgYDVQQDDB9pbnRlcm5hbC1nYXRld2F5LWp3dC5hcGkuc2MubmV0MRwwGgYJKoZIhvcNAQkBFg1BUElQU1NAc2MuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArVWBoIi3IJ4nOWXu7\/SDxczqMou1B+c4c2FdQrOXrK31HxAaz4WEtma9BLXFdFHJ5mCCPIvdUcVxxnCynqhMOkZ\/a7acQbUD9cDzI8isMB9JL7VooDw0CctxHxffjqQQVIEhC2Q7zsM1pQayR7cl+pbBlvHIoRxq2n1B0fFvfoiosjf4kDiCpgHdM+v5Hw9aVYmUbroHxmQWqhB0iRTJQPPLZqqQVC50A1Q\/96gkwoODyotc46Uy9wYEpdGrtDG\/thWay3fmMsjpWR0U25xFIrxTrfCGBblYpD7juukWWml2E9rtE2rHgUxbymxXjEw7xrMwcGrhOGyqwoBqJy1JVwIDAQABo4ICLTCCAikwZAYIKwYBBQUHAQEEWDBWMFQGCCsGAQUFBzABhkhodHRwOi8vY29yZW9jc3AuZ2xvYmFsLnN0YW5kYXJkY2hhcnRlcmVkLmNvbS9lamJjYS9wdWJsaWN3ZWIvc3RhdHVzL29jc3AwHQYDVR0OBBYEFIinW4BNDeVEFcuLf8YjZjtySoW9MAwGA1UdEwEB\/wQCMAAwHwYDVR0jBBgwFoAUfNZMoZi33nKrcmVU3TFVQnuEi\/4wggFCBgNVHR8EggE5MIIBNTCCATGggcKggb+GgbxodHRwOi8vY29yZWNybC5nbG9iYWwuc3RhbmRhcmRjaGFydGVyZWQuY29tL2VqYmNhL3B1YmxpY3dlYi93ZWJkaXN0L2NlcnRkaXN0P2NtZD1jcmwmaXNzdWVyPUNOPVN0YW5kYXJkJTIwQ2hhcnRlcmVkJTIwQmFuayUyMFNpZ25pbmclMjBDQSUyMEcxJTIwLSUyMFNIQTIsTz1TdGFuZGFyZCUyMENoYXJ0ZXJlZCUyMEJhbmssQz1HQqJqpGgwZjE1MDMGA1UEAwwsU3RhbmRhcmQgQ2hhcnRlcmVkIEJhbmsgU2lnbmluZyBDQSBHMSAtIFNIQTIxIDAeBgNVBAoMF1N0YW5kYXJkIENoYXJ0ZXJlZCBCYW5rMQswCQYDVQQGEwJHQjAOBgNVHQ8BAf8EBAMCBsAwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMEMA0GCSqGSIb3DQEBCwUAA4ICAQBtsoRlDHuOTDChcWdfdVUtRgP0U0ijDSeJi8vULN1rgYnqqJc4PdJno50aiu9MGlxY02O7HW7ZVD6QEG\/pqHmZ0sbWpb\/fumMgZSjP65IcGuS53zgcNtLYnyXyEv+v5T\/CK3bk4Li6tUW3ScJPUwVWwP1E0\/u6aBSb5k\/h4lTwS1o88ybS5pJOg6XutXByp991QQrrs7tp7fKNynjNZbFuG3J1e09X+zTfJOpjaDUofQTkt8IyMRI6Cs4wI1eZA+dAIL8B0n8ze1mRl1FOJqgdZrAQjoqZkCTnc0Il5VY\/dUXxGVg6D9e5pfck3FWT107K9\/5EZoxytpqYXFCjMXi5hx4YjK17OUgm82mZhvqkNdzF8Yq2vFuB3LPfyelESq99xFLykvinrVm1NtZKeDTT1Jq\/VvZt6stO\/tovq1RfJJcznpYcwOzxlnhGR6E+hxuBx7aDJzGf0JaoRxQILH1B2XV9WDI3HPYQsP7XtriX+QUJ\/aly28QkV48RmaGYCsly43YZu1MKudSsw+dhnbZzRsg\/aes3dzGW2x137bQPtux7k2LCSpsTXgedhOys28YoGlsoe8kUv0myAU4Stt+I3mrwO3BKUn+tJggvlDiiiyT1tg2HiklyU\/2FxQkZRMeB0eRrXTpg3l9x2mpF+dDFxOMKszxwD2kgoEZgo6o58A=="],
			"n": "nr9UsxnPVd21iuiGcIJ_Qli2XVlAZe5VbELA1hO2-L4k5gI4fjHZ3ysUcautLpbOYogOQgsnlpsLrCmvNDvBDVzVp2nMbpguJlt12vHSP1fRJJpipGQ8qU-VaXsC4OjOQf3H9ojAU5Vfnl5gZ7kVCd8g4M29l-IRyNpxE-Ccxc2Y7molsCHT6GHLMMBVsd11JIOXMICJf4hz2YYkQ1t7C8SaB2RFRPuGO5Mn6mfAnwdmRera4TBz6_pIPPCgCbN8KOdJItWkr9F7Tjv_0nhh-ZVlQvbQ9PXHyKTj00g3IYUlbZIWHm0Ley__fzNZk2dyAAVjNA2QSzTZJc33MQx1pQ",
			"e": "AQAB",
			"x5t": "-qC0akuyiHT7GcV5a8O5nrFsKVWM9da7lzq6DLrj09I",
			"alg": "RS256",
			"kty": "RSA"
		}
	]
})";
	auto jwks = jwt::parse_jwks(public_key);
	auto jwk = jwks.get_jwk("internal-gateway-jwt");

	ASSERT_TRUE(jwk.has_algorithm());
	ASSERT_TRUE(jwk.has_key_id());
	ASSERT_TRUE(jwk.has_x5c());
	ASSERT_FALSE(jwk.has_jwk_claim("foo"));

	ASSERT_EQ("RS256", jwk.get_algorithm());
	ASSERT_EQ("internal-gateway-jwt", jwk.get_key_id());

	ASSERT_THROW(jwks.get_jwk("123456"), jwt::error::claim_not_present_exception);
}

TEST(JwksTest, Missingx5c) {
	std::string public_key = R"({
	"keys": [{
			"kid": "internal-gateway-jwt",
			"use": "sig",
			"n": "nr9UsxnPVd21iuiGcIJ_Qli2XVlAZe5VbELA1hO2-L4k5gI4fjHZ3ysUcautLpbOYogOQgsnlpsLrCmvNDvBDVzVp2nMbpguJlt12vHSP1fRJJpipGQ8qU-VaXsC4OjOQf3H9ojAU5Vfnl5gZ7kVCd8g4M29l-IRyNpxE-Ccxc2Y7molsCHT6GHLMMBVsd11JIOXMICJf4hz2YYkQ1t7C8SaB2RFRPuGO5Mn6mfAnwdmRera4TBz6_pIPPCgCbN8KOdJItWkr9F7Tjv_0nhh-ZVlQvbQ9PXHyKTj00g3IYUlbZIWHm0Ley__fzNZk2dyAAVjNA2QSzTZJc33MQx1pQ",
			"e": "AQAB",
			"x5t": "-qC0akuyiHT7GcV5a8O5nrFsKVWM9da7lzq6DLrj09I",
			"alg": "RS256",
			"kty": "RSA"
		},
		{
			"kid": "internal-0",
			"use": "sig",
			"x5c": [],
			"n": "nr9UsxnPVd21iuiGcIJ_Qli2XVlAZe5VbELA1hO2-L4k5gI4fjHZ3ysUcautLpbOYogOQgsnlpsLrCmvNDvBDVzVp2nMbpguJlt12vHSP1fRJJpipGQ8qU-VaXsC4OjOQf3H9ojAU5Vfnl5gZ7kVCd8g4M29l-IRyNpxE-Ccxc2Y7molsCHT6GHLMMBVsd11JIOXMICJf4hz2YYkQ1t7C8SaB2RFRPuGO5Mn6mfAnwdmRera4TBz6_pIPPCgCbN8KOdJItWkr9F7Tjv_0nhh-ZVlQvbQ9PXHyKTj00g3IYUlbZIWHm0Ley__fzNZk2dyAAVjNA2QSzTZJc33MQx1pQ",
			"e": "AQAB",
			"x5t": "-qC0akuyiHT7GcV5a8O5nrFsKVWM9da7lzq6DLrj09I",
			"alg": "RS256",
			"kty": "RSA"
		},
		{
			"kid": "internal-1",
			"x5c": ["1","2","3"],
			"n": "nr9UsxnPVd21iuiGcIJ_Qli2XVlAZe5VbELA1hO2-L4k5gI4fjHZ3ysUcautLpbOYogOQgsnlpsLrCmvNDvBDVzVp2nMbpguJlt12vHSP1fRJJpipGQ8qU-VaXsC4OjOQf3H9ojAU5Vfnl5gZ7kVCd8g4M29l-IRyNpxE-Ccxc2Y7molsCHT6GHLMMBVsd11JIOXMICJf4hz2YYkQ1t7C8SaB2RFRPuGO5Mn6mfAnwdmRera4TBz6_pIPPCgCbN8KOdJItWkr9F7Tjv_0nhh-ZVlQvbQ9PXHyKTj00g3IYUlbZIWHm0Ley__fzNZk2dyAAVjNA2QSzTZJc33MQx1pQ",
			"e": "AQAB",
			"x5t": "-qC0akuyiHT7GcV5a8O5nrFsKVWM9da7lzq6DLrj09I",
			"alg": "RS256",
			"kty": "RSA"
		}
	]
})";

	auto jwks = jwt::parse_jwks(public_key);
	ASSERT_TRUE(jwks.has_jwk("internal-gateway-jwt"));
	ASSERT_FALSE(jwks.has_jwk("random-jwt"));
	auto jwk = jwks.get_jwk("internal-gateway-jwt");

	ASSERT_TRUE(jwk.has_algorithm());
	ASSERT_THROW(jwk.get_x5c(), jwt::error::claim_not_present_exception);

	auto jwk2 = jwks.get_jwk("internal-0");

	ASSERT_EQ(jwk2.get_x5c().size(), 0);
	ASSERT_THROW(jwk2.get_x5c_key_value(), jwt::error::claim_not_present_exception);

	auto jwk3 = jwks.get_jwk("internal-1");
	ASSERT_EQ(jwk3.get_x5c().size(), 3);
	ASSERT_EQ(jwk3.get_x5c_key_value(), "1");
}
