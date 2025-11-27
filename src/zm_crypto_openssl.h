/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#ifndef ZONEMINDER_SRC_ZM_CRYPTO_OPENSSL_H_
#define ZONEMINDER_SRC_ZM_CRYPTO_OPENSSL_H_

#ifdef HAVE_LIBOPENSSL

#include "zm_crypto_generics.h"
#include "zm_utils.h"

#include <openssl/evp.h>

namespace zm {
namespace crypto {
namespace impl {
namespace openssl {

typedef EVP_MD const *(*HashCreator)();

template <HashAlgorithms Algorithm>
struct HashAlgorithmMapper;

template <>
struct HashAlgorithmMapper<HashAlgorithms::kMD5> {
// TODO: Remove conditional once Jessie and CentOS 7 are deprecated
// This is needed since GCC 4.8 is faulty (https://gcc.gnu.org/bugzilla/show_bug.cgi?id=60199)
#if defined(__GNUC__) && __GNUC__ < 5
  static HashCreator hash_creator() {
    static constexpr HashCreator creator = EVP_md5;
    return creator;
  }
#else
  static constexpr HashCreator hash_creator = EVP_md5;
#endif
};

template <>
struct HashAlgorithmMapper<HashAlgorithms::kSHA1> {
// TODO: Remove conditional once Jessie and CentOS 7 are deprecated
// This is needed since GCC 4.8 is faulty (https://gcc.gnu.org/bugzilla/show_bug.cgi?id=60199)
#if defined(__GNUC__) && __GNUC__ < 5
  static HashCreator hash_creator() {
    static constexpr HashCreator creator = EVP_sha1;
    return creator;
  }
#else
  static constexpr HashCreator hash_creator = EVP_sha1;
#endif
};

template <HashAlgorithms Algorithm>
class GenericHashImpl : public GenericHash<GenericHashImpl<Algorithm>, Algorithm> {
 public:
  GenericHashImpl() {
    // TODO: Use EVP_MD_CTX_new once we drop support for Jessie and CentOS 7 (OpenSSL > 1.1.0)
    ctx_ = EVP_MD_CTX_create();
#if defined(__GNUC__) && __GNUC__ < 5
    EVP_DigestInit_ex(ctx_, HashAlgorithmMapper<Algorithm>::hash_creator()(), nullptr);
#else
    EVP_DigestInit_ex(ctx_, HashAlgorithmMapper<Algorithm>::hash_creator(), nullptr);
#endif
  };

  ~GenericHashImpl() {
    // TODO: Use EVP_MD_CTX_free once we drop support for Jessie and CentOS 7 (OpenSSL > 1.1.0)
    EVP_MD_CTX_destroy(ctx_);
  }

  void DoUpdateData(const uint8 *data, size_t length) {
    int32 res = EVP_DigestUpdate(ctx_, data, length);
    ASSERT(res == 1);
  }

  void DoFinalize() {
    uint32 length = 0;
    int32 res = EVP_DigestFinal_ex(ctx_, digest_.data(), &length);
    ASSERT(res == 1);
    ASSERT(length == HashAlgorithm<Algorithm>::digest_length);
  }

 private:
  EVP_MD_CTX *ctx_;

  using Base = GenericHash<GenericHashImpl<Algorithm>, Algorithm>;
  using Base::digest_;
};
}  // namespace openssl
}  // namespace impl
}  // namespace crypto
}  // namespace zm

#endif  // HAVE_LIBOPENSSL

#endif  // ZONEMINDER_SRC_ZM_CRYPTO_OPENSSL_H_
