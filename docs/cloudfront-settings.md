# AWS Cloudfront settings

## Hints

### Why is my 400, 401, 500, .. response cached? 

Cloudfront caches 4xx and 5xx responses (by default) for 5 minutes. For an API this might be an puzzling behavior.
To avoid this goto "Cloudfront Distribution" -> "Error Pages" -> "Create Custom Error Page". 

Choose your HTTP StatusCodes and set the "Error Caching Minimum TTL" to 0 (or any value you want). 