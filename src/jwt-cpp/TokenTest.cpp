#include <gtest/gtest.h>
#include "include/jwt-cpp/jwt.h"

namespace {
	extern std::string rsa_priv_key;
	extern std::string rsa_pub_key;
	extern std::string rsa_pub_key_invalid;
	extern std::string rsa512_priv_key;
	extern std::string rsa512_pub_key;
	extern std::string rsa512_pub_key_invalid;
	extern std::string ecdsa_priv_key;
	extern std::string ecdsa_pub_key;
	extern std::string ecdsa_pub_key_invalid;
}

TEST(TokenTest, DecodeToken) {
	std::string token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";
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
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::none{});
	ASSERT_EQ("eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0.eyJpc3MiOiJhdXRoMCJ9.", token);
}

TEST(TokenTest, CreateTokenHS256) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::hs256{"secret"});
	ASSERT_EQ("eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE", token);
}

TEST(TokenTest, CreateTokenRS256) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::rs256(rsa_pub_key, rsa_priv_key, "", ""));

	ASSERT_EQ(
		"eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ", token);
}

TEST(TokenTest, CreateTokenRS512) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::rs512(rsa512_pub_key, rsa512_priv_key, "", ""));

	ASSERT_EQ(
		"eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZSSQyLKvI0"
		"TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4GB"
		"hfGgejPBCBlGrQtqFGFdHHOjNHY", token);
}

TEST(TokenTest, CreateTokenPS256) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::ps256(rsa_pub_key, rsa_priv_key, "", ""));

	// TODO: Find a better way to check if generated signature is valid
	// Can't do simple check for equal since pss adds random salt.
}

TEST(TokenTest, CreateTokenPS384) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::ps384(rsa_pub_key, rsa_priv_key, "", ""));

	// TODO: Find a better way to check if generated signature is valid
	// Can't do simple check for equal since pss adds random salt.
}

TEST(TokenTest, CreateTokenPS512) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::ps512(rsa_pub_key, rsa_priv_key, "", ""));

	// TODO: Find a better way to check if generated signature is valid
	// Can't do simple check for equal since pss adds random salt.
}

TEST(TokenTest, CreateTokenES256) {
	
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::es256("", ecdsa_priv_key, "", ""));

	auto decoded = jwt::decode(token);

	ASSERT_THROW(jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa_pub_key_invalid, "", "", "")).verify(decoded), jwt::signature_verification_exception);
	ASSERT_NO_THROW(jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa_pub_key, "", "", "")).verify(decoded));
}

TEST(TokenTest, CreateTokenES256NoPrivate) {
	
	ASSERT_THROW([](){
		auto token = jwt::create()
			.set_issuer("auth0")
			.set_type("JWS")
			.sign(jwt::algorithm::es256(ecdsa_pub_key, "", "", ""));
	}(), jwt::signature_generation_exception);
}

TEST(TokenTest, VerifyTokenRS256) {
	std::string token = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::rs256(rsa_pub_key, rsa_priv_key, "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS256PublicOnly) {
	std::string token = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::rs256(rsa_pub_key, "", "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS256Fail) {
	std::string token = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.VA2i1ui1cnoD6I3wnji1WAVCf29EekysvevGrT2GXqK1dDMc8"
		"HAZCTQxa1Q8NppnpYV-hlqxh-X3Bb0JOePTGzjynpNZoJh2aHZD-GKpZt7OO1Zp8AFWPZ3p8Cahq8536fD8RiBES9jRsvChZvOqA7gMcFc4"
		"YD0iZhNIcI7a654u5yPYyTlf5kjR97prCf_OXWRn-bYY74zna4p_bP9oWCL4BkaoRcMxi-IR7kmVcCnvbYqyIrKloXP2qPO442RBGqU7Ov9"
		"sGQxiVqtRHKXZR9RbfvjrErY1KGiCp9M5i2bsUHadZEY44FE2jiOmx-uc2z5c05CCXqVSpfCjWbh9gQ";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::rs256(rsa_pub_key_invalid, "", "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenRS512) {
	std::string token = "eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::rs512(rsa512_pub_key, rsa512_priv_key, "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS512PublicOnly) {
	std::string token = "eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::rs512(rsa512_pub_key, "", "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenRS512Fail) {
	std::string token = "eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.GZhnjtsvBl2_KDSxg4JW6xnmNjr2mWhYSZ"
		"SSQyLKvI0TK86sJKchkt_HDy2IC5l5BGRhq_Xv9pHdA1umidQZG3a7gWvHsujqybCBgBraMTd1wJrCl4QxFg2RYHhHbRqb9BnPJgFD_vryd4"
		"GBhfGgejPBCBlGrQtqFGFdHHOjNHY";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::rs512(rsa_pub_key_invalid, "", "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenHS256) {
	std::string token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::hs256{ "secret" })
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);
	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyFail) {
	auto token = jwt::create()
		.set_issuer("auth0")
		.set_type("JWS")
		.sign(jwt::algorithm::none{});

	auto decoded_token = jwt::decode(token);

	{
		auto verify = jwt::verify()
			.allow_algorithm(jwt::algorithm::none{})
			.with_issuer("auth");
		ASSERT_THROW(verify.verify(decoded_token), jwt::token_verification_exception);
	}
	{
		auto verify = jwt::verify()
			.allow_algorithm(jwt::algorithm::none{})
			.with_issuer("auth0")
			.with_audience({ "test" });
		ASSERT_THROW(verify.verify(decoded_token), jwt::token_verification_exception);
	}
	{
		auto verify = jwt::verify()
			.allow_algorithm(jwt::algorithm::none{})
			.with_issuer("auth0")
			.with_subject("test");
		ASSERT_THROW(verify.verify(decoded_token), jwt::token_verification_exception);
	}
	{
		auto verify = jwt::verify()
			.allow_algorithm(jwt::algorithm::none{})
			.with_issuer("auth0")
			.with_claim("myclaim", jwt::claim(std::string("test")));
		ASSERT_THROW(verify.verify(decoded_token), jwt::token_verification_exception);
	}
}

TEST(TokenTest, VerifyTokenES256) {
	const std::string token = "eyJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJhdXRoMCJ9.4iVk3-Y0v4RT4_9IaQlp-8dZ_4fsTzIylgrPTDLrEvTHBTyVS3tgPbr2_IZfLETtiKRqCg0aQ5sh9eIsTTwB1g";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::es256(ecdsa_pub_key, "", "", ""));
	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenES256Fail) {
	const std::string token = "eyJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJhdXRoMCJ9.4iVk3-Y0v4RT4_9IaQlp-8dZ_4fsTzIylgrPTDLrEvTHBTyVS3tgPbr2_IZfLETtiKRqCg0aQ5sh9eIsTTwB1g";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::es256(ecdsa_pub_key_invalid, "", "", ""));
	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::signature_verification_exception);
}

TEST(TokenTest, VerifyTokenPS256) {
	std::string token = "eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.CJ4XjVWdbV6vXGZkD4GdJbtYc80SN9cmPOqRhZBRzOyDRqTFE"
		"4MsbdKyQuhAWcvuMOjn-24qOTjVMR_P_uTC1uG6WPLcucxZyLnbb56zbKnEklW2SX0mQnCGewr-93a_vDaFT6Cp45MsF_OwFPRCMaS5CJg-"
		"N5KY67UrVSr3s9nkuK9ZTQkyODHfyEUh9F_FhRCATGrb5G7_qHqBYvTvaPUXqzhhpCjN855Tocg7A24Hl0yMwM-XdasucW5xNdKjG_YCkis"
		"HX7ax--JiF5GNYCO61eLFteO4THUg-3Z0r4OlGqlppyWo5X5tjcxOZCvBh7WDWfkxA48KFZPRv0nlKA";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::ps256(rsa_pub_key, rsa_priv_key, "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenPS256PublicOnly) {
	std::string token = "eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.CJ4XjVWdbV6vXGZkD4GdJbtYc80SN9cmPOqRhZBRzOyDRqTFE"
		"4MsbdKyQuhAWcvuMOjn-24qOTjVMR_P_uTC1uG6WPLcucxZyLnbb56zbKnEklW2SX0mQnCGewr-93a_vDaFT6Cp45MsF_OwFPRCMaS5CJg-"
		"N5KY67UrVSr3s9nkuK9ZTQkyODHfyEUh9F_FhRCATGrb5G7_qHqBYvTvaPUXqzhhpCjN855Tocg7A24Hl0yMwM-XdasucW5xNdKjG_YCkis"
		"HX7ax--JiF5GNYCO61eLFteO4THUg-3Z0r4OlGqlppyWo5X5tjcxOZCvBh7WDWfkxA48KFZPRv0nlKA";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::ps256(rsa_pub_key, "", "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	verify.verify(decoded_token);
}

TEST(TokenTest, VerifyTokenPS256Fail) {
	std::string token = "eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.CJ4XjVWdbV6vXGZkD4GdJbtYc80SN9cmPOqRhZBRzOyDRqTFE"
		"4MsbdKyQuhAWcvuMOjn-24qOTjVMR_P_uTC1uG6WPLcucxZyLnbb56zbKnEklW2SX0mQnCGewr-93a_vDaFT6Cp45MsF_OwFPRCMaS5CJg-"
		"N5KY67UrVSr3s9nkuK9ZTQkyODHfyEUh9F_FhRCATGrb5G7_qHqBYvTvaPUXqzhhpCjN855Tocg7A24Hl0yMwM-XdasucW5xNdKjG_YCkis"
		"HX7ax--JiF5GNYCO61eLFteO4THUg-3Z0r4OlGqlppyWo5X5tjcxOZCvBh7WDWfkxA48KFZPRv0nlKA";

	auto verify = jwt::verify()
		.allow_algorithm(jwt::algorithm::ps256(rsa_pub_key_invalid, "", "", ""))
		.with_issuer("auth0");

	auto decoded_token = jwt::decode(token);

	ASSERT_THROW(verify.verify(decoded_token), jwt::signature_verification_exception);
}

namespace {
	std::string rsa_priv_key = R"(-----BEGIN PRIVATE KEY-----
MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC4ZtdaIrd1BPIJ
tfnF0TjIK5inQAXZ3XlCrUlJdP+XHwIRxdv1FsN12XyMYO/6ymLmo9ryoQeIrsXB
XYqlET3zfAY+diwCb0HEsVvhisthwMU4gZQu6TYW2s9LnXZB5rVtcBK69hcSlA2k
ZudMZWxZcj0L7KMfO2rIvaHw/qaVOE9j0T257Z8Kp2CLF9MUgX0ObhIsdumFRLaL
DvDUmBPr2zuh/34j2XmWwn1yjN/WvGtdfhXW79Ki1S40HcWnygHgLV8sESFKUxxQ
mKvPUTwDOIwLFL5WtE8Mz7N++kgmDcmWMCHc8kcOIu73Ta/3D4imW7VbKgHZo9+K
3ESFE3RjAgMBAAECggEBAJTEIyjMqUT24G2FKiS1TiHvShBkTlQdoR5xvpZMlYbN
tVWxUmrAGqCQ/TIjYnfpnzCDMLhdwT48Ab6mQJw69MfiXwc1PvwX1e9hRscGul36
ryGPKIVQEBsQG/zc4/L2tZe8ut+qeaK7XuYrPp8bk/X1e9qK5m7j+JpKosNSLgJj
NIbYsBkG2Mlq671irKYj2hVZeaBQmWmZxK4fw0Istz2WfN5nUKUeJhTwpR+JLUg4
ELYYoB7EO0Cej9UBG30hbgu4RyXA+VbptJ+H042K5QJROUbtnLWuuWosZ5ATldwO
u03dIXL0SH0ao5NcWBzxU4F2sBXZRGP2x/jiSLHcqoECgYEA4qD7mXQpu1b8XO8U
6abpKloJCatSAHzjgdR2eRDRx5PMvloipfwqA77pnbjTUFajqWQgOXsDTCjcdQui
wf5XAaWu+TeAVTytLQbSiTsBhrnoqVrr3RoyDQmdnwHT8aCMouOgcC5thP9vQ8Us
rVdjvRRbnJpg3BeSNimH+u9AHgsCgYEA0EzcbOltCWPHRAY7B3Ge/AKBjBQr86Kv
TdpTlxePBDVIlH+BM6oct2gaSZZoHbqPjbq5v7yf0fKVcXE4bSVgqfDJ/sZQu9Lp
PTeV7wkk0OsAMKk7QukEpPno5q6tOTNnFecpUhVLLlqbfqkB2baYYwLJR3IRzboJ
FQbLY93E8gkCgYB+zlC5VlQbbNqcLXJoImqItgQkkuW5PCgYdwcrSov2ve5r/Acz
FNt1aRdSlx4176R3nXyibQA1Vw+ztiUFowiP9WLoM3PtPZwwe4bGHmwGNHPIfwVG
m+exf9XgKKespYbLhc45tuC08DATnXoYK7O1EnUINSFJRS8cezSI5eHcbQKBgQDC
PgqHXZ2aVftqCc1eAaxaIRQhRmY+CgUjumaczRFGwVFveP9I6Gdi+Kca3DE3F9Pq
PKgejo0SwP5vDT+rOGHN14bmGJUMsX9i4MTmZUZ5s8s3lXh3ysfT+GAhTd6nKrIE
kM3Nh6HWFhROptfc6BNusRh1kX/cspDplK5x8EpJ0QKBgQDWFg6S2je0KtbV5PYe
RultUEe2C0jYMDQx+JYxbPmtcopvZQrFEur3WKVuLy5UAy7EBvwMnZwIG7OOohJb
vkSpADK6VPn9lbqq7O8cTedEHttm6otmLt8ZyEl3hZMaL3hbuRj6ysjmoFKx6CrX
rK0/Ikt5ybqUzKCMJZg2VKGTxg==
-----END PRIVATE KEY-----)";
	std::string rsa_pub_key = R"(-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuGbXWiK3dQTyCbX5xdE4
yCuYp0AF2d15Qq1JSXT/lx8CEcXb9RbDddl8jGDv+spi5qPa8qEHiK7FwV2KpRE9
83wGPnYsAm9BxLFb4YrLYcDFOIGULuk2FtrPS512Qea1bXASuvYXEpQNpGbnTGVs
WXI9C+yjHztqyL2h8P6mlThPY9E9ue2fCqdgixfTFIF9Dm4SLHbphUS2iw7w1JgT
69s7of9+I9l5lsJ9cozf1rxrXX4V1u/SotUuNB3Fp8oB4C1fLBEhSlMcUJirz1E8
AziMCxS+VrRPDM+zfvpIJg3JljAh3PJHDiLu902v9w+Iplu1WyoB2aPfitxEhRN0
YwIDAQAB
-----END PUBLIC KEY-----)";
	std::string rsa_pub_key_invalid = R"(-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxzYuc22QSst/dS7geYYK
5l5kLxU0tayNdixkEQ17ix+CUcUbKIsnyftZxaCYT46rQtXgCaYRdJcbB3hmyrOa
vkhTpX79xJZnQmfuamMbZBqitvscxW9zRR9tBUL6vdi/0rpoUwPMEh8+Bw7CgYR0
FK0DhWYBNDfe9HKcyZEv3max8Cdq18htxjEsdYO0iwzhtKRXomBWTdhD5ykd/fAC
VTr4+KEY+IeLvubHVmLUhbE5NgWXxrRpGasDqzKhCTmsa2Ysf712rl57SlH0Wz/M
r3F7aM9YpErzeYLrl0GhQr9BVJxOvXcVd4kmY+XkiCcrkyS1cnghnllh+LCwQu1s
YwIDAQAB
-----END PUBLIC KEY-----)";
	std::string rsa512_priv_key = R"(-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQDdlatRjRjogo3WojgGHFHYLugdUWAY9iR3fy4arWNA1KoS8kVw
33cJibXr8bvwUAUparCwlvdbH6dvEOfou0/gCFQsHUfQrSDv+MuSUMAe8jzKE4qW
+jK+xQU9a03GUnKHkkle+Q0pX/g6jXZ7r1/xAK5Do2kQ+X5xK9cipRgEKwIDAQAB
AoGAD+onAtVye4ic7VR7V50DF9bOnwRwNXrARcDhq9LWNRrRGElESYYTQ6EbatXS
3MCyjjX2eMhu/aF5YhXBwkppwxg+EOmXeh+MzL7Zh284OuPbkglAaGhV9bb6/5Cp
uGb1esyPbYW+Ty2PC0GSZfIXkXs76jXAu9TOBvD0ybc2YlkCQQDywg2R/7t3Q2OE
2+yo382CLJdrlSLVROWKwb4tb2PjhY4XAwV8d1vy0RenxTB+K5Mu57uVSTHtrMK0
GAtFr833AkEA6avx20OHo61Yela/4k5kQDtjEf1N0LfI+BcWZtxsS3jDM3i1Hp0K
Su5rsCPb8acJo5RO26gGVrfAsDcIXKC+bQJAZZ2XIpsitLyPpuiMOvBbzPavd4gY
6Z8KWrfYzJoI/Q9FuBo6rKwl4BFoToD7WIUS+hpkagwWiz+6zLoX1dbOZwJACmH5
fSSjAkLRi54PKJ8TFUeOP15h9sQzydI8zJU+upvDEKZsZc/UhT/SySDOxQ4G/523
Y0sz/OZtSWcol/UMgQJALesy++GdvoIDLfJX5GBQpuFgFenRiRDabxrE9MNUZ2aP
FaFp+DyAe+b4nDwuJaW2LURbr8AEZga7oQj0uYxcYw==
-----END RSA PRIVATE KEY-----)";
	std::string rsa512_pub_key = R"(-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDdlatRjRjogo3WojgGHFHYLugd
UWAY9iR3fy4arWNA1KoS8kVw33cJibXr8bvwUAUparCwlvdbH6dvEOfou0/gCFQs
HUfQrSDv+MuSUMAe8jzKE4qW+jK+xQU9a03GUnKHkkle+Q0pX/g6jXZ7r1/xAK5D
o2kQ+X5xK9cipRgEKwIDAQAB
-----END PUBLIC KEY-----)";
	std::string rsa512_pub_key_invalid = R"(-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxzYuc22QSst/dS7geYYK
5l5kLxU0tayNdixkEQ17ix+CUcUbKIsnyftZxaCYT46rQtXgCaYRdJcbB3hmyrOa
vkhTpX79xJZnQmfuamMbZBqitvscxW9zRR9tBUL6vdi/0rpoUwPMEh8+Bw7CgYR0
FK0DhWYBNDfe9HKcyZEv3max8Cdq18htxjEsdYO0iwzhtKRXomBWTdhD5ykd/fAC
VTr4+KEY+IeLvubHVmLUhbE5NgWXxrRpGasDqzKhCTmsa2Ysf712rl57SlH0Wz/M
r3F7aM9YpErzeYLrl0GhQr9BVJxOvXcVd4kmY+XkiCcrkyS1cnghnllh+LCwQu1s
YwIDAQAB
-----END PUBLIC KEY-----)";
	std::string ecdsa_priv_key = R"(-----BEGIN PRIVATE KEY-----
MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQgPGJGAm4X1fvBuC1z
SpO/4Izx6PXfNMaiKaS5RUkFqEGhRANCAARCBvmeksd3QGTrVs2eMrrfa7CYF+sX
sjyGg+Bo5mPKGH4Gs8M7oIvoP9pb/I85tdebtKlmiCZHAZE5w4DfJSV6
-----END PRIVATE KEY-----)";
	std::string ecdsa_pub_key = R"(-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEQgb5npLHd0Bk61bNnjK632uwmBfr
F7I8hoPgaOZjyhh+BrPDO6CL6D/aW/yPObXXm7SpZogmRwGROcOA3yUleg==
-----END PUBLIC KEY-----)";
	std::string ecdsa_pub_key_invalid = R"(-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEoBUyo8CQAFPeYPvv78ylh5MwFZjT
CLQeb042TjiMJxG+9DLFmRSMlBQ9T/RsLLc+PmpB1+7yPAR+oR5gZn3kJQ==
-----END PUBLIC KEY-----)";
}
