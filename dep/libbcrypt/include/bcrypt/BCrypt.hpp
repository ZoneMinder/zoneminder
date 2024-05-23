#ifndef __BCRYPT__
#define __BCRYPT__

#ifdef _WIN32
#include "winbcrypt.h"
#else

#include "bcrypt.h"
#include <string>
#include <stdexcept>

class BCrypt {
public:
    static std::string generateHash(const std::string & password, int workload = 12){
        char salt[BCRYPT_HASHSIZE];
        char hash[BCRYPT_HASHSIZE];
        int ret;
        ret = bcrypt_gensalt(workload, salt);
        if(ret != 0)throw std::runtime_error{"bcrypt: can not generate salt"};
        ret = bcrypt_hashpw(password.c_str(), salt, hash);
        if(ret != 0)throw std::runtime_error{"bcrypt: can not generate hash"};
        return std::string{hash};
    }

    static bool validatePassword(const std::string & password, const std::string & hash){
        return (bcrypt_checkpw(password.c_str(), hash.c_str()) == 0);
    }
};

#endif

#endif // __BCRYPT__
