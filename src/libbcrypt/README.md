# libbcrypt
A c++ wrapper around bcrypt password hashing

## How to build this
This is a CMake based project:

```bash
git clone https://github.com/trusch/libbcrypt
cd libbcrypt
mkdir build
cd build
cmake ..
make
sudo make install
sudo ldconfig
```

## How to use this

Here an example how to use this wrapper class (you can find it in the src/ subdirectory)

```cpp
#include "bcrypt/BCrypt.hpp"
#include <iostream>

int main(){
	BCrypt bcrypt;
	std::string password = "test";
	std::string hash = bcrypt.generateHash(password);
	std::cout<<bcrypt.validatePassword(password,hash)<<std::endl;
	std::cout<<bcrypt.validatePassword("test1",hash)<<std::endl;
	return 0;
}
```

build this with something like this:

```bash
g++ --std=c++11 -lbcrypt main.cpp
```
