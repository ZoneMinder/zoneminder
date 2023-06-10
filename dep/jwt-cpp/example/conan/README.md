# Conan example

In the current directory on Linux environment

```sh
mkdir build && cd build
conan install .. && cmake .. 
cmake --build .
```

run executable

```sh
$ ./bin/conan-example
sub = "jrocket@example.com"
iss = "Online JWT Builder"
exp = 1641559177
aud = "www.example.com"
Surname = "Rocket"
Role = ["Manager","Project Administrator"]
iat = 1610023177
GivenName = "Johnny"
Email = "jrocket@example.com"
```
