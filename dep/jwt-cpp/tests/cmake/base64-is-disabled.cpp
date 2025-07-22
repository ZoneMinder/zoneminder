#ifndef JWT_DISABLE_BASE64
#error "This test expects 'JWT_DISABLE_BASE64' to be defined!"
#endif

#include "jwt-cpp/jwt.h"

int main() {
	jwt::date date;
	return 0;
}
