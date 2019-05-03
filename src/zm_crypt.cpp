#include "zm.h"
# include "zm_crypt.h"
#include <algorithm>



//https://stackoverflow.com/a/46403026/1361529
char char2int(char input) {
    if (input >= '0' && input <= '9')
      return input - '0';
    else if (input >= 'A' && input <= 'F')
      return input - 'A' + 10;
    else if (input >= 'a' && input <= 'f')
      return input - 'a' + 10;
    else
      return input;  // this really should not happen

}
std::string hex2str(std::string &hex) {
    std::string out;
    out.resize(hex.size() / 2 + hex.size() % 2);
    std::string::iterator it = hex.begin();
    std::string::iterator out_it = out.begin();
    if (hex.size() % 2 != 0) {
        *out_it++ = char(char2int(*it++));
    }

    for (; it < hex.end() - 1; it++) {
        *out_it++ = char2int(*it++) << 4 | char2int(*it);
    };

    return out;
}


bool verifyPassword(const char *username, const char *input_password, const char *db_password_hash) {
  bool password_correct = false;
  if (strlen(db_password_hash ) < 4) {
    // actually, shoud be more, but this is min. for next code
    Error ("DB Password is too short or invalid to check");
    return false;
  }
  if (db_password_hash[0] == '*') {
    // MYSQL PASSWORD
    Info ("%s is using an MD5 encoded password", username);
    SHA1 checksum;

    // next few lines do '*'+SHA1(raw(SHA1(password)))
    // which is MYSQL >=4.1 PASSWORD algorithm
    checksum.update(input_password);
    std::string interim_hash = checksum.final();
    std::string binary_hash = hex2str(interim_hash); // get interim hash
    checksum.update(binary_hash);
    interim_hash = checksum.final();
    std::string final_hash = "*" + interim_hash;
    std::transform(final_hash.begin(), final_hash.end(), final_hash.begin(), ::toupper);

    Debug (5, "Computed password_hash:%s, stored password_hash:%s", final_hash.c_str(),  db_password_hash);
    password_correct = (std::string(db_password_hash) == final_hash);
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