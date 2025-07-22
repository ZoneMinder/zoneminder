#include "zm_crypt.h"

#include "zm_logger.h"
#include "zm_utils.h"
#include "BCrypt.hpp"
#include <algorithm>
#include <cstring>
#include <random>

#if HAVE_LIBJWT
#include <jwt.h>
#else
#include <jwt-cpp/jwt.h>
#endif

// returns username if valid, "" if not
#if HAVE_LIBJWT
std::pair <std::string, unsigned int> verifyToken(const std::string &jwt_token_str, const std::string &key) {
  std::string username = "";
  unsigned int token_issued_at = 0;
  int err = 0;
  jwt_t *jwt = nullptr;

  err = jwt_new(&jwt);
  if (err) {
    Error("Unable to Allocate JWT object");
    return std::make_pair("", 0);
  }

  err = jwt_set_alg(jwt, JWT_ALG_HS256, (const unsigned char*)key.c_str(), key.length());
  if (err) {
    jwt_free(jwt);
    Error("Error setting Algorithm for JWT decode");
    return std::make_pair("", 0);
  }

  err = jwt_decode(&jwt, jwt_token_str.c_str(),
                   reinterpret_cast<const unsigned char *>(key.c_str()), key.length());
  if (err) {
    jwt_free(jwt);
    Error("Could not decode JWT");
    return std::make_pair("", 0);
  }

  const char *c_type = jwt_get_grant(jwt, (const char*)"type");
  if (!c_type) {
    jwt_free(jwt);
    Error("Missing token type. This should not happen");
    return std::make_pair("", 0);
  } else if (std::string(c_type) != "access") {
    jwt_free(jwt);
    Error("Only access tokens are allowed. Please do not use refresh tokens");
    return std::make_pair("", 0);
  }

  const char *c_username = jwt_get_grant(jwt, (const char*)"user");
  if (!c_username) {
    jwt_free(jwt);
    Error("User not found in claim");
    return std::make_pair("", 0);
  }

  username = std::string(c_username);
  Debug(1, "Got %s as user claim from token", username.c_str());

  token_issued_at = (unsigned int)jwt_get_grant_int(jwt, "iat");
  if (errno == ENOENT) {
    jwt_free(jwt);
    Error("IAT not found in claim. This should not happen");
    return std::make_pair("", 0);
  }

  Debug(1, "Got IAT token=%u", token_issued_at);
  jwt_free(jwt);
  return std::make_pair(username, token_issued_at);
}
#else // HAVE_LIBJWT
std::pair <std::string, unsigned int> verifyToken(const std::string &jwt_token_str, const std::string &key) {
  std::string username = "";
  unsigned int token_issued_at = 0;
  try {
    // is it decodable?
    auto decoded = jwt::decode(jwt_token_str);
    auto verifier = jwt::verify()
                    .allow_algorithm(jwt::algorithm::hs256{ key })
                    .with_issuer("ZoneMinder");

    // signature verified?
    verifier.verify(decoded);

    // make sure it has fields we need
    if (decoded.has_payload_claim("type")) {
      std::string type = decoded.get_payload_claim("type").as_string();
      if (type != "access") {
        Error("Only access tokens are allowed. Please do not use refresh tokens");
        return std::make_pair("", 0);
      }
    } else {
      // something is wrong. All ZM tokens have type
      Error("Missing token type. This should not happen");
      return std::make_pair("", 0);
    }

    if (decoded.has_payload_claim("user")) {
      username = decoded.get_payload_claim("user").as_string();
      Debug(1, "Got %s as user claim from token", username.c_str());
    } else {
      Error("User not found in claim");
      return std::make_pair("", 0);
    }

    if (decoded.has_payload_claim("iat")) {
      token_issued_at = (unsigned int) (decoded.get_payload_claim("iat").as_integer());
      Debug(1, "Got IAT token=%u", token_issued_at);
    } else {
      Error("IAT not found in claim. This should not happen");
      return std::make_pair("", 0);
    }
  } // try
  catch (const std::exception &e) {
    Error("Unable to verify token: %s", e.what());
    return std::make_pair("", 0);
  } catch (...) {
    Error("unknown exception");
    return std::make_pair("", 0);
  }
  return std::make_pair(username, token_issued_at);
}
#endif // HAVE_LIBJWT

bool verifyPassword(const char *username, const char *input_password, const char *db_password_hash) {
  using namespace zm::crypto;

  bool password_correct = false;
  if ( strlen(db_password_hash) < 4 ) {
    // actually, should be more, but this is min. for next code
    Error("DB Password is too short or invalid to check");
    return false;
  }
  if ( db_password_hash[0] == '*' ) {
    // MYSQL PASSWORD
    Debug(1, "%s is using an SHA1 encoded password", username);

    SHA1::Digest digest = SHA1::GetDigestOf(SHA1::GetDigestOf(input_password));
    std::string hex_digest = '*' + StringToUpper(ByteArrayToHexString(digest));

    Debug(1, "Computed password_hash: %s, stored password_hash: %s", hex_digest.c_str(), db_password_hash);
    password_correct = (strcmp(db_password_hash, hex_digest.c_str()) == 0);
  } else if (
    (db_password_hash[0] == '$')
    &&
    (db_password_hash[1] == '2')
    &&
    (db_password_hash[3] == '$')
  ) {
    // BCRYPT
    Debug(1, "%s is using a bcrypt encoded password", username);
    BCrypt bcrypt;
    password_correct = bcrypt.validatePassword(std::string(input_password), std::string(db_password_hash));
  } else if ( strncmp(db_password_hash, "-ZM-",4) == 0 ) {
    Error("Authentication failed - migration of password not complete. Please log into web console for this user and retry this operation");
    return false;
  } else {
    Warning("%s is using a plain text (not recommended) or scheme not understood", username);
    password_correct = (strcmp(input_password, db_password_hash) == 0);
  }

  return password_correct;
}

std::string generateKey(const int length) {

  const std::string lookup = "0123456789ABCDEF";

  std::random_device rnd;
  std::mt19937 rng(rnd());
  std::uniform_int_distribution<> genDigit(0,15);
  std::string keyBuffer (length, '0');
  for ( int i = 0; i < length; i++ ) {
    keyBuffer[i] = lookup[genDigit(rng)];
  }
  return keyBuffer;


}
