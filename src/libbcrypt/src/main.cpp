#include "bcrypt/BCrypt.hpp"
#include <iostream>

int main(){
	std::string right_password = "right_password";
	std::string wrong_password = "wrong_password";

	std::cout << "generate hash... " << std::flush;
	std::string hash = BCrypt::generateHash(right_password, 12);
	std::cout << "done." << std::endl;

	std::cout << "checking right password: " << std::flush
			  << BCrypt::validatePassword(right_password,hash) << std::endl;

	std::cout << "checking wrong password: " << std::flush
			  << BCrypt::validatePassword(wrong_password,hash) << std::endl;

	return 0;
}
