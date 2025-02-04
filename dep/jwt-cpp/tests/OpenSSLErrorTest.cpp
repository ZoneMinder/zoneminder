#ifdef __linux__ // None of this stuff is going to work outside of linux!

#ifndef HUNTER_ENABLED // Static linking (which hunter always? does) breaks the tests (duplicate definition), so skip them

#include "jwt-cpp/jwt.h"
#include <gtest/gtest.h>

#include <dlfcn.h>
// TODO: Figure out why the tests fail on older openssl versions
#ifndef JWT_OPENSSL_1_0_0 // It fails on < 1.1 but no idea why.
// LibreSSL has different return codes but was already outside of the effective scope

/**
 * ============ Beginning of black magic ===============
 * We mock out a couple of openssl functions.
 * We can do this because the current executable take precedence while linking.
 * Once it is running and we want normal behaviour, we query the original method using dlsym.
 */
static uint64_t fail_BIO_new = 0;
static uint64_t fail_PEM_read_bio_X509 = 0;
static uint64_t fail_X509_get_pubkey = 0;
static uint64_t fail_PEM_write_bio_PUBKEY = 0;
static uint64_t fail_PEM_write_bio_cert = 0;
static uint64_t fail_BIO_ctrl = 0;
static uint64_t fail_BIO_write = 0;
static uint64_t fail_PEM_read_bio_PUBKEY = 0;
static uint64_t fail_PEM_read_bio_PrivateKey = 0;
static uint64_t fail_HMAC = 0;
static uint64_t fail_EVP_MD_CTX_new = 0;
static uint64_t fail_EVP_DigestInit = 0;
static uint64_t fail_EVP_DigestUpdate = 0;
static uint64_t fail_EVP_DigestFinal = 0;
static uint64_t fail_EVP_SignFinal = 0;
static uint64_t fail_EVP_VerifyFinal = 0;
#ifdef JWT_OPENSSL_3_0
static uint64_t fail_EVP_PKEY_public_check = 0;
static uint64_t fail_EVP_PKEY_private_check = 0;
static uint64_t fail_EVP_PKEY_CTX_new_from_pkey = 0;
#else
static uint64_t fail_EC_KEY_check_key = 0;
static uint64_t fail_EVP_PKEY_get1_EC_KEY = 0;
#endif
static uint64_t fail_ECDSA_SIG_new = 0;
static uint64_t fail_EVP_PKEY_get1_RSA = 0;
static uint64_t fail_EVP_DigestSignInit = 0;
static uint64_t fail_EVP_DigestSign = 0;
static uint64_t fail_EVP_DigestVerifyInit = 0;
static uint64_t fail_EVP_DigestVerify = 0;
static uint64_t fail_EVP_DigestSignFinal = 0;
static uint64_t fail_EVP_DigestVerifyFinal = 0;
static uint64_t fail_d2i_ECDSA_SIG = 0;
static uint64_t fail_i2d_ECDSA_SIG = 0;

BIO* BIO_new(const BIO_METHOD* type) {
	static BIO* (*origMethod)(const BIO_METHOD*) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "BIO_new");
	bool fail = fail_BIO_new & 1;
	fail_BIO_new = fail_BIO_new >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(type);
}

X509* PEM_read_bio_X509(BIO* bp, X509** x, pem_password_cb* cb, void* u) {
	static X509* (*origMethod)(BIO * bp, X509 * *x, pem_password_cb * cb, void* u) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "PEM_read_bio_X509");
	bool fail = fail_PEM_read_bio_X509 & 1;
	fail_PEM_read_bio_X509 = fail_PEM_read_bio_X509 >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(bp, x, cb, u);
}

EVP_PKEY* X509_get_pubkey(X509* x) {
	static EVP_PKEY* (*origMethod)(X509*) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "X509_get_pubkey");
	bool fail = fail_X509_get_pubkey & 1;
	fail_X509_get_pubkey = fail_X509_get_pubkey >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(x);
}

#ifdef JWT_OPENSSL_3_0
#define OPENSSL_CONST const
#else
#define OPENSSL_CONST
#endif

int PEM_write_bio_PUBKEY(BIO* bp, OPENSSL_CONST EVP_PKEY* x) {
	static int (*origMethod)(BIO * bp, OPENSSL_CONST EVP_PKEY * x) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "PEM_write_bio_PUBKEY");
	bool fail = fail_PEM_write_bio_PUBKEY & 1;
	fail_PEM_write_bio_PUBKEY = fail_PEM_write_bio_PUBKEY >> 1;
	if (fail)
		return 0;
	else
		return origMethod(bp, x);
}

int PEM_write_bio_X509(BIO* bp, OPENSSL_CONST X509* x) {
	static int (*origMethod)(BIO * bp, OPENSSL_CONST X509 * x) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "PEM_write_bio_X509");
	bool fail = fail_PEM_write_bio_cert & 1;
	fail_PEM_write_bio_cert = fail_PEM_write_bio_cert >> 1;
	if (fail)
		return 0;
	else
		return origMethod(bp, x);
}

long BIO_ctrl(BIO* bp, int cmd, long larg, void* parg) {
	static long (*origMethod)(BIO * bp, int cmd, long larg, void* parg) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "BIO_ctrl");
	bool fail = fail_BIO_ctrl & 1;
	fail_BIO_ctrl = fail_BIO_ctrl >> 1;
	if (fail)
		return 0;
	else
		return origMethod(bp, cmd, larg, parg);
}

int BIO_write(BIO* b, const void* data, int dlen) {
	static int (*origMethod)(BIO * b, const void* data, int dlen) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "BIO_write");
	bool fail = fail_BIO_write & 1;
	fail_BIO_write = fail_BIO_write >> 1;
	if (fail)
		return 0;
	else
		return origMethod(b, data, dlen);
}

EVP_PKEY* PEM_read_bio_PUBKEY(BIO* bp, EVP_PKEY** x, pem_password_cb* cb, void* u) {
	static EVP_PKEY* (*origMethod)(BIO * bp, EVP_PKEY * *x, pem_password_cb * cb, void* u) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "PEM_read_bio_PUBKEY");
	bool fail = fail_PEM_read_bio_PUBKEY & 1;
	fail_PEM_read_bio_PUBKEY = fail_PEM_read_bio_PUBKEY >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(bp, x, cb, u);
}

EVP_PKEY* PEM_read_bio_PrivateKey(BIO* bp, EVP_PKEY** x, pem_password_cb* cb, void* u) {
	static EVP_PKEY* (*origMethod)(BIO * bp, EVP_PKEY * *x, pem_password_cb * cb, void* u) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "PEM_read_bio_PrivateKey");
	bool fail = fail_PEM_read_bio_PrivateKey & 1;
	fail_PEM_read_bio_PrivateKey = fail_PEM_read_bio_PrivateKey >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(bp, x, cb, u);
}

unsigned char* HMAC(const EVP_MD* evp_md, const void* key, int key_len, const unsigned char* d, size_t n,
					unsigned char* md, unsigned int* md_len) {
	static unsigned char* (*origMethod)(const EVP_MD* evp_md, const void* key, int key_len, const unsigned char* d,
										size_t n, unsigned char* md, unsigned int* md_len) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "HMAC");
	bool fail = fail_HMAC & 1;
	fail_HMAC = fail_HMAC >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(evp_md, key, key_len, d, n, md, md_len);
}

EVP_MD_CTX* EVP_MD_CTX_new(void) {
	static EVP_MD_CTX* (*origMethod)(void) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_MD_CTX_new");
	bool fail = fail_EVP_MD_CTX_new & 1;
	fail_EVP_MD_CTX_new = fail_EVP_MD_CTX_new >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod();
}

int EVP_DigestSignFinal(EVP_MD_CTX* ctx, unsigned char* sigret, size_t* siglen) {
	static int (*origMethod)(EVP_MD_CTX * ctx, unsigned char* sigret, size_t* siglen) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestSignFinal");
	bool fail = fail_EVP_DigestSignFinal & 1;
	fail_EVP_DigestSignFinal = fail_EVP_DigestSignFinal >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, sigret, siglen);
}

int EVP_DigestInit(EVP_MD_CTX* ctx, const EVP_MD* type) {
	static int (*origMethod)(EVP_MD_CTX * ctx, const EVP_MD* type) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestInit");
	bool fail = fail_EVP_DigestInit & 1;
	fail_EVP_DigestInit = fail_EVP_DigestInit >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, type);
}

int EVP_DigestUpdate(EVP_MD_CTX* ctx, const void* d, size_t cnt) {
	static int (*origMethod)(EVP_MD_CTX * ctx, const void* d, size_t cnt) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestUpdate");
	bool fail = fail_EVP_DigestUpdate & 1;
	fail_EVP_DigestUpdate = fail_EVP_DigestUpdate >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, d, cnt);
}

int EVP_DigestFinal(EVP_MD_CTX* ctx, unsigned char* md, unsigned int* s) {
	static int (*origMethod)(EVP_MD_CTX * ctx, unsigned char* md, unsigned int* s) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestFinal");
	bool fail = fail_EVP_DigestFinal & 1;
	fail_EVP_DigestFinal = fail_EVP_DigestFinal >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, md, s);
}

int EVP_SignFinal(EVP_MD_CTX* ctx, unsigned char* md, unsigned int* s, EVP_PKEY* pkey) {
	static int (*origMethod)(EVP_MD_CTX * ctx, unsigned char* md, unsigned int* s, EVP_PKEY* pkey) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_SignFinal");
	bool fail = fail_EVP_SignFinal & 1;
	fail_EVP_SignFinal = fail_EVP_SignFinal >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, md, s, pkey);
}

int EVP_VerifyFinal(EVP_MD_CTX* ctx, const unsigned char* sigbuf, unsigned int siglen, EVP_PKEY* pkey) {
	static int (*origMethod)(EVP_MD_CTX * ctx, const unsigned char* sigbuf, unsigned int siglen, EVP_PKEY* pkey) =
		nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_VerifyFinal");
	bool fail = fail_EVP_VerifyFinal & 1;
	fail_EVP_VerifyFinal = fail_EVP_VerifyFinal >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, sigbuf, siglen, pkey);
}

#ifdef JWT_OPENSSL_3_0
int EVP_PKEY_public_check(EVP_PKEY_CTX* ctx) {
	static int (*origMethod)(EVP_PKEY_CTX * ctx) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_PKEY_public_check");
	bool fail = fail_EVP_PKEY_public_check & 1;
	fail_EVP_PKEY_public_check = fail_EVP_PKEY_public_check >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx);
}

int EVP_PKEY_private_check(EVP_PKEY_CTX* ctx) {
	static int (*origMethod)(EVP_PKEY_CTX * ctx) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_PKEY_private_check");
	bool fail = fail_EVP_PKEY_private_check & 1;
	fail_EVP_PKEY_private_check = fail_EVP_PKEY_private_check >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx);
}

EVP_PKEY_CTX* EVP_PKEY_CTX_new_from_pkey(OSSL_LIB_CTX* libctx, EVP_PKEY* pkey, const char* propquery) {
	static EVP_PKEY_CTX* (*origMethod)(OSSL_LIB_CTX * libctx, EVP_PKEY * pkey, const char* propquery) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_PKEY_CTX_new_from_pkey");
	bool fail = fail_EVP_PKEY_CTX_new_from_pkey & 1;
	fail_EVP_PKEY_CTX_new_from_pkey = fail_EVP_PKEY_CTX_new_from_pkey >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(libctx, pkey, propquery);
}

#else
int EC_KEY_check_key(const EC_KEY* key) {
	static int (*origMethod)(const EC_KEY* key) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EC_KEY_check_key");
	bool fail = fail_EC_KEY_check_key & 1;
	fail_EC_KEY_check_key = fail_EC_KEY_check_key >> 1;
	if (fail)
		return 0;
	else
		return origMethod(key);
}

EC_KEY* EVP_PKEY_get1_EC_KEY(EVP_PKEY* pkey) {
	static EC_KEY* (*origMethod)(EVP_PKEY * pkey) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_PKEY_get1_EC_KEY");
	bool fail = fail_EVP_PKEY_get1_EC_KEY & 1;
	fail_EVP_PKEY_get1_EC_KEY = fail_EVP_PKEY_get1_EC_KEY >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(pkey);
}
#endif

ECDSA_SIG* ECDSA_SIG_new(void) {
	static ECDSA_SIG* (*origMethod)() = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "ECDSA_SIG_new");
	bool fail = fail_ECDSA_SIG_new & 1;
	fail_ECDSA_SIG_new = fail_ECDSA_SIG_new >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod();
}

struct rsa_st* EVP_PKEY_get1_RSA(EVP_PKEY* pkey) {
	static struct rsa_st* (*origMethod)(EVP_PKEY * pkey) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_PKEY_get1_RSA");
	bool fail = fail_EVP_PKEY_get1_RSA & 1;
	fail_EVP_PKEY_get1_RSA = fail_EVP_PKEY_get1_RSA >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(pkey);
}

int EVP_DigestSignInit(EVP_MD_CTX* ctx, EVP_PKEY_CTX** pctx, const EVP_MD* type, ENGINE* e, EVP_PKEY* pkey) {
	static int (*origMethod)(EVP_MD_CTX * ctx, EVP_PKEY_CTX * *pctx, const EVP_MD* type, ENGINE* e, EVP_PKEY* pkey) =
		nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestSignInit");
	bool fail = fail_EVP_DigestSignInit & 1;
	fail_EVP_DigestSignInit = fail_EVP_DigestSignInit >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, pctx, type, e, pkey);
}

int EVP_DigestSign(EVP_MD_CTX* ctx, unsigned char* sigret, size_t* siglen, const unsigned char* tbs, size_t tbslen) {
	static int (*origMethod)(EVP_MD_CTX * ctx, unsigned char* sigret, size_t* siglen, const unsigned char* tbs,
							 size_t tbslen) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestSign");
	bool fail = fail_EVP_DigestSign & 1;
	fail_EVP_DigestSign = fail_EVP_DigestSign >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, sigret, siglen, tbs, tbslen);
}

int EVP_DigestVerifyInit(EVP_MD_CTX* ctx, EVP_PKEY_CTX** pctx, const EVP_MD* type, ENGINE* e, EVP_PKEY* pkey) {
	static int (*origMethod)(EVP_MD_CTX * ctx, EVP_PKEY_CTX * *pctx, const EVP_MD* type, ENGINE* e, EVP_PKEY* pkey) =
		nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestVerifyInit");
	bool fail = fail_EVP_DigestVerifyInit & 1;
	fail_EVP_DigestVerifyInit = fail_EVP_DigestVerifyInit >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, pctx, type, e, pkey);
}

int EVP_DigestVerify(EVP_MD_CTX* ctx, unsigned char* sigret, size_t* siglen, const unsigned char* tbs, size_t tbslen) {
	static int (*origMethod)(EVP_MD_CTX * ctx, unsigned char* sigret, size_t* siglen, const unsigned char* tbs,
							 size_t tbslen) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestVerify");
	bool fail = fail_EVP_DigestVerify & 1;
	fail_EVP_DigestVerify = fail_EVP_DigestVerify >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, sigret, siglen, tbs, tbslen);
}

int EVP_DigestVerifyFinal(EVP_MD_CTX* ctx, const unsigned char* sigret, size_t siglen) {
	static int (*origMethod)(EVP_MD_CTX * ctx, const unsigned char* sigret, size_t siglen) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "EVP_DigestVerifyFinal");
	bool fail = fail_EVP_DigestVerifyFinal & 1;
	fail_EVP_DigestVerifyFinal = fail_EVP_DigestVerifyFinal >> 1;
	if (fail)
		return 0;
	else
		return origMethod(ctx, sigret, siglen);
}

int i2d_ECDSA_SIG(const ECDSA_SIG* sig, unsigned char** ppout) {
	static int (*origMethod)(const ECDSA_SIG* sig, unsigned char** ppout) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "i2d_ECDSA_SIG");
	bool fail = fail_i2d_ECDSA_SIG & 1;
	fail_i2d_ECDSA_SIG = fail_i2d_ECDSA_SIG >> 1;
	if (fail)
		return -1;
	else
		return origMethod(sig, ppout);
}

ECDSA_SIG* d2i_ECDSA_SIG(ECDSA_SIG** psig, const unsigned char** ppin, long len) {
	static ECDSA_SIG* (*origMethod)(ECDSA_SIG * *psig, const unsigned char** ppin, long len) = nullptr;
	if (origMethod == nullptr) origMethod = (decltype(origMethod))dlsym(RTLD_NEXT, "d2i_ECDSA_SIG");
	bool fail = fail_d2i_ECDSA_SIG & 1;
	fail_d2i_ECDSA_SIG = fail_d2i_ECDSA_SIG >> 1;
	if (fail)
		return nullptr;
	else
		return origMethod(psig, ppin, len);
}

/**
 * =========== End of black magic ============
 */

inline namespace test_keys {
	extern std::string rsa_priv_key;
	extern std::string rsa_pub_key;
	extern std::string rsa_pub_key_invalid;
	extern std::string rsa512_priv_key;
	extern std::string rsa512_pub_key;
	extern std::string rsa512_pub_key_invalid;
	extern std::string ecdsa256_certificate;
	extern std::string ecdsa256_priv_key;
	extern std::string ecdsa256_pub_key;
	extern std::string ecdsa256_pub_key_invalid;
	extern std::string ecdsa384_priv_key;
	extern std::string ecdsa384_pub_key;
	extern std::string ecdsa384_pub_key_invalid;
	extern std::string ecdsa521_priv_key;
	extern std::string ecdsa521_pub_key;
	extern std::string ecdsa521_pub_key_invalid;
	extern std::string sample_cert;
	extern std::string sample_cert_base64_der;
	extern std::string sample_cert_pubkey;
	extern std::string ed25519_priv_key;
	extern std::string ed25519_pub_key;
	extern std::string ed25519_pub_key_invalid;
	extern std::string ed25519_certificate;
	extern std::string ed448_priv_key;
	extern std::string ed448_pub_key;
	extern std::string ed448_pub_key_invalid;
} // namespace test_keys

TEST(OpenSSLErrorTest, ExtractPubkeyFromCertReference) {
	std::error_code ec;
	auto res = jwt::helper::extract_pubkey_from_cert(sample_cert, "", ec);
	ASSERT_EQ(res, sample_cert_pubkey);
	ASSERT_FALSE(!(!ec));
	ASSERT_EQ(ec.value(), 0);
}

TEST(OpenSSLErrorTest, ConvertCertBase64DerToPemReference) {
	std::error_code ec;
	auto res = jwt::helper::convert_base64_der_to_pem(sample_cert_base64_der, ec);
	ASSERT_EQ(res, sample_cert);
	ASSERT_FALSE(!(!ec));
	ASSERT_EQ(ec.value(), 0);
}

struct multitest_entry {
	uint64_t* fail_mask_ptr;
	uint64_t fail_bitmask;
	std::error_code expected_ec;
};

template<typename Func>
void run_multitest(const std::vector<multitest_entry>& mapping, Func fn) {
	for (auto& e : mapping) {
		std::error_code ec;
		*e.fail_mask_ptr = e.fail_bitmask;
		try {
			fn(ec);
		} catch (...) {
			*e.fail_mask_ptr = 0;
			throw;
		}
		*e.fail_mask_ptr = 0;
		ASSERT_EQ(ec, e.expected_ec);
	}
}

TEST(OpenSSLErrorTest, ExtractPubkeyFromCert) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_PEM_read_bio_X509, 1, jwt::error::rsa_error::cert_load_failed},
										 {&fail_X509_get_pubkey, 1, jwt::error::rsa_error::get_key_failed},
										 {&fail_PEM_write_bio_PUBKEY, 1, jwt::error::rsa_error::write_key_failed},
										 {&fail_BIO_ctrl, 1, jwt::error::rsa_error::convert_to_pem_failed}};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::helper::extract_pubkey_from_cert(sample_cert, "");
			FAIL(); // Should never reach this
		} catch (const jwt::error::rsa_exception& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, ExtractPubkeyFromCertErrorCode) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_PEM_read_bio_X509, 1, jwt::error::rsa_error::cert_load_failed},
										 {&fail_X509_get_pubkey, 1, jwt::error::rsa_error::get_key_failed},
										 {&fail_PEM_write_bio_PUBKEY, 1, jwt::error::rsa_error::write_key_failed},
										 {&fail_BIO_ctrl, 1, jwt::error::rsa_error::convert_to_pem_failed}};

	run_multitest(mapping, [](std::error_code& ec) {
		auto res = jwt::helper::extract_pubkey_from_cert(sample_cert, "", ec);
		ASSERT_EQ(res, "");
	});
}

TEST(OpenSSLErrorTest, ConvertCertBase64DerToPem) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_PEM_write_bio_cert, 1, jwt::error::rsa_error::write_cert_failed},
										 {&fail_BIO_ctrl, 1, jwt::error::rsa_error::convert_to_pem_failed}};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::helper::convert_base64_der_to_pem(sample_cert_base64_der);
			FAIL(); // Should never reach this
		} catch (const jwt::error::rsa_exception& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, ConvertCertBase64DerToPemErrorCode) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_PEM_write_bio_cert, 1, jwt::error::rsa_error::write_cert_failed},
										 {&fail_BIO_ctrl, 1, jwt::error::rsa_error::convert_to_pem_failed}};

	run_multitest(mapping, [](std::error_code& ec) {
		auto res = jwt::helper::convert_base64_der_to_pem(sample_cert_base64_der, ec);
		ASSERT_EQ(res, "");
	});
}

TEST(OpenSSLErrorTest, LoadPublicKeyFromStringReference) {
	auto res = jwt::helper::load_public_key_from_string(rsa_pub_key, "");
	ASSERT_TRUE(res);
}

TEST(OpenSSLErrorTest, LoadPublicKeyFromString) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
										 {&fail_PEM_read_bio_PUBKEY, 1, jwt::error::rsa_error::load_key_bio_read}};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::helper::load_public_key_from_string(rsa_pub_key, "");
			FAIL(); // Should never reach this
		} catch (const jwt::error::rsa_exception& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, LoadPublicKeyFromStringErrorCode) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
										 {&fail_PEM_read_bio_PUBKEY, 1, jwt::error::rsa_error::load_key_bio_read}};

	run_multitest(mapping, [](std::error_code& ec) {
		auto res = jwt::helper::load_public_key_from_string(rsa_pub_key, "", ec);
		ASSERT_FALSE(res);
	});
}

TEST(OpenSSLErrorTest, LoadPublicKeyCertFromStringReference) {
	auto res = jwt::helper::load_public_key_from_string(sample_cert, "");
	ASSERT_TRUE(res);
}

TEST(OpenSSLErrorTest, LoadPublicKeyCertFromString) {
	std::vector<multitest_entry> mapping {
		{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
#if !defined(LIBRESSL_VERSION_NUMBER) || LIBRESSL_VERSION_NUMBER < 0x3050300fL
			{&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
#else
			{&fail_BIO_write, 1, jwt::error::rsa_error::write_key_failed},
#endif
		{
			&fail_PEM_read_bio_PUBKEY, 1, jwt::error::rsa_error::load_key_bio_read
		}
	};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::helper::load_public_key_from_string(sample_cert, "");
			FAIL(); // Should never reach this
		} catch (const jwt::error::rsa_exception& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, LoadPublicKeyCertFromStringErrorCode) {
	std::vector<multitest_entry> mapping {
		{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
#if !defined(LIBRESSL_VERSION_NUMBER) || LIBRESSL_VERSION_NUMBER < 0x3050300fL
			{&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
#else
			{&fail_BIO_write, 1, jwt::error::rsa_error::write_key_failed},
#endif
		{
			&fail_PEM_read_bio_PUBKEY, 1, jwt::error::rsa_error::load_key_bio_read
		}
	};

	run_multitest(mapping, [](std::error_code& ec) {
		auto res = jwt::helper::load_public_key_from_string(sample_cert, "", ec);
		ASSERT_FALSE(res);
	});
}

TEST(OpenSSLErrorTest, LoadPrivateKeyFromStringReference) {
	auto res = jwt::helper::load_private_key_from_string(rsa_priv_key, "");
	ASSERT_TRUE(res);
}

TEST(OpenSSLErrorTest, LoadPrivateKeyFromString) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
										 {&fail_PEM_read_bio_PrivateKey, 1, jwt::error::rsa_error::load_key_bio_read}};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::helper::load_private_key_from_string(rsa_priv_key, "");
			FAIL(); // Should never reach this
		} catch (const jwt::error::rsa_exception& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, LoadPrivateKeyFromStringErrorCode) {
	std::vector<multitest_entry> mapping{{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
										 {&fail_PEM_read_bio_PrivateKey, 1, jwt::error::rsa_error::load_key_bio_read}};

	run_multitest(mapping, [](std::error_code& ec) {
		auto res = jwt::helper::load_private_key_from_string(rsa_priv_key, "", ec);
		ASSERT_FALSE(res);
	});
}

TEST(OpenSSLErrorTest, HMACSign) {
	std::string token =
		"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJpc3MiOiJhdXRoMCJ9.AbIJTDMFc7yUa5MhvcP03nJPyCPzZtQcGEp-zWfOkEE";

	auto verify = jwt::verify().allow_algorithm(jwt::algorithm::hs256{"secret"}).with_issuer("auth0");

	auto decoded_token = jwt::decode(token);
	std::vector<multitest_entry> mapping{{&fail_HMAC, 1, jwt::error::signature_generation_error::hmac_failed}};

	run_multitest(mapping, [&](std::error_code& ec) { verify.verify(decoded_token, ec); });
}

TEST(OpenSSLErrorTest, RS256Reference) {
	jwt::algorithm::rs256 alg{rsa_pub_key, rsa_priv_key};
	std::error_code ec;
	auto res = alg.sign("testdata", ec);
	ASSERT_EQ(jwt::base::encode<jwt::alphabet::base64>(res),
			  "oCJUeLmIKKVVE/UWhEL/Malx0l9TCoXWNAS2z9o8ZYNaS4POIeadZWeUbLdICx3SCJCnGRwL8JkAmYx1wexT2QGuVXXAtZvRu8ceyuQy"
			  "AhzGkI9HdADu5YAJsUaLknDUV5hmundXQY8lhwQnKFXW0rl0H8DoFiPQErFmcKI6PA9NVGK/LSiqHqesNeg0wqCTxMmeT6pqI7FH9fDO"
			  "CaBpwUJ4t5aKoytQ75t13OfUM7tfLlVkFZtI3RndhivxLA5d4Elt/Gv3RhDu6Eiom5NZ/pwRvP26Sox+FWapz3DGCil70H1iGSYu8ENa"
			  "afUBCGGhT4sk7kl7zS6XiEpMobLq3A==");
	ASSERT_FALSE(!(!ec));

	alg.verify("testdata", res, ec);
	ASSERT_FALSE(!(!ec));
}

TEST(OpenSSLErrorTest, RS256SignErrorCode) {
	jwt::algorithm::rs256 alg{rsa_pub_key, rsa_priv_key};
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_generation_error::create_context_failed},
		{&fail_EVP_DigestInit, 1, jwt::error::signature_generation_error::signinit_failed},
		{&fail_EVP_DigestUpdate, 1, jwt::error::signature_generation_error::signupdate_failed},
		{&fail_EVP_SignFinal, 1, jwt::error::signature_generation_error::signfinal_failed}};

	run_multitest(mapping, [&alg](std::error_code& ec) {
		auto res = alg.sign("testdata", ec);
		ASSERT_EQ(res, "");
	});
}

TEST(OpenSSLErrorTest, RS256VerifyErrorCode) {
	jwt::algorithm::rs256 alg{rsa_pub_key, rsa_priv_key};
	auto signature = jwt::base::decode<jwt::alphabet::base64>(
		"oCJUeLmIKKVVE/UWhEL/Malx0l9TCoXWNAS2z9o8ZYNaS4POIeadZWeUbLdICx3SCJCnGRwL8JkAmYx1wexT2QGuVXXAtZvRu8ceyuQy"
		"AhzGkI9HdADu5YAJsUaLknDUV5hmundXQY8lhwQnKFXW0rl0H8DoFiPQErFmcKI6PA9NVGK/LSiqHqesNeg0wqCTxMmeT6pqI7FH9fDO"
		"CaBpwUJ4t5aKoytQ75t13OfUM7tfLlVkFZtI3RndhivxLA5d4Elt/Gv3RhDu6Eiom5NZ/pwRvP26Sox+FWapz3DGCil70H1iGSYu8ENa"
		"afUBCGGhT4sk7kl7zS6XiEpMobLq3A==");
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_verification_error::create_context_failed},
		{&fail_EVP_DigestInit, 1, jwt::error::signature_verification_error::verifyinit_failed},
		{&fail_EVP_DigestUpdate, 1, jwt::error::signature_verification_error::verifyupdate_failed},
		{&fail_EVP_VerifyFinal, 1, jwt::error::signature_verification_error::verifyfinal_failed}};

	run_multitest(mapping, [&alg, &signature](std::error_code& ec) { alg.verify("testdata", signature, ec); });
}

TEST(OpenSSLErrorTest, LoadECDSAPrivateKeyFromString) {
	std::vector<multitest_entry> mapping{
		{&fail_BIO_new, 1, jwt::error::ecdsa_error::create_mem_bio_failed},
		{&fail_BIO_write, 1, jwt::error::ecdsa_error::load_key_bio_write},
		{&fail_PEM_read_bio_PrivateKey, 1, jwt::error::ecdsa_error::load_key_bio_read},
#ifdef JWT_OPENSSL_3_0
		{&fail_EVP_PKEY_private_check, 1, jwt::error::ecdsa_error::invalid_key},
		{&fail_EVP_PKEY_CTX_new_from_pkey, 1, jwt::error::ecdsa_error::create_context_failed},
#else
		{&fail_EC_KEY_check_key, 1, jwt::error::ecdsa_error::invalid_key},
		{&fail_EVP_PKEY_get1_EC_KEY, 1, jwt::error::ecdsa_error::invalid_key},
#endif
	};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::algorithm::es256 alg{"", ecdsa256_priv_key};
			FAIL(); // Should never reach this
		} catch (const std::system_error& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, LoadECDSAPublicKeyFromString) {
	std::vector<multitest_entry> mapping{
		{&fail_BIO_new, 1, jwt::error::ecdsa_error::create_mem_bio_failed},
		{&fail_BIO_write, 1, jwt::error::ecdsa_error::load_key_bio_write},
		{&fail_PEM_read_bio_PUBKEY, 1, jwt::error::ecdsa_error::load_key_bio_read},
#ifdef JWT_OPENSSL_3_0
		{&fail_EVP_PKEY_public_check, 1, jwt::error::ecdsa_error::invalid_key},
		{&fail_EVP_PKEY_CTX_new_from_pkey, 1, jwt::error::ecdsa_error::create_context_failed},
#else
		{&fail_EC_KEY_check_key, 1, jwt::error::ecdsa_error::invalid_key},
		{&fail_EVP_PKEY_get1_EC_KEY, 1, jwt::error::ecdsa_error::invalid_key},
#endif
	};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::algorithm::es256 alg{ecdsa256_pub_key, ""};
			FAIL(); // Should never reach this
		} catch (const std::system_error& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, ECDSACertificate) {
	std::vector<multitest_entry> mapping {
		{&fail_BIO_new, 1, jwt::error::ecdsa_error::create_mem_bio_failed},
#if !defined(LIBRESSL_VERSION_NUMBER) || LIBRESSL_VERSION_NUMBER < 0x3050300fL
			{&fail_BIO_write, 1, jwt::error::ecdsa_error::load_key_bio_write},
#else
			{&fail_BIO_write, 1, jwt::error::rsa_error::write_key_failed},
#endif
			{&fail_PEM_read_bio_PUBKEY, 1, jwt::error::ecdsa_error::load_key_bio_read},
			// extract_pubkey_from_cert
			{&fail_BIO_new, 2, jwt::error::rsa_error::create_mem_bio_failed},
			{&fail_PEM_read_bio_X509, 1, jwt::error::rsa_error::cert_load_failed},
			{&fail_X509_get_pubkey, 1, jwt::error::rsa_error::get_key_failed},
			{&fail_PEM_write_bio_PUBKEY, 1, jwt::error::rsa_error::write_key_failed}, {
			&fail_BIO_ctrl, 1, jwt::error::rsa_error::convert_to_pem_failed
		}
	};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::algorithm::es256 alg{ecdsa256_certificate};
			FAIL(); // Should never reach this
		} catch (const std::system_error& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, ES256Reference) {
	jwt::algorithm::es256 alg{ecdsa256_pub_key, ecdsa256_priv_key};
	std::error_code ec;
	auto res = alg.sign("testdata", ec);
	ASSERT_FALSE(!(!ec));

	alg.verify("testdata", res, ec);
	ASSERT_FALSE(!(!ec));
}

TEST(OpenSSLErrorTest, ES256SignErrorCode) {
	jwt::algorithm::es256 alg{ecdsa256_pub_key, ecdsa256_priv_key};
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_generation_error::create_context_failed},
		{&fail_EVP_DigestSignInit, 1, jwt::error::signature_generation_error::signinit_failed},
		{&fail_EVP_DigestUpdate, 1, jwt::error::signature_generation_error::digestupdate_failed},
		{&fail_EVP_DigestSignFinal, 1, jwt::error::signature_generation_error::signfinal_failed},
		{&fail_EVP_DigestSignFinal, 2, jwt::error::signature_generation_error::signfinal_failed},
		{&fail_d2i_ECDSA_SIG, 1, jwt::error::signature_generation_error::signature_decoding_failed},
	};

	run_multitest(mapping, [&alg](std::error_code& ec) {
		auto res = alg.sign("testdata", ec);
		ASSERT_EQ(res, "");
	});
}

TEST(OpenSSLErrorTest, ES256VerifyErrorCode) {
	jwt::algorithm::es256 alg{ecdsa256_pub_key, ecdsa256_priv_key};
	auto signature = jwt::base::decode<jwt::alphabet::base64>(
		"aC/NqyHfPw5FDA0yRAnrbkrAlXjsr0obRkCg/HgP+77QYJrAg6YKkKoJwMXjUX8fQrxXKTN7em5L9dtmOep37Q==");
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_verification_error::create_context_failed},
		{&fail_EVP_DigestVerifyInit, 1, jwt::error::signature_verification_error::verifyinit_failed},
		{&fail_EVP_DigestUpdate, 1, jwt::error::signature_verification_error::verifyupdate_failed},
		{&fail_EVP_DigestVerifyFinal, 1, jwt::error::signature_verification_error::invalid_signature},
		{&fail_ECDSA_SIG_new, 1, jwt::error::signature_verification_error::create_context_failed},
		{&fail_i2d_ECDSA_SIG, 1, jwt::error::signature_verification_error::signature_encoding_failed},
		{&fail_i2d_ECDSA_SIG, 2, jwt::error::signature_verification_error::signature_encoding_failed},
	};

	run_multitest(mapping, [&alg, &signature](std::error_code& ec) { alg.verify("testdata", signature, ec); });
}

TEST(OpenSSLErrorTest, PS256Reference) {
	jwt::algorithm::ps256 alg{rsa_pub_key, rsa_priv_key};
	std::error_code ec;
	auto res = alg.sign("testdata", ec);
	ASSERT_FALSE(!(!ec));

	alg.verify("testdata", res, ec);
	ASSERT_FALSE(!(!ec));
}

TEST(OpenSSLErrorTest, PS256SignErrorCode) {
	jwt::algorithm::ps256 alg{rsa_pub_key, rsa_priv_key};
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_generation_error::create_context_failed},
		{&fail_EVP_DigestSignInit, 1, jwt::error::signature_generation_error::signinit_failed},
		{&fail_EVP_DigestUpdate, 1, jwt::error::signature_generation_error::digestupdate_failed},
		{&fail_EVP_DigestSignFinal, 1, jwt::error::signature_generation_error::signfinal_failed},
		//TODO: EVP_PKEY_CTX_set_rsa_padding, EVP_PKEY_CTX_set_rsa_pss_saltlen
	};

	run_multitest(mapping, [&alg](std::error_code& ec) {
		auto res = alg.sign("testdata", ec);
		ASSERT_EQ(res, "");
	});
}

TEST(OpenSSLErrorTest, PS256VerifyErrorCode) {
	jwt::algorithm::ps256 alg{rsa_pub_key, rsa_priv_key};
	std::string signature =
		"LMiWCiW0a/"
		"mbU6LK8EZaDQ6TGisqfD+LF46zUbzjhFt02J9yVuf3ZDNTdRgLKKP8nCJUx0SN+5CS2YD268Ioxau5bWs49RVCxtID5DcRpJlSo+Vk+"
		"dCmwxhQWHX8HNh3o7kBK5H8fLeTeupuSov+0hH3+"
		"GRrYJqZvCdbcadi6amNKCfeIl6a5mp2VCM55NsPoRxsmSzc1G7AHWb1ckOCsm3KY5BL6B074bHgoqO3yaLlKWLAcy4OYyRpJ/wnZQ9PPrhwdq/"
		"B59uW3x1QUCKYKgZeqZOoqIP1YgLwvEpPtXYutQCFr4eBKgV7vdtE0wgHR43ka16fi5L4SyaZv53NCg==";
	signature = jwt::base::decode<jwt::alphabet::base64>(signature);
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_verification_error::create_context_failed},
		{&fail_EVP_DigestVerifyInit, 1, jwt::error::signature_verification_error::verifyinit_failed},
		{&fail_EVP_DigestUpdate, 1, jwt::error::signature_verification_error::verifyupdate_failed},
		{&fail_EVP_DigestVerifyFinal, 1, jwt::error::signature_verification_error::verifyfinal_failed},
	};

	run_multitest(mapping, [&alg, &signature](std::error_code& ec) { alg.verify("testdata", signature, ec); });
}

#if !defined(JWT_OPENSSL_1_0_0) && !defined(JWT_OPENSSL_1_1_0)
TEST(OpenSSLErrorTest, EdDSAKey) {
	std::vector<multitest_entry> mapping{
		// load_private_key_from_string
		{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
		{&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
		{&fail_PEM_read_bio_PrivateKey, 1, jwt::error::rsa_error::load_key_bio_read},
		// load_public_key_from_string
		{&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
		{&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
		// { &fail_PEM_read_bio_PUBKEY, 1, jwt::error::rsa_error::load_key_bio_read }
	};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::algorithm::ed25519 alg{ed25519_pub_key, ed25519_priv_key};
			FAIL(); // Should never reach this
		} catch (const std::system_error& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, EdDSACertificate) {
	std::vector<multitest_entry> mapping{// load_public_key_from_string
										 {&fail_BIO_new, 1, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_BIO_write, 1, jwt::error::rsa_error::load_key_bio_write},
										 {&fail_PEM_read_bio_PUBKEY, 1, jwt::error::rsa_error::load_key_bio_read},
										 // extract_pubkey_from_cert
										 {&fail_BIO_new, 2, jwt::error::rsa_error::create_mem_bio_failed},
										 {&fail_PEM_read_bio_X509, 1, jwt::error::rsa_error::cert_load_failed},
										 {&fail_X509_get_pubkey, 1, jwt::error::rsa_error::get_key_failed},
										 {&fail_PEM_write_bio_PUBKEY, 1, jwt::error::rsa_error::write_key_failed},
										 {&fail_BIO_ctrl, 1, jwt::error::rsa_error::convert_to_pem_failed}};

	run_multitest(mapping, [](std::error_code& ec) {
		try {
			jwt::algorithm::ed25519 alg{ed25519_certificate};
			FAIL(); // Should never reach this
		} catch (const std::system_error& e) { ec = e.code(); }
	});
}

TEST(OpenSSLErrorTest, Ed25519Reference) {
	jwt::algorithm::ed25519 alg{ed25519_pub_key, ed25519_priv_key};
	std::error_code ec;
	auto res = alg.sign("testdata", ec);
	ASSERT_FALSE(!(!ec));

	alg.verify("testdata", res, ec);
	ASSERT_FALSE(!(!ec));
}

TEST(OpenSSLErrorTest, Ed25519SignErrorCode) {
	jwt::algorithm::ed25519 alg{ed25519_pub_key, ed25519_priv_key};
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_generation_error::create_context_failed},
		{&fail_EVP_DigestSignInit, 1, jwt::error::signature_generation_error::signinit_failed},
		{&fail_EVP_DigestSign, 1, jwt::error::signature_generation_error::signfinal_failed}};

	run_multitest(mapping, [&alg](std::error_code& ec) {
		auto res = alg.sign("testdata", ec);
		ASSERT_EQ(res, "");
	});
}

TEST(OpenSSLErrorTest, Ed25519VerifyErrorCode) {
	jwt::algorithm::ed25519 alg{ed25519_pub_key, ed25519_priv_key};
	auto signature = jwt::base::decode<jwt::alphabet::base64>(
		"aC/NqyHfPw5FDA0yRAnrbkrAlXjsr0obRkCg/HgP+77QYJrAg6YKkKoJwMXjUX8fQrxXKTN7em5L9dtmOep37Q==");
	std::vector<multitest_entry> mapping{
		{&fail_EVP_MD_CTX_new, 1, jwt::error::signature_verification_error::create_context_failed},
		{&fail_EVP_DigestVerifyInit, 1, jwt::error::signature_verification_error::verifyinit_failed},
		{&fail_EVP_DigestVerify, 1, jwt::error::signature_verification_error::verifyfinal_failed}};

	run_multitest(mapping, [&alg, &signature](std::error_code& ec) { alg.verify("testdata", signature, ec); });
}
#endif
#endif
#endif
#endif
