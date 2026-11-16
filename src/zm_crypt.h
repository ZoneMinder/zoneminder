//
// ZoneMinder General Utility Functions, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#ifndef ZM_CRYPT_H
#define ZM_CRYPT_H

#include "zm_config.h"
#include "zm_crypto_gnutls.h"
#include "zm_crypto_openssl.h"
#include "zm_define.h"
#include <string>

bool verifyPassword(const char *username, const char *input_password, const char *db_password_hash);

std::string generateKey(const int length);

std::pair<std::string, unsigned int> verifyToken(const std::string &token, const std::string &key);

namespace zm {
namespace crypto {
namespace impl {

#if defined(HAVE_LIBGNUTLS)
template<HashAlgorithms Algorithm>
using Hash = gnutls::GenericHashImpl<Algorithm>;
#elif defined(HAVE_LIBOPENSSL)
template<HashAlgorithms Algorithm>
using Hash = openssl::GenericHashImpl<Algorithm>;
#endif
}
}
}

namespace zm {
namespace crypto {
using MD5 = impl::Hash<impl::HashAlgorithms::kMD5>;
using SHA1 = impl::Hash<impl::HashAlgorithms::kSHA1>;
using SHA256 = impl::Hash<impl::HashAlgorithms::kSHA256>;

// A short, non-reversible tag for a credential such as an auth hash or a token,
// so log lines can be matched up without the credential itself being logged:
// "fp:" followed by the first 8 hex digits of the value's SHA-256. The web
// side's ZM\logFingerprint() gives the same tag for the same value. Not for
// passwords: a password's fingerprint can be brute-forced offline.
std::string LogFingerprint(const std::string &secret);
}
}

#endif // ZM_CRYPT_H
