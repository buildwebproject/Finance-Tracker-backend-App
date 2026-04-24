# Auth API (Google + Twilio OTP) for Flutter

This document covers the authentication APIs available in this backend and how to use them from Flutter.

## Base URL

- Local web: `http://localhost:8000`
- Android emulator: `http://10.0.2.2:8000`
- iOS simulator: `http://localhost:8000`

Use `Content-Type: application/json` for all requests below.

## Public Auth Endpoints

These endpoints do **not** require Bearer token:

- `POST /api/auth/google`
- `POST /api/auth/otp/send`
- `POST /api/auth/otp/verify`
- `POST /api/auth/login` (username/password login)

All other `/api/*` endpoints require:

`Authorization: Bearer <access_token>`

## Authenticated Profile/Session Endpoints

These endpoints require Bearer token:

- `GET /api/auth/me`
- `PUT /api/auth/profile`
- `PATCH /api/auth/profile`
- `POST /api/auth/logout`

## 1) Google Login

### Endpoint

`POST /api/auth/google`

### Request body

```json
{
  "id_token": "GOOGLE_ID_TOKEN_FROM_FLUTTER"
}
```

### Success response (`200`)

```json
{
  "token_type": "Bearer",
  "access_token": "payload.signature",
  "expires_at": "2026-03-06T10:00:00+00:00",
  "user": {
    "id": 1,
    "identifier": "dev.patel@example.com",
    "roles": ["ROLE_USER"],
    "full_name": "Dev Patel",
    "auth_provider": "google",
    "google_subject": "123456789012345678901",
    "google_email": "dev.patel@example.com",
    "google_name": "Dev Patel",
    "google_picture_url": "https://lh3.googleusercontent.com/...",
    "google_email_verified": true,
    "twilio_phone_number": null,
    "twilio_channel": null,
    "last_social_login_at": "2026-03-06T09:45:00+00:00",
    "date_of_birth": "1995-08-15",
    "gender": "male",
    "profile": "Senior product designer and investor."
  }
}
```

### Common errors

- `400` `id_token is required.`
- `400` `JSON request body is required.` / `Invalid JSON payload.`
- `401` invalid token, expired token, audience mismatch, unverified Google account, or linked to another Google account
- `403` `User account is disabled.`
- `500` `GOOGLE_OAUTH_CLIENT_ID is not configured.`

## 2) Twilio OTP - Send Code

### Endpoint

`POST /api/auth/otp/send`

### Request body

```json
{
  "phone": "+919876543210"
}
```

### Success response (`200`)

```json
{
  "message": "OTP sent successfully.",
  "phone": "+919876543210"
}
```

### Phone format rules

- Must be valid E.164 (`+` + country code + number)
- Backend normalizes spaces/dashes/brackets and auto-adds `+` if missing
- Final validation: `^\+[1-9]\d{7,14}$`

### Common errors

- `400` invalid phone / invalid JSON
- `500` `Twilio Verify credentials are not configured.`
- `502` Twilio API/network errors

## 3) Twilio OTP - Verify Code (Login)

### Endpoint

`POST /api/auth/otp/verify`

### Request body

```json
{
  "phone": "+919876543210",
  "otp": "123456"
}
```

### OTP rules

- Digits only
- Length: 4 to 8 (`^\d{4,8}$`)

### Success response (`200`)

Same response structure as Google login (token + `user` object), but usually:

- `auth_provider = "twilio"`
- `twilio_phone_number` and `twilio_channel` are filled

### Common errors

- `400` invalid phone / invalid otp / invalid JSON
- `401` `Invalid or expired OTP.`
- `403` `User account is disabled.`
- `500` Twilio configuration missing
- `502` Twilio API/network errors

## 4) Username/Password Login (Optional)

### Endpoint

`POST /api/auth/login`

### Request body

```json
{
  "username": "admin",
  "password": "your_password"
}
```

### Success response (`200`)

```json
{
  "token_type": "Bearer",
  "access_token": "payload.signature",
  "expires_at": "2026-03-06T10:00:00+00:00"
}
```

### Error

- `401` `Invalid credentials.`

## Access Token Notes

- Token type is always `Bearer`
- Expiry controlled by env `API_TOKEN_TTL` (currently `3600` seconds)
- Token format is app-specific HMAC token (`payload.signature`), not a standard JWT
- If token is invalid/expired, protected APIs return unauthorized

## Flutter Integration Example (Dio)

```dart
import 'package:dio/dio.dart';

class AuthApi {
  AuthApi(this._dio);
  final Dio _dio;

  Future<Map<String, dynamic>> loginWithGoogle(String idToken) async {
    final res = await _dio.post(
      '/api/auth/google',
      data: {'id_token': idToken},
    );
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> sendOtp(String phone) async {
    await _dio.post('/api/auth/otp/send', data: {'phone': phone});
  }

  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) async {
    final res = await _dio.post(
      '/api/auth/otp/verify',
      data: {'phone': phone, 'otp': otp},
    );
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateProfile({
    required String email,
    required String fullName,
    String? dateOfBirth,
    String? gender,
    String? profile,
  }) async {
    final res = await _dio.put(
      '/api/auth/profile',
      data: {
        'email': email,
        'full_name': fullName,
        'date_of_birth': dateOfBirth,
        'gender': gender,
        'profile': profile,
      },
    );
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> me() async {
    final res = await _dio.get('/api/auth/me');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> logout() async {
    await _dio.post('/api/auth/logout');
  }
}
```

Set base URL once:

```dart
final dio = Dio(BaseOptions(
  baseUrl: 'http://10.0.2.2:8000', // Android emulator
  headers: {'Content-Type': 'application/json'},
));
```

For protected APIs after login:

```dart
dio.options.headers['Authorization'] = 'Bearer $accessToken';
```

## 5) Get Current User (`me`)

### Endpoint

`GET /api/auth/me`

### Success response (`200`)

```json
{
  "user": {
    "id": 1,
    "identifier": "dev.patel@example.com",
    "roles": ["ROLE_USER"],
    "full_name": "Dev Patel",
    "email": "dev.patel@example.com",
    "auth_provider": "google",
    "date_of_birth": "1995-08-15",
    "gender": "male",
    "profile": "Senior product designer and investor."
  }
}
```

### Common errors

- `401` invalid/expired/revoked token

## 6) Update Profile (Email + Full Name)

### Endpoint

`PUT /api/auth/profile` (or `PATCH /api/auth/profile`)

### Request body

```json
{
  "email": "dev.patel@example.com",
  "full_name": "Dev Patel",
  "date_of_birth": "1995-08-15",
  "gender": "male",
  "profile": "Senior product designer and investor."
}
```

### Success response (`200`)

```json
{
  "message": "Profile updated successfully.",
  "user": {
    "id": 1,
    "identifier": "dev.patel@example.com",
    "roles": ["ROLE_USER"],
    "full_name": "Dev Patel",
    "email": "dev.patel@example.com",
    "auth_provider": "google",
    "date_of_birth": "1995-08-15",
    "gender": "male",
    "profile": "Senior product designer and investor."
  }
}
```

### Common errors

- `400` `email is required and must be valid.`
- `400` `full_name is required and must be between 2 and 255 characters.`
- `400` `date_of_birth must be a valid date in YYYY-MM-DD format.`
- `400` `gender must be one of: male, female, other.`
- `400` `profile must be a valid text value up to 5000 characters.`
- `409` `email is already in use.`
- `401` invalid/expired token

## 7) Logout

### Endpoint

`POST /api/auth/logout`

### Request body

No request body required.

### Success response (`200`)

```json
{
  "message": "Logged out successfully."
}
```

### Notes

- Current access token is revoked immediately on backend.
- After logout, same token cannot be used for protected APIs.

## Flutter Google Sign-In Hint

After Google sign-in in Flutter, send `idToken` to backend:

```dart
final account = await googleSignIn.signIn();
final auth = await account!.authentication;
final idToken = auth.idToken; // send this to /api/auth/google
```

## Data Stored in User Table (Social Auth Fields)

Backend stores/updates these fields for social auth:

- `fullName` (set automatically from Google name if empty)
- `authProvider`
- `googleSubject`
- `googleEmail`
- `googleName`
- `googlePictureUrl`
- `googleEmailVerified`
- `twilioPhoneNumber`
- `twilioChannel`
- `lastSocialLoginAt`
- `dateOfBirth`
- `gender`
- `profile`

This allows you to show provider-specific profile info in Flutter.
