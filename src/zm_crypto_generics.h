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

#ifndef ZONEMINDER_SRC_ZM_CRYPTO_GENERICS_H_
#define ZONEMINDER_SRC_ZM_CRYPTO_GENERICS_H_

#include "zm_define.h"
#include "zm_utils.h"

#include <array>
#include <cstring>

namespace zm {
namespace crypto {
namespace impl {

enum class HashAlgorithms { kMD5, kSHA1 };

template <HashAlgorithms Algorithm>
struct HashAlgorithm;

template <>
struct HashAlgorithm<HashAlgorithms::kMD5> {
  static constexpr size_t digest_length = 16;
};

template <>
struct HashAlgorithm<HashAlgorithms::kSHA1> {
  static constexpr size_t digest_length = 20;
};

template <typename Impl, HashAlgorithms Algorithm>
class GenericHash {
 public:
  static constexpr size_t DIGEST_LENGTH = HashAlgorithm<Algorithm>::digest_length;
  using Digest = std::array<uint8, DIGEST_LENGTH>;

  static Digest GetDigestOf(uint8 const *data, size_t len) {
    Impl hash;
    hash.UpdateData(data, len);
    hash.Finalize();
    return hash.GetDigest();
  }

  template <typename... Ts>
  static Digest GetDigestOf(Ts &&...pack) {
    Impl hash;
    UpdateData(hash, std::forward<Ts>(pack)...);
    hash.Finalize();
    return hash.GetDigest();
  }

  void UpdateData(const uint8 *data, size_t length) {
    static_cast<Impl &>(*this).DoUpdateData(data, length);
  }
  void UpdateData(const std::string &str) {
    UpdateData(reinterpret_cast<const uint8 *>(str.c_str()), str.size());
  }
  void UpdateData(const char *str) {
    UpdateData(reinterpret_cast<const uint8 *>(str), strlen(str));
  }
  template <typename Container>
  void UpdateData(Container const &c) {
    UpdateData(zm::data(c), zm::size(c));
  }

  void Finalize() { static_cast<Impl &>(*this).DoFinalize(); }

  const Digest &GetDigest() const { return digest_; }

 protected:
  Digest digest_ = {};

 private:
  template <typename T>
  static void UpdateData(Impl &hash, T const &data) {
    hash.UpdateData(data);
  }

  template <typename T, typename... TRest>
  static void UpdateData(Impl &hash, T const &data, TRest &&...rest) {
    hash.UpdateData(data);
    UpdateData(hash, std::forward<TRest>(rest)...);
  }
};
}  // namespace impl
}  // namespace crypto
}  // namespace zm

#endif  // ZONEMINDER_SRC_ZM_CRYPTO_GENERICS_H_
