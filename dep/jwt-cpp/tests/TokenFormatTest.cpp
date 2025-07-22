#include "jwt-cpp/jwt.h"
#include <gtest/gtest.h>

TEST(TokenFormatTest, MissingDot) {
	ASSERT_THROW(jwt::decode("eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0.eyJpc3MiOiJhdXRoMCJ9"), std::invalid_argument);
	ASSERT_THROW(jwt::decode("eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0eyJpc3MiOiJhdXRoMCJ9."), std::invalid_argument);
	ASSERT_THROW(jwt::decode("eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0eyJpc3MiOiJhdXRoMCJ9"), std::invalid_argument);
}

TEST(TokenFormatTest, InvalidChar) {
	ASSERT_THROW(jwt::decode("eyJhbGciOiJub25lIiwidHlwIjoiSldTIn0().eyJpc3MiOiJhdXRoMCJ9."), std::runtime_error);
}

TEST(TokenFormatTest, InvalidJSON) {
	ASSERT_THROW(jwt::decode("YXsiYWxnIjoibm9uZSIsInR5cCI6IkpXUyJ9YQ.eyJpc3MiOiJhdXRoMCJ9."), std::runtime_error);
}
