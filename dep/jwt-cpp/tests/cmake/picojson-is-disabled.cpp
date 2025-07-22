#ifndef JWT_DISABLE_PICOJSON
#error "This test expects 'JWT_DISABLE_PICOJSON' to be defined!"
#endif

#include "jwt-cpp/jwt.h"

int main() {
	jwt::date date;
	return 0;
}
