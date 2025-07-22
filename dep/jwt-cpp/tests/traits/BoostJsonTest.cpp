#include "jwt-cpp/traits/boost-json/traits.h"

#include <gtest/gtest.h>

TEST(BoostJsonTest, BasicClaims) {
	const auto string = jwt::basic_claim<jwt::traits::boost_json>(jwt::traits::boost_json::string_type("string"));
	ASSERT_EQ(string.get_type(), jwt::json::type::string);

	const auto array =
		jwt::basic_claim<jwt::traits::boost_json>(std::set<jwt::traits::boost_json::string_type>{"string", "string"});
	ASSERT_EQ(array.get_type(), jwt::json::type::array);

	jwt::traits::boost_json::value_type jvi = 159816816;
	const auto integer = jwt::basic_claim<jwt::traits::boost_json>(jvi);
	ASSERT_EQ(integer.get_type(), jwt::json::type::integer);
}

TEST(BoostJsonTest, AudienceAsString) {
	jwt::traits::boost_json::string_type token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJ0ZXN0In0.WZnM3SIiSRHsbO3O7Z2bmIzTJ4EC32HRBKfLznHhrh4";
	auto decoded = jwt::decode<jwt::traits::boost_json>(token);

	ASSERT_TRUE(decoded.has_algorithm());
	ASSERT_TRUE(decoded.has_type());
	ASSERT_FALSE(decoded.has_content_type());
	ASSERT_FALSE(decoded.has_key_id());
	ASSERT_FALSE(decoded.has_issuer());
	ASSERT_FALSE(decoded.has_subject());
	ASSERT_TRUE(decoded.has_audience());
	ASSERT_FALSE(decoded.has_expires_at());
	ASSERT_FALSE(decoded.has_not_before());
	ASSERT_FALSE(decoded.has_issued_at());
	ASSERT_FALSE(decoded.has_id());

	ASSERT_EQ("HS256", decoded.get_algorithm());
	ASSERT_EQ("JWT", decoded.get_type());
	auto aud = decoded.get_audience();
	ASSERT_EQ(1, aud.size());
	ASSERT_EQ("test", *aud.begin());
}

TEST(BoostJsonTest, SetArray) {
	std::vector<int64_t> vect = {100, 20, 10};
	auto token = jwt::create<jwt::traits::boost_json>()
					 .set_payload_claim("test", jwt::basic_claim<jwt::traits::boost_json>(vect.begin(), vect.end()))
					 .sign(jwt::algorithm::none{});
	ASSERT_EQ(token, "eyJhbGciOiJub25lIn0.eyJ0ZXN0IjpbMTAwLDIwLDEwXX0.");
}

TEST(BoostJsonTest, SetObject) {
	jwt::traits::boost_json::value_type value;
	ASSERT_TRUE(jwt::traits::boost_json::parse(value, "{\"api-x\": [1]}"));
	jwt::basic_claim<jwt::traits::boost_json> object(value);
	ASSERT_EQ(object.get_type(), jwt::json::type::object);

	auto token = jwt::create<jwt::traits::boost_json>()
					 .set_payload_claim("namespace", object)
					 .sign(jwt::algorithm::hs256("test"));
	ASSERT_EQ(token,
			  "eyJhbGciOiJIUzI1NiJ9.eyJuYW1lc3BhY2UiOnsiYXBpLXgiOlsxXX19.F8I6I2RcSF98bKa0IpIz09fRZtHr1CWnWKx2za-tFQA");
}

TEST(BoostJsonTest, VerifyTokenHS256) {
	jwt::traits::boost_json::string_type token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";

	const auto decoded_token = jwt::decode<jwt::traits::boost_json>(token);
	const auto verify =
		jwt::verify<jwt::traits::boost_json>().allow_algorithm(jwt::algorithm::hs256{"secret"}).with_issuer("auth0");
	verify.verify(decoded_token);
}

TEST(BoostJsonTest, VerifyTokenExpirationValid) {
	const auto token = jwt::create<jwt::traits::boost_json>()
						   .set_issuer("auth0")
						   .set_issued_at(std::chrono::system_clock::now())
						   .set_expires_at(std::chrono::system_clock::now() + std::chrono::seconds{3600})
						   .sign(jwt::algorithm::hs256{"secret"});

	const auto decoded_token = jwt::decode<jwt::traits::boost_json>(token);
	const auto verify =
		jwt::verify<jwt::traits::boost_json>().allow_algorithm(jwt::algorithm::hs256{"secret"}).with_issuer("auth0");
	verify.verify(decoded_token);
}

TEST(BoostJsonTest, VerifyTokenExpired) {
	const auto token = jwt::create<jwt::traits::boost_json>()
						   .set_issuer("auth0")
						   .set_issued_at(std::chrono::system_clock::now() - std::chrono::seconds{3601})
						   .set_expires_at(std::chrono::system_clock::now() - std::chrono::seconds{1})
						   .sign(jwt::algorithm::hs256{"secret"});

	const auto decoded_token = jwt::decode<jwt::traits::boost_json>(token);
	const auto verify =
		jwt::verify<jwt::traits::boost_json>().allow_algorithm(jwt::algorithm::hs256{"secret"}).with_issuer("auth0");
	ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);

	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_TRUE(!(!ec));
	ASSERT_EQ(ec.category(), jwt::error::token_verification_error_category());
	ASSERT_EQ(ec.value(), static_cast<int>(jwt::error::token_verification_error::token_expired));
}

TEST(BoostJsonTest, VerifyArray) {
	jwt::traits::boost_json::string_type token = "eyJhbGciOiJub25lIn0.eyJ0ZXN0IjpbMTAwLDIwLDEwXX0.";
	const auto decoded_token = jwt::decode<jwt::traits::boost_json>(token);

	std::vector<int64_t> vect = {100, 20, 10};
	jwt::basic_claim<jwt::traits::boost_json> array_claim(vect.begin(), vect.end());
	const auto verify =
		jwt::verify<jwt::traits::boost_json>().allow_algorithm(jwt::algorithm::none{}).with_claim("test", array_claim);
	ASSERT_NO_THROW(verify.verify(decoded_token));
}

TEST(BoostJsonTest, VerifyObject) {
	jwt::traits::boost_json::string_type token =
		"eyJhbGciOiJIUzI1NiJ9.eyJuYW1lc3BhY2UiOnsiYXBpLXgiOlsxXX19.F8I6I2RcSF98bKa0IpIz09fRZtHr1CWnWKx2za-tFQA";
	const auto decoded_token = jwt::decode<jwt::traits::boost_json>(token);

	jwt::traits::boost_json::value_type value;
	ASSERT_TRUE(jwt::traits::boost_json::parse(value, "{\"api-x\": [1]}"));
	jwt::basic_claim<jwt::traits::boost_json> object_claim(value);
	const auto verify = jwt::verify<jwt::traits::boost_json>()
							.allow_algorithm(jwt::algorithm::hs256("test"))
							.with_claim("namespace", object_claim);
	ASSERT_NO_THROW(verify.verify(decoded_token));
}
