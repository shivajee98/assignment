# Hierarchy Chat - Android App

Native Android app for hierarchical messaging.

## Features

- Simple chat interface
- Hierarchical messaging (downward only)
- Role-based user list

## Setup

1. Open in Android Studio
2. Update API URL in `app/build.gradle`:
   ```groovy
   buildConfigField "String", "API_BASE_URL", '"https://your-backend.onrender.com/api/"'
   ```
3. Build and run

## Build APK

```bash
./gradlew assembleDebug
```

APK location: `app/build/outputs/apk/debug/app-debug.apk`

## Project Structure

```
app/src/main/java/com/hierarchychat/
├── activities/
│   ├── SplashActivity.kt
│   ├── LoginActivity.kt
│   ├── MainActivity.kt
│   └── ChatActivity.kt
├── adapters/
│   ├── ConversationAdapter.kt
│   └── MessageAdapter.kt
├── api/
│   ├── ApiClient.kt
│   └── ApiService.kt
├── models/
│   └── Models.kt
└── utils/
    ├── SessionManager.kt
    └── Utils.kt
```

## Login

Use credentials from backend seeder:
- Email: superadmin@example.com
- Password: password123
