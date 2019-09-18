#include "../../include/bcrypt/BCrypt.hpp"
#include <iostream>

using namespace std;

int main() {
	string right_password = "right_password";
	string wrong_password = "wrong_password";

	cout << "generate hash... " << flush;
	string hash = BCrypt::generateHash(right_password, 12);
	cout << "done." << endl;

	cout << "checking right password: " << flush
		<< BCrypt::validatePassword(right_password, hash) << endl;

	cout << "checking wrong password: " << flush
		<< BCrypt::validatePassword(wrong_password, hash) << endl;

	system("pause");
	return 0;
}
