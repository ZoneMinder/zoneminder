# Signing Tokens

## Custom Signature Algorithms

The libraries design is open so you can implement your own algorithms, see [existing examples](https://github.com/Thalhammer/jwt-cpp/blob/73f23419235661e89a304ba5ab09d6714fb8dd94/include/jwt-cpp/jwt.h#L874) for ideas.

```cpp
struct your_algorithm{
	std::string sign(const std::string& /*unused*/, std::error_code& ec) const {
		ec.clear();
        // CALL YOUR METHOD HERE
		return {};
	}
	void verify(const std::string& /*unused*/, const std::string& signature, std::error_code& ec) const {
		ec.clear();
		if (!signature.empty()) { ec = error::signature_verification_error::invalid_signature; }
        
        // CALL YOUR METHOD HERE
	}
	std::string name() const { return "your_algorithm"; }
};
```

Then everything else is the same, just pass in your implementation such as:


```cpp
auto token = jwt::create()
                .set_id("custom-algo-example")
                .set_issued_at(std::chrono::system_clock::now())
                .set_expires_at(std::chrono::system_clock::now() + std::chrono::seconds{36000})
                .set_payload_claim("sample", jwt::claim(std::string{"test"}))
                .sign(your_algorithm{/* what ever you want */});
```
