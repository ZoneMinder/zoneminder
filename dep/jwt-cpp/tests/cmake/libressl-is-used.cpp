#if !__has_include(<tls.h>)
#error "missing LibreSSL's TLS header!"
#endif

#include <tls.h>

#include "jwt-cpp/jwt.h"

int main() {
	tls_init();
	jwt::date date;
	return 0;
}
