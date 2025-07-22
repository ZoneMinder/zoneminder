#if !__has_include(<wolfssl/ssl.h>)
#error "missing wolfSSL's SSL header!"
#endif

#ifndef OPENSSL_EXTRA
#error "missing wolfSSL's OPENSSL_EXTRA macro!"
#endif

#ifndef OPENSSL_ALL
#error "missing wolfSSL's OPENSSL_ALL macro!"
#endif

#include "jwt-cpp/jwt.h"

#include <wolfssl/ssl.h>

int main() {
	wolfSSL_library_init();
	jwt::date date;
	return 0;
}
