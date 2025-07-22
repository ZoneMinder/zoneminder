#include <jwt-cpp/base.h>

extern "C" {

int LLVMFuzzerTestOneInput(const uint8_t* Data, size_t Size) {
	try {
		const auto bin = jwt::base::decode<jwt::alphabet::base64>(std::string{(char*)Data, Size});
	} catch (const std::runtime_error&) {
		// parse errors are ok, because input may be random bytes
	}
	return 0; // Non-zero return values are reserved for future use.
}
}
