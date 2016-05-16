###OSX port notes

This is an initial port *attempt* OSX
It's just at a point where stuff compiles, and processes run from command line
That's pretty much it. No packaging modifications yet (and therefore no make install) and no idea if mapped memory works
No idea if it works together as a system either.

This may help folks who are interested in an OSX port.


#### Toolchain
requirement - brew

brew install cmake
brew install libjpeg
brew install mysql
brew install ssl

sudo perl -MCPAN -e 'install DBI'
sudo perl -MCPAN -e 'install DBD::mysql'
sudo perl -MCPAN -e 'install Sys::Mmap'
brew install pkg-config
brew install glib
brew install pcre++
xcode-select --install

#### Environment used to build

Xcode Version 7.3.1 (7D1014)
gcc --version
Configured with: --prefix=/Applications/Xcode.app/Contents/Developer/usr --with-gxx-include-dir=/usr/include/c++/4.2.1
Apple LLVM version 7.3.0 (clang-703.0.31)
Target: x86_64-apple-darwin15.4.0
Thread model: posix
InstalledDir: /Applications/Xcode.app/Contents/Developer/Toolchains/XcodeDefault.xctoolchain/usr/bin


#### Build process
git submodule update --init --recursive
cmake  -DOPENSSL_ROOT_DIR=/usr/local/Cellar/openssl/1.0.2a-1/include


