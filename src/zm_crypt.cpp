#include "zm.h"
# include "zm_crypt.h"
#include <algorithm>





std::string createToken() {
  std::string token = jwt::create()
                        .set_issuer("auth0")
                        //.set_expires_at(jwt::date(expiresAt))
                        //.set_issued_at(jwt::date(tp))
                        //.set_issued_at(jwt::date(std::chrono::system_clock::now()))
                        //.set_expires_at(jwt::date(std::chrono::system_clock::now()+std::chrono::seconds{EXPIRY}))
                        .sign(jwt::algorithm::hs256{"secret"});
  return token;
}

bool verifyPassword(const char *username, const char *input_password, const char *db_password_hash) {
  bool password_correct = false;
  Info ("JWT created as %s",createToken().c_str());
  if (strlen(db_password_hash ) < 4) {
    // actually, shoud be more, but this is min. for next code
    Error ("DB Password is too short or invalid to check");
    return false;
  }
  if (db_password_hash[0] == '*') {
    // MYSQL PASSWORD
    Info ("%s is using an MD5 encoded password", username);
    unsigned char digest_interim[SHA_DIGEST_LENGTH];
    unsigned char digest_final[SHA_DIGEST_LENGTH];
    SHA1((unsigned char*)&input_password, strlen((const char *) input_password), (unsigned char*)&digest_interim);
    SHA1((unsigned char*)&digest_interim, strlen((const char *)digest_interim), (unsigned char*)&digest_final);
    char final_hash[SHA_DIGEST_LENGTH * 2 +2];
    for(int i = 0; i < SHA_DIGEST_LENGTH; i++)
         sprintf(&final_hash[i*2], "%02X", (unsigned int)digest_final[i]);

    Info ("Computed password_hash:%s, stored password_hash:%s", final_hash,  db_password_hash);
    Debug (5, "Computed password_hash:%s, stored password_hash:%s", final_hash,  db_password_hash);
    password_correct = (strcmp(db_password_hash, final_hash)==0);
  }
  else if ((db_password_hash[0] == '$') && (db_password_hash[1]== '2')
           &&(db_password_hash[3] == '$')) {
    // BCRYPT 
    Info ("%s is using a bcrypt encoded password", username);
    BCrypt bcrypt;
    std::string input_hash = bcrypt.generateHash(std::string(input_password));
    password_correct = bcrypt.validatePassword(std::string(input_password), std::string(db_password_hash));
  }
  else {
    // plain
    Warning ("%s is using a plain text password, please do not use plain text", username);
    password_correct = (strcmp(input_password, db_password_hash) == 0);
  }
  return password_correct;
}