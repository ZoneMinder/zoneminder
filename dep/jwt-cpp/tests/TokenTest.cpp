#include "jwt-cpp/jwt.h"
#include <gtest/gtest.h>

inline namespace test_keys {
	extern std::string rsa_priv_key;
	extern std::string rsa_pub_key;
	extern std::string rsa_pub_key_invalid;
	extern std::string rsa512_priv_key;
	extern std::string rsa512_pub_key;
	extern std::string rsa512_pub_key_invalid;
	extern std::string ecdsa256_certificate;
	extern std::string ecdsa256_priv_key;
	extern std::string ecdsa256_pub_key;
	extern std::string ecdsa256_pub_key_invalid;
	extern std::string ecdsa384_priv_key;
	extern std::string ecdsa384_pub_key;
	extern std::string ecdsa384_pub_key_invalid;
	extern std::string ecdsa521_priv_key;
	extern std::string ecdsa521_pub_key;
	extern std::string ecdsa521_pub_key_invalid;
	extern std::string ed25519_priv_key;
	extern std::string ed25519_pub_key;
	extern std::string ed25519_pub_key_invalid;
	extern std::string ed448_priv_key;
	extern std::string ed448_pub_key;
	extern std::string ed448_pub_key_invalid;
} // namespace test_keys

TEST(TokenTest, DecodeToken) {
	std::string token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";
	auto decoded = jwt::decode(token);

	ASSERT_TRUE(decoded.has_algorithm());
	ASSERT_TRUE(decoded.has_type());
	ASSERT_FALSE(decoded.has_content_type());
	ASSERT_FALSE(decoded.has_key_id());
	ASSERT_TRUE(decoded.has_issuer());
	ASSERT_FALSE(decoded.has_subject());
	ASSERT_FALSE(decoded.has_audience());
	ASSERT_FALSE(decoded.has_expires_at());
	ASSERT_FALSE(decoded.has_not_before());
	ASSERT_FALSE(decoded.has_issued_at());
	ASSERT_FALSE(decoded.has_id());

	ASSERT_EQ("HS256", decoded.get_algorithm());
	ASSERT_EQ("JWS", decoded.get_type());
	ASSERT_EQ("auth0", decoded.get_issuer());
}

TEST(TokenTest, CreateToken) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::none{});
	ASSERT_EQ("eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0.eyJpc3MiOiJhdXRoMCJ9.", token);
}

TEST(TokenTest, CreateTokenHS256) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::hs256{"secret"});
	ASSERT_EQ("eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE",
			  token);
}

TEST(TokenTest, CreateTokenRS256) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
		jwt::algorithm::rs256(rsa_pub_key, rsa_priv_key, "", ""));

	ASSERT_EQ(
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ",
		token);
}

TEST(TokenTest, CreateTokenRS512) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
		jwt::algorithm::rs512(rsa512_pub_key, rsa512_priv_key, "", ""));

	ASSERT_EQ("eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZSSQyLKvI0"
			  "TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4GB"
			  "hfGgejPBCBlGrQtqFGFdHHOjNHY",
			  token);
}

TEST(TokenTest, CreateTokenPS256) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
		jwt::algorithm::ps256(rsa_pub_key, rsa_priv_key, "", ""));

	// TODO: Find a better way to check if generated signature is valid
	// Can't do simple check for equal since pss adds random salt.
}

TEST(TokenTest, CreateTokenPS384) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
		jwt::algorithm::ps384(rsa_pub_key, rsa_priv_key, "", ""));

	// TODO: Find a better way to check if generated signature is valid
	// Can't do simple check for equal since pss adds random salt.
}

TEST(TokenTest, CreateTokenPS512) {
	auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
		jwt::algorithm::ps512(rsa_pub_key, rsa_priv_key, "", ""));

	// TODO: Find a better way to check if generated signature is valid
	// Can't do simple check for equal since pss adds random salt.
}

TEST(TokenTest, CreateTokenES256) {

	auto token =
		jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::es256("", ecdsa256_priv_key, "", ""));

	auto decoded = jwt::decode(token);

	ASSERT_THROW(
		jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa256_pub_key_invalid, "", "", "")).verify(decoded),
		jwt::error::signature_verification_exception);
	ASSERT_NO_THROW(jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa256_pub_key, "", "", "")).verify(decoded));
}

TEST(TokenTest, CreateTokenES256NoPrivate) {
	ASSERT_THROW(
		[]() {
			auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
				jwt::algorithm::es256(ecdsa256_pub_key, "", "", ""));
		}(),
		jwt::error::signature_generation_exception);
}

TEST(TokenTest, CreateTokenES384) {

	auto token =
		jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::es384("", ecdsa384_priv_key, "", ""));

	auto decoded = jwt::decode(token);

	ASSERT_THROW(
		jwt::verify().allow_algorithm(jwt::algorithm::es384(ecdsa384_pub_key_invalid, "", "", "")).verify(decoded),
		jwt::error::signature_verification_exception);
	ASSERT_NO_THROW(jwt::verify().allow_algorithm(jwt::algorithm::es384(ecdsa384_pub_key, "", "", "")).verify(decoded));
}

TEST(TokenTest, CreateTokenES384NoPrivate) {

	ASSERT_THROW(
		[]() {
			auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
				jwt::algorithm::es384(ecdsa384_pub_key, "", "", ""));
		}(),
		jwt::error::signature_generation_exception);
}

TEST(TokenTest, CreateTokenES512) {

	auto token =
		jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::es512("", ecdsa521_priv_key, "", ""));

	auto decoded = jwt::decode(token);

	ASSERT_THROW(
		jwt::verify().allow_algorithm(jwt::algorithm::es512(ecdsa521_pub_key_invalid, "", "", "")).verify(decoded),
		jwt::error::signature_verification_exception);
	ASSERT_NO_THROW(jwt::verify().allow_algorithm(jwt::algorithm::es512(ecdsa521_pub_key, "", "", "")).verify(decoded));
}

TEST(TokenTest, CreateTokenES512NoPrivate) {

	ASSERT_THROW(
		[]() {
			auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(
				jwt::algorithm::es512(ecdsa521_pub_key, "", "", ""));
		}(),
		jwt::error::signature_generation_exception);
}

#if !defined(JWT_OPENSSL_1_0_0) && !defined(JWT_OPENSSL_1_1_0)
TEST(TokenTest, CreateTokenEd25519) {

	auto token =
		jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::ed25519("", ed25519_priv_key, "", ""));

	auto decoded = jwt::decode(token);

	ASSERT_THROW(
		jwt::verify().allow_algorithm(jwt::algorithm::ed25519(ed25519_pub_key_invalid, "", "", "")).verify(decoded),
		jwt::error::signature_verification_exception);
	ASSERT_NO_THROW(
		jwt::verify().allow_algorithm(jwt::algorithm::ed25519(ed25519_pub_key, "", "", "")).verify(decoded));
}

TEST(TokenTest, CreateTokenEd448) {

	auto token =
		jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::ed448("", ed448_priv_key, "", ""));

	auto decoded = jwt::decode(token);

	ASSERT_THROW(
		jwt::verify().allow_algorithm(jwt::algorithm::ed448(ed448_pub_key_invalid, "", "", "")).verify(decoded),
		jwt::error::signature_verification_exception);
	ASSERT_NO_THROW(jwt::verify().allow_algorithm(jwt::algorithm::ed448(ed448_pub_key, "", "", "")).verify(decoded));
}
#endif

TEST(TokenTest, VerifyTokenWrongAlgorithm) {
	std::string token =
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::none{}).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
}

TEST(TokenTest, VerifyTokenNoneFail) {
	// None algorithm should not have a signature
	std::string token = "eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJpYXQiOjE1OTUyNjc1MTZ9.cmFuZG9tc2ln";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::none{});

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenRS256FailNoKey) {
	ASSERT_THROW(
		[]() {
			auto verify = jwt::verify().allow_algorithm(jwt::algorithm::rs256("", "", "", "")).with_issuer("auth0");
		}(),
		jwt::error::rsa_exception);
}

TEST(TokenTest, VerifyTokenRS256) {
	std::string token =
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify =
		jwt::verify().allow_algorithm(jwt::algorithm::rs256(rsa_pub_key, rsa_priv_key, "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS256PublicOnly) {
	std::string token =
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::rs256(rsa_pub_key, "", "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS256PrivateOnly) {
	std::string token =
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::rs256("", rsa_priv_key, "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS256Fail) {
	std::string token =
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify =
		jwt::verify().allow_algorithm(jwt::algorithm::rs256(rsa_pub_key_invalid, "", "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenRS512) {
	std::string token =
		"eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify = jwt::verify()
					  .allow_algorithm(jwt::algorithm::rs512(rsa512_pub_key, rsa512_priv_key, "", ""))
					  .with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS512PublicOnly) {
	std::string token =
		"eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::rs512(rsa512_pub_key, "", "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS512PrivateOnly) {
	std::string token =
		"eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify =
		jwt::verify().allow_algorithm(jwt::algorithm::rs512("", rsa512_priv_key, "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS512Fail) {
	std::string token =
		"eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify =
		jwt::verify().allow_algorithm(jwt::algorithm::rs512(rsa_pub_key_invalid, "", "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenHS256) {
	std::string token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::hs256{"secret"}).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);
	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenHS256Fail) {
	std::string token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::hs256{"wrongsecret"}).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);
	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenHS256FailSignatureLength) {
	std::string token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkA";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::hs256{"secret"}).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);
	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyFail) {
	{
		auto token = jwt::create()
						 .set_issuer("auth0")
						 .set_type("JWS")
						 .set_audience("random")
						 .set_payload_claim("typetest", picojson::value(10.0))
						 .sign(jwt::algorithm::none{});

		auto decoded_token = jwt::decode(token);

		{
			auto verify = jwt::verify().allow_algorithm(jwt::algorithm::none{}).with_issuer("auth");
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			auto verify = jwt::verify().allow_algorithm(jwt::algorithm::none{}).with_type("JWT");
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			auto verify = jwt::verify()
							  .allow_algorithm(jwt::algorithm::none{})
							  .with_issuer("auth0")
							  .with_audience(std::set<std::string>{"test"});
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			auto verify =
				jwt::verify().allow_algorithm(jwt::algorithm::none{}).with_issuer("auth0").with_audience("test");
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			auto verify =
				jwt::verify().allow_algorithm(jwt::algorithm::none{}).with_issuer("auth0").with_subject("test");
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			auto verify = jwt::verify()
							  .allow_algorithm(jwt::algorithm::none{})
							  .with_issuer("auth0")
							  .with_claim("myclaim", jwt::claim(std::string("test")));
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			auto verify = jwt::verify()
							  .allow_algorithm(jwt::algorithm::none{})
							  .with_issuer("auth0")
							  .with_claim("typetest", jwt::claim(picojson::value(true)));
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
		{
			jwt::claim object;
			std::istringstream iss{R"({ "test": null })"};
			iss >> object;
			ASSERT_EQ(object.get_type(), jwt::json::type::object);

			auto verify = jwt::verify()
							  .allow_algorithm(jwt::algorithm::none{})
							  .with_issuer("auth0")
							  .with_claim("myclaim", object);
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
	}
	{
		auto token = jwt::create().set_issuer("auth0").set_type("JWS").sign(jwt::algorithm::none{});

		auto decoded_token = jwt::decode(token);

		{
			auto verify =
				jwt::verify().allow_algorithm(jwt::algorithm::none{}).with_issuer("auth0").with_audience("test");
			ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
		}
	}
}

TEST(TokenTest, VerifyTokenES256FailNoKey) {
	ASSERT_THROW(
		[]() {
			auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es256("", "", "", "")).with_issuer("auth0");
		}(),
		jwt::error::ecdsa_exception);
}

TEST(TokenTest, VerifyTokenES256) {
	const std::string token = "eyJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJhdXRoMCJ9.4iVk3-Y0v4RT4_9IaQlp-8dZ_"
							  "4fsTzIylgrPTDLrEvTHBTyVS3tgPbr2_IZfLETtiKRqCg0aQ5sh9eIsTTwB1g";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa256_pub_key, "", "", ""));
	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenES256Fail) {
	const std::string token = "eyJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJhdXRoMCJ9.4iVk3-Y0v4RT4_9IaQlp-8dZ_"
							  "4fsTzIylgrPTDLrEvTHBTyVS3tgPbr2_IZfLETtiKRqCg0aQ5sh9eIsTTwB1g";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa256_pub_key_invalid, "", "", ""));
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenES384) {
	const std::string token =
		"eyJhbGciOiJFUzM4NCIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.nTUwWanmj_K1VZM5it1ES-1FbnmRDL-lH3V_Fem-"
		"AhMur9Q61yZfKIydrpdavkm_SMxEsUGPVoqkpoEsjFjrtzMDs5s9yaFYD_ydiy1dsn9VbcI55voA3XwEcWFiPHri";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es384(ecdsa384_pub_key, "", "", ""));
	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenES384Fail) {
	const std::string token =
		"eyJhbGciOiJFUzM4NCIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.nTUwWanmj_K1VZM5it1ES-1FbnmRDL-lH3V_Fem-"
		"AhMur9Q61yZfKIydrpdavkm_SMxEsUGPVoqkpoEsjFjrtzMDs5s9yaFYD_ydiy1dsn9VbcI55voA3XwEcWFiPHri";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es384(ecdsa384_pub_key_invalid, "", "", ""));
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenES521) {
	const std::string token =
		"eyJhbGciOiJFUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.ASF5hh9_Jyujzm3GRBttoth-3I6lCcwqun9Tt7Ekz9_23BN6-"
		"BFgwKidECWCNc4VINEqFEFdApC2y3YRdkpKX2etAWI7yYudAlxJ7Z17m6GwAoLOGaeNonsaKOe1UnC5W86eoXrCoPRgzsFTpKIb8NiolcYWjIY"
		"-r8gQd7BZ7whaj9Ft";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es512(ecdsa521_pub_key, "", "", ""));
	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenES521Fail) {
	const std::string token =
		"eyJhbGciOiJFUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.ASF5hh9_Jyujzm3GRBttoth-3I6lCcwqun9Tt7Ekz9_23BN6-"
		"BFgwKidECWCNc4VINEqFEFdApC2y3YRdkpKX2etAWI7yYudAlxJ7Z17m6GwAoLOGaeNonsaKOe1UnC5W86eoXrCoPRgzsFTpKIb8NiolcYWjIY"
		"-r8gQd7BZ7whaj9Ft";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es512(ecdsa521_pub_key_invalid, "", "", ""));
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenPS256) {
	std::string token =
		"eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.CJ4XjVWdbV6vXGZkD4GdJbtYc80SN9cmPOqRhZBRzOyDRqTFE"
		"4MsbdKyQuhAWcvuMOjn-24qOTjVMR_P_uTC1uG6WPLcucxZyLnbb56zbKnEklW2SX0mQnCGewr-93a_vDaFT6Cp45MsF_OwFPRCMaS5CJg-"
		"N5KY67UrVSr3s9nkuK9ZTQkyODHfyEUh9F_FhRCATGrb5G7_qHqBYvTvaPUXqzhhpCjN855Tocg7A24Hl0yMwM-XdasucW5xNdKjG_YCkis"
		"HX7ax--JiF5GNYCO61eLFteO4THUg-3Z0r4OlGqlppyWo5X5tjcxOZCvBh7WDWfkxA48KFZPRv0nlKA";

	auto verify =
		jwt::verify().allow_algorithm(jwt::algorithm::ps256(rsa_pub_key, rsa_priv_key, "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenPS256PublicOnly) {
	std::string token =
		"eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.CJ4XjVWdbV6vXGZkD4GdJbtYc80SN9cmPOqRhZBRzOyDRqTFE"
		"4MsbdKyQuhAWcvuMOjn-24qOTjVMR_P_uTC1uG6WPLcucxZyLnbb56zbKnEklW2SX0mQnCGewr-93a_vDaFT6Cp45MsF_OwFPRCMaS5CJg-"
		"N5KY67UrVSr3s9nkuK9ZTQkyODHfyEUh9F_FhRCATGrb5G7_qHqBYvTvaPUXqzhhpCjN855Tocg7A24Hl0yMwM-XdasucW5xNdKjG_YCkis"
		"HX7ax--JiF5GNYCO61eLFteO4THUg-3Z0r4OlGqlppyWo5X5tjcxOZCvBh7WDWfkxA48KFZPRv0nlKA";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::ps256(rsa_pub_key, "", "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenPS256Fail) {
	std::string token =
		"eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.CJ4XjVWdbV6vXGZkD4GdJbtYc80SN9cmPOqRhZBRzOyDRqTFE"
		"4MsbdKyQuhAWcvuMOjn-24qOTjVMR_P_uTC1uG6WPLcucxZyLnbb56zbKnEklW2SX0mQnCGewr-93a_vDaFT6Cp45MsF_OwFPRCMaS5CJg-"
		"N5KY67UrVSr3s9nkuK9ZTQkyODHfyEUh9F_FhRCATGrb5G7_qHqBYvTvaPUXqzhhpCjN855Tocg7A24Hl0yMwM-XdasucW5xNdKjG_YCkis"
		"HX7ax--JiF5GNYCO61eLFteO4THUg-3Z0r4OlGqlppyWo5X5tjcxOZCvBh7WDWfkxA48KFZPRv0nlKA";

	auto verify =
		jwt::verify().allow_algorithm(jwt::algorithm::ps256(rsa_pub_key_invalid, "", "", "")).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenPS256FailNoKey) {
	ASSERT_THROW(
		[]() {
			auto verify = jwt::verify().allow_algorithm(jwt::algorithm::ps256("", "", "", "")).with_issuer("auth0");
		}(),
		jwt::error::rsa_exception);
}

#if !defined(JWT_OPENSSL_1_0_0) && !defined(JWT_OPENSSL_1_1_0)
TEST(TokenTest, VerifyTokenEd25519) {
	const std::string token =
		"eyJhbGciOiJFZERTQSIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.OujgVcO8xQx5xLcAYWENCRU1SCGH5HcX4MX4o6wU3M4"
		"DOnKiNmc0O2AnvQlzr-9cgI4QGQzeC6gz_fgLoesADg";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::ed25519(ed25519_pub_key, "", "", ""));
	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenEd25519Fail) {
	const std::string token =
		"eyJhbGciOiJFZERTQSIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.OujgVcO8xQx5xLcAYWENCRU1SCGH5HcX4MX4o6wU3M4"
		"DOnKiNmc0O2AnvQlzr-9cgI4QGQzeC6gz_fgLoesADg";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::ed25519(ed25519_pub_key_invalid, "", "", ""));
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenEd448) {
	const std::string token =
		"eyJhbGciOiJFZERTQSIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.Aldes9jrXZXxfNjuovqmIZ3r2WF4yVXVr2Q8B8SkAmv"
		"Bsw_3MHs8HtgKeXbqKFYWpHOCtmZJcH-AWMvoY6FCNdQqbESGTkv58O6tFbXDD_nLejWNAOuvcO2LPMySmkVNQUopmQf_HO62Mug1ngepUDE"
		"A";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::ed448(ed448_pub_key, "", "", ""));
	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenEd448Fail) {
	const std::string token =
		"eyJhbGciOiJFZERTQSIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.Aldes9jrXZXxfNjuovqmIZ3r2WF4yVXVr2Q8B8SkAmv"
		"Bsw_3MHs8HtgKeXbqKFYWpHOCtmZJcH-AWMvoY6FCNdQqbESGTkv58O6tFbXDD_nLejWNAOuvcO2LPMySmkVNQUopmQf_HO62Mug1ngepUDE"
		"A";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::ed448(ed448_pub_key_invalid, "", "", ""));
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::error::signature_verification_exception);
}
#endif

struct test_clock {
	jwt::date n;
	jwt::date now() const { return n; }
};

TEST(TokenTest, VerifyTokenExpireFail) {
	auto token = jwt::create().set_expires_at(std::chrono::system_clock::from_time_t(100)).sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify<test_clock, jwt::traits::kazuho_picojson>({std::chrono::system_clock::from_time_t(110)})
					  .allow_algorithm(jwt::algorithm::none{});
	ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_TRUE(!(!ec));
	ASSERT_EQ(ec.category(), jwt::error::token_verification_error_category());
	ASSERT_EQ(ec.value(), static_cast<int>(jwt::error::token_verification_error::token_expired));
}

TEST(TokenTest, VerifyTokenExpire) {
	auto token = jwt::create().set_expires_at(std::chrono::system_clock::from_time_t(100)).sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify<test_clock, jwt::traits::kazuho_picojson>({std::chrono::system_clock::from_time_t(90)})
					  .allow_algorithm(jwt::algorithm::none{});
	ASSERT_NO_THROW(verify.verify(decoded_token));
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_FALSE(!(!ec));
	ASSERT_EQ(ec.value(), 0);
}

TEST(TokenTest, VerifyTokenNBFFail) {
	auto token = jwt::create().set_not_before(std::chrono::system_clock::from_time_t(100)).sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify<test_clock, jwt::traits::kazuho_picojson>({std::chrono::system_clock::from_time_t(90)})
					  .allow_algorithm(jwt::algorithm::none{});
	ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_TRUE(!(!ec));
	ASSERT_EQ(ec.category(), jwt::error::token_verification_error_category());
	ASSERT_EQ(ec.value(), static_cast<int>(jwt::error::token_verification_error::token_expired));
}

TEST(TokenTest, VerifyTokenNBF) {
	auto token = jwt::create().set_not_before(std::chrono::system_clock::from_time_t(100)).sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify<test_clock, jwt::traits::kazuho_picojson>({std::chrono::system_clock::from_time_t(110)})
					  .allow_algorithm(jwt::algorithm::none{});
	ASSERT_NO_THROW(verify.verify(decoded_token));
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_FALSE(!(!ec));
	ASSERT_EQ(ec.value(), 0);
}

TEST(TokenTest, VerifyTokenIATFail) {
	auto token = jwt::create().set_issued_at(std::chrono::system_clock::from_time_t(100)).sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify<test_clock, jwt::traits::kazuho_picojson>({std::chrono::system_clock::from_time_t(90)})
					  .allow_algorithm(jwt::algorithm::none{});
	ASSERT_THROW(verify.verify(decoded_token), jwt::error::token_verification_exception);
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_TRUE(!(!ec));
	ASSERT_EQ(ec.category(), jwt::error::token_verification_error_category());
	ASSERT_EQ(ec.value(), static_cast<int>(jwt::error::token_verification_error::token_expired));
}

TEST(TokenTest, VerifyTokenIAT) {
	auto token = jwt::create().set_issued_at(std::chrono::system_clock::from_time_t(100)).sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify<test_clock, jwt::traits::kazuho_picojson>({std::chrono::system_clock::from_time_t(110)})
					  .allow_algorithm(jwt::algorithm::none{});
	ASSERT_NO_THROW(verify.verify(decoded_token));
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_FALSE(!(!ec));
	ASSERT_EQ(ec.value(), 0);
}

TEST(TokenTest, VerifyTokenType) {
	auto token = jwt::create().set_type("JWS").sign(jwt::algorithm::none{});
	auto decoded_token = jwt::decode(token);

	auto verify = jwt::verify().with_type("jws").allow_algorithm(jwt::algorithm::none{});
	ASSERT_NO_THROW(verify.verify(decoded_token));
	std::error_code ec;
	ASSERT_NO_THROW(verify.verify(decoded_token, ec));
	ASSERT_FALSE(!(!ec));
	ASSERT_EQ(ec.value(), 0);
}

TEST(TokenTest, GetClaimThrows) {
	auto token = "eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0.eyJpc3MiOiJhdXRoMCJ9.";
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(decoded_token.get_header_claim("test"), jwt::error::claim_not_present_exception);
	ASSERT_THROW(decoded_token.get_payload_claim("test"), jwt::error::claim_not_present_exception);
}

TEST(TokenTest, ThrowInvalidKeyLength) {
	// We should throw if passed the wrong size
	ASSERT_THROW(jwt::algorithm::es256(ecdsa384_pub_key, ""), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es256("", ecdsa384_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es256(ecdsa384_pub_key, ecdsa384_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es256(ecdsa521_pub_key, ""), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es256("", ecdsa521_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es256(ecdsa521_pub_key, ecdsa521_priv_key), jwt::error::ecdsa_exception);

	// But also if only one cert has the wrong size
	ASSERT_THROW(jwt::algorithm::es256(ecdsa256_pub_key, ecdsa384_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es256(ecdsa256_pub_key, ecdsa521_priv_key), jwt::error::ecdsa_exception);

	ASSERT_THROW(jwt::algorithm::es384(ecdsa256_pub_key, ""), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es384("", ecdsa256_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es384(ecdsa256_pub_key, ecdsa256_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es384(ecdsa521_pub_key, ""), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es384("", ecdsa521_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es384(ecdsa521_pub_key, ecdsa521_priv_key), jwt::error::ecdsa_exception);

	ASSERT_THROW(jwt::algorithm::es384(ecdsa384_pub_key, ecdsa256_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es384(ecdsa384_pub_key, ecdsa521_priv_key), jwt::error::ecdsa_exception);

	ASSERT_THROW(jwt::algorithm::es512(ecdsa256_pub_key, ""), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es512("", ecdsa256_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es512(ecdsa256_pub_key, ecdsa256_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es512(ecdsa384_pub_key, ""), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es512("", ecdsa384_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es512(ecdsa384_pub_key, ecdsa384_priv_key), jwt::error::ecdsa_exception);

	ASSERT_THROW(jwt::algorithm::es512(ecdsa521_pub_key, ecdsa256_priv_key), jwt::error::ecdsa_exception);
	ASSERT_THROW(jwt::algorithm::es512(ecdsa521_pub_key, ecdsa384_priv_key), jwt::error::ecdsa_exception);

	// Make sure we do not throw if the correct params are passed
	ASSERT_NO_THROW(jwt::algorithm::es256(ecdsa256_pub_key, ecdsa256_priv_key));
	ASSERT_NO_THROW(jwt::algorithm::es384(ecdsa384_pub_key, ecdsa384_priv_key));
	ASSERT_NO_THROW(jwt::algorithm::es512(ecdsa521_pub_key, ecdsa521_priv_key));
}

TEST(TokenTest, MoveDecodedToken) {
	std::string token0 = "eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0.eyJpc3MiOiJhdXRoMCJ9.";
	std::string token1 =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";
	auto decoded_token0 = jwt::decode(token0);
	auto decoded_token1 = jwt::decode(token1);
	decoded_token0 = std::move(decoded_token1);
	ASSERT_EQ(token1, decoded_token0.get_token());

	ASSERT_TRUE(decoded_token0.has_algorithm());
	ASSERT_TRUE(decoded_token0.has_type());
	ASSERT_FALSE(decoded_token0.has_content_type());
	ASSERT_FALSE(decoded_token0.has_key_id());
	ASSERT_TRUE(decoded_token0.has_issuer());
	ASSERT_FALSE(decoded_token0.has_subject());
	ASSERT_FALSE(decoded_token0.has_audience());
	ASSERT_FALSE(decoded_token0.has_expires_at());
	ASSERT_FALSE(decoded_token0.has_not_before());
	ASSERT_FALSE(decoded_token0.has_issued_at());
	ASSERT_FALSE(decoded_token0.has_id());

	ASSERT_EQ("HS256", decoded_token0.get_algorithm());
	ASSERT_EQ("JWS", decoded_token0.get_type());
	ASSERT_EQ("auth0", decoded_token0.get_issuer());
}
