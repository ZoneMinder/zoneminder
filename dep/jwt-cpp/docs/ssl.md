# Cryptography Libraries

The underlying cryptography libraries describe [here](../README.md#ssl-compatibility) can be selected when 
configuring CMake by explicitly setting `JWT_SSL_LIBRARY` to one of three values. The default is to use OpenSSL.

- OpenSSL
- LibreSSL
- wolfSSL

Here's an example:

```sh
cmake . -DJWT_SSL_LIBRARY:STRING=wolfSSL 
```

## Notes

JWT-CPP relies on the OpenSSL API, as a result both LibreSSL and wolfSSL need to include their respective compatibility layers.
Most system already have OpenSSL so it's important to make sure when compiling your application it only includes one. Otherwise you may have missing symbols when linking.
