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

#ifndef ZONEMINDER_SRC_ZM_CRYPTO_GNUTLS_H_
#define ZONEMINDER_SRC_ZM_CRYPTO_GNUTLS_H_

#ifdef HAVE_LIBGNUTLS

#include "zm_crypto_generics.h"
#include "zm_utils.h"

#include <gnutls/crypto.h>

namespace zm {
namespace crypto {
namespace impl {
namespace gnutls {

template <HashAlgorithms Algorithm>
struct HashAlgorithmMapper;

template <>
struct HashAlgorithmMapper<HashAlgorithms::kMD5> {
  static constexpr gnutls_digest_algorithm_t algorithm = GNUTLS_DIG_MD5;
};

template <>
struct HashAlgorithmMapper<HashAlgorithms::kSHA1> {
  static constexpr gnutls_digest_algorithm_t algorithm = GNUTLS_DIG_SHA1;
};

template <HashAlgorithms Algorithm>
class GenericHashImpl : public GenericHash<GenericHashImpl<Algorithm>, Algorithm> {
 public:
  GenericHashImpl() {
    int32 ret = gnutls_hash_init(&handle_, HashAlgorithmMapper<Algorithm>::algorithm);
    ASSERT(ret == 0);
  };

  void DoUpdateData(const uint8 *data, size_t length) {
    int32 res = gnutls_hash(handle_, data, length);
    ASSERT(res == 0);
  }

  void DoFinalize() { gnutls_hash_deinit(handle_, digest_.data()); }

 private:
  gnutls_hash_hd_t handle_ = {};

  using Base = GenericHash<GenericHashImpl<Algorithm>, Algorithm>;
  using Base::digest_;
};
}  // namespace gnutls
}  // namespace impl
}  // namespace crypto
}  // namespace zm

#endif  // HAVE_LIBGNUTLS

#endif  // ZONEMINDER_SRC_ZM_CRYPTO_GNUTLS_H_
