# jwt-cpp

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/5f7055e294744901991fd0a1620b231d)](https://app.codacy.com/app/Thalhammer/jwt-cpp?utm_source=github.com&utm_medium=referral&utm_content=Thalhammer/jwt-cpp&utm_campaign=Badge_Grade_Settings)

A header only library for creating and validating json web tokens in c++.

## Signature algorithms
As of version 0.2.0 jwt-cpp supports all algorithms defined by the spec. The modular design of jwt-cpp allows one to add additional algorithms without any problems. If you need any feel free to open a pull request.
For the sake of completeness, here is a list of all supported algorithms:
* HS256
* HS384
* HS512
* RS256
* RS384
* RS512
* ES256
* ES384
* ES512
* PS256
* PS384
* PS512

## Examples
Simple example of decoding a token and printing all claims:
```c++
#include <jwt-cpp/jwt.h>
#include <iostream>

int main(int argc, const char** argv) {
	std::string token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";
	auto decoded = jwt::decode(token);

	for(auto& e : decoded.get_payload_claims())
		std::cout << e.first << " = " << e.second.to_json() << std::endl;
}
```

In order to verify a token you first build a verifier and use it to verify a decoded token.
```c++
auto verifier = jwt::verify()
	.allow_algorithm(jwt::algorithm::hs256{ "secret" })
	.with_issuer("auth0");

verifier.verify(decoded_token);
```
The created verifier is stateless so you can reuse it for different tokens.

Creating a token (and signing) is equally easy.
```c++
auto token = jwt::create()
	.set_issuer("auth0")
	.set_type("JWS")
	.set_payload_claim("sample", std::string("test"))
	.sign(jwt::algorithm::hs256{"secret"});
```

Here is a simple example of creating a token that will expire in 2 hours:

```c++

	// Note to @Thalhammer: please replace with a better example if this is not a good way
        auto token = jwt::create()
         .set_issuer("auth0")
         .set_issued_at(jwt::date(std::chrono::system_clock::now()))
         .set_expires_at(jwt::date(std::chrono::system_clock::now()+ std::chrono::seconds{3600}))
         .sign(jwt::algorithm::hs256{"secret"}


```

## Contributing
If you have an improvement or found a bug feel free to [open an issue](https://github.com/Thalhammer/jwt-cpp/issues/new) or add the change and create a pull request. If you file a bug please make sure to include as much information about your environment (compiler version, etc.) as possible to help reproduce the issue. If you add a new feature please make sure to also include test cases for it.

## Dependencies
In order to use jwt-cpp you need the following tools.
* libcrypto (openssl or compatible)
* libssl-dev (for the header files)
* a compiler supporting at least c++11
* basic stl support

In order to build the test cases you also need
* gtest installed in linker path
* pthread

## Troubleshooting
#### Expired tokens
If you are generating tokens that seem to immediately expire, you are likely not using UTC. Specifically,
if you use `get_time` to get the current time, it likely uses localtime, while this library uses UTC, which may be why your token is immediately expiring. Please see example above on the right way to use current time.

#### Missing _HMAC amd _EVP_sha256 symbols on Mac
There seems to exists a problem with the included openssl library of MacOS. Make sure you link to one provided by brew.
See [here](https://github.com/Thalhammer/jwt-cpp/issues/6) for more details.
#### Building on windows fails with syntax errors
The header "Windows.h", which is often included in windowsprojects, defines macros for MIN and MAX which screw up std::numeric_limits.
See [here](https://github.com/Thalhammer/jwt-cpp/issues/5) for more details. To fix this do one of the following things:
* define NOMINMAX, which suppresses this behaviour
* include this library before you include windows.h
* place ```#undef max``` and ```#undef min``` before you include this library
