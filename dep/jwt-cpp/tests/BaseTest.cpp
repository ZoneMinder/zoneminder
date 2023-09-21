#include "jwt-cpp/base.h"
#include <gtest/gtest.h>

TEST(BaseTest, Base64Index) {
	ASSERT_EQ(0, jwt::alphabet::index(jwt::alphabet::base64::data(), 'A'));
	ASSERT_EQ(32, jwt::alphabet::index(jwt::alphabet::base64::data(), 'g'));
	ASSERT_EQ(62, jwt::alphabet::index(jwt::alphabet::base64::data(), '+'));
}

TEST(BaseTest, Base64URLIndex) {
	ASSERT_EQ(0, jwt::alphabet::index(jwt::alphabet::base64url::data(), 'A'));
	ASSERT_EQ(32, jwt::alphabet::index(jwt::alphabet::base64url::data(), 'g'));
	ASSERT_EQ(62, jwt::alphabet::index(jwt::alphabet::base64url::data(), '-'));
}

TEST(BaseTest, BaseDetailsCountPadding) {
	using jwt::base::details::padding;
	ASSERT_EQ(padding{}, jwt::base::details::count_padding("ABC", {"~"}));
	ASSERT_EQ((padding{3, 3}), jwt::base::details::count_padding("ABC~~~", {"~"}));
	ASSERT_EQ((padding{5, 5}), jwt::base::details::count_padding("ABC~~~~~", {"~"}));

	ASSERT_EQ(padding{}, jwt::base::details::count_padding("ABC", {"~", "!"}));
	ASSERT_EQ((padding{1, 1}), jwt::base::details::count_padding("ABC!", {"~", "!"}));
	ASSERT_EQ((padding{1, 1}), jwt::base::details::count_padding("ABC~", {"~", "!"}));
	ASSERT_EQ((padding{3, 3}), jwt::base::details::count_padding("ABC~~!", {"~", "!"}));
	ASSERT_EQ((padding{3, 3}), jwt::base::details::count_padding("ABC!~~", {"~", "!"}));
	ASSERT_EQ((padding{5, 5}), jwt::base::details::count_padding("ABC~~!~~", {"~", "!"}));

	ASSERT_EQ((padding{2, 6}), jwt::base::details::count_padding("MTIzNA%3d%3d", {"%3d", "%3D"}));
	ASSERT_EQ((padding{2, 6}), jwt::base::details::count_padding("MTIzNA%3d%3D", {"%3d", "%3D"}));
	ASSERT_EQ((padding{2, 6}), jwt::base::details::count_padding("MTIzNA%3D%3d", {"%3d", "%3D"}));
	ASSERT_EQ((padding{2, 6}), jwt::base::details::count_padding("MTIzNA%3D%3D", {"%3d", "%3D"}));

	// Some fake scenarios

	ASSERT_EQ(padding{}, jwt::base::details::count_padding("", {"~"}));
	ASSERT_EQ(padding{}, jwt::base::details::count_padding("ABC", {"~", "~~!"}));
	ASSERT_EQ(padding{}, jwt::base::details::count_padding("ABC!", {"~", "~~!"}));
	ASSERT_EQ((padding{1, 1}), jwt::base::details::count_padding("ABC~", {"~", "~~!"}));
	ASSERT_EQ((padding{1, 3}), jwt::base::details::count_padding("ABC~~!", {"~", "~~!"}));
	ASSERT_EQ((padding{2, 2}), jwt::base::details::count_padding("ABC!~~", {"~", "~~!"}));
	ASSERT_EQ((padding{3, 5}), jwt::base::details::count_padding("ABC~~!~~", {"~", "~~!"}));
	ASSERT_EQ(padding{}, jwt::base::details::count_padding("ABC~~!~~", {}));
}

TEST(BaseTest, Base64Decode) {
	ASSERT_EQ("1", jwt::base::decode<jwt::alphabet::base64>("MQ=="));
	ASSERT_EQ("12", jwt::base::decode<jwt::alphabet::base64>("MTI="));
	ASSERT_EQ("123", jwt::base::decode<jwt::alphabet::base64>("MTIz"));
	ASSERT_EQ("1234", jwt::base::decode<jwt::alphabet::base64>("MTIzNA=="));
}

TEST(BaseTest, Base64DecodeURL) {
	ASSERT_EQ("1", jwt::base::decode<jwt::alphabet::base64url>("MQ%3d%3d"));
	ASSERT_EQ("12", jwt::base::decode<jwt::alphabet::base64url>("MTI%3d"));
	ASSERT_EQ("123", jwt::base::decode<jwt::alphabet::base64url>("MTIz"));
	ASSERT_EQ("1234", jwt::base::decode<jwt::alphabet::base64url>("MTIzNA%3d%3d"));
}

TEST(BaseTest, Base64DecodeURLCaseInsensitive) {
	ASSERT_EQ("1", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MQ%3d%3d"));
	ASSERT_EQ("1", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MQ%3D%3d"));
	ASSERT_EQ("1", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MQ%3d%3D"));
	ASSERT_EQ("12", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MTI%3d"));
	ASSERT_EQ("123", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MTIz"));
	ASSERT_EQ("1234", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MTIzNA%3d%3d"));
	ASSERT_EQ("1234", jwt::base::decode<jwt::alphabet::helper::base64url_percent_encoding>("MTIzNA%3D%3D"));
}

TEST(BaseTest, Base64Encode) {
	ASSERT_EQ("MQ==", jwt::base::encode<jwt::alphabet::base64>("1"));
	ASSERT_EQ("MTI=", jwt::base::encode<jwt::alphabet::base64>("12"));
	ASSERT_EQ("MTIz", jwt::base::encode<jwt::alphabet::base64>("123"));
	ASSERT_EQ("MTIzNA==", jwt::base::encode<jwt::alphabet::base64>("1234"));
}

TEST(BaseTest, Base64EncodeURL) {
	ASSERT_EQ("MQ%3d%3d", jwt::base::encode<jwt::alphabet::base64url>("1"));
	ASSERT_EQ("MTI%3d", jwt::base::encode<jwt::alphabet::base64url>("12"));
	ASSERT_EQ("MTIz", jwt::base::encode<jwt::alphabet::base64url>("123"));
	ASSERT_EQ("MTIzNA%3d%3d", jwt::base::encode<jwt::alphabet::base64url>("1234"));
}

TEST(BaseTest, Base64Pad) {
	ASSERT_EQ("MQ==", jwt::base::pad<jwt::alphabet::base64>("MQ"));
	ASSERT_EQ("MTI=", jwt::base::pad<jwt::alphabet::base64>("MTI"));
	ASSERT_EQ("MTIz", jwt::base::pad<jwt::alphabet::base64>("MTIz"));
	ASSERT_EQ("MTIzNA==", jwt::base::pad<jwt::alphabet::base64>("MTIzNA"));
}

TEST(BaseTest, Base64PadURL) {
	ASSERT_EQ("MQ%3d%3d", jwt::base::pad<jwt::alphabet::base64url>("MQ"));
	ASSERT_EQ("MTI%3d", jwt::base::pad<jwt::alphabet::base64url>("MTI"));
	ASSERT_EQ("MTIz", jwt::base::pad<jwt::alphabet::base64url>("MTIz"));
	ASSERT_EQ("MTIzNA%3d%3d", jwt::base::pad<jwt::alphabet::base64url>("MTIzNA"));
}

TEST(BaseTest, Base64Trim) {
	ASSERT_EQ("MQ", jwt::base::trim<jwt::alphabet::base64>("MQ=="));
	ASSERT_EQ("MTI", jwt::base::trim<jwt::alphabet::base64>("MTI="));
	ASSERT_EQ("MTIz", jwt::base::trim<jwt::alphabet::base64>("MTIz"));
	ASSERT_EQ("MTIzNA", jwt::base::trim<jwt::alphabet::base64>("MTIzNA=="));
}

TEST(BaseTest, Base64TrimURL) {
	ASSERT_EQ("MQ", jwt::base::trim<jwt::alphabet::base64url>("MQ%3d%3d"));
	ASSERT_EQ("MTI", jwt::base::trim<jwt::alphabet::base64url>("MTI%3d"));
	ASSERT_EQ("MTIz", jwt::base::trim<jwt::alphabet::base64url>("MTIz"));
	ASSERT_EQ("MTIzNA", jwt::base::trim<jwt::alphabet::base64url>("MTIzNA%3d%3d"));
}
