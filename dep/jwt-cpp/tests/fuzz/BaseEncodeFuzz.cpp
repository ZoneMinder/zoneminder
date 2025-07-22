#include <jwt-cpp/base.h>

extern "C" {

int LLVMFuzzerTestOneInput(const uint8_t* Data, size_t Size) {
	jwt::base::encode<jwt::alphabet::base64>(std::string{(char*)Data, Size});
	return 0; // Non-zero return values are reserved for future use.
}
}
