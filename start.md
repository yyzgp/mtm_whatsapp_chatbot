# MTM Sales CRM - Quick Start Guide

## Prerequisites
- PHP 8.2 (XAMPP: `C:\xampp\php\php.exe`)
- MySQL (XAMPP MySQL service)
- Node.js + npm (for compiling CSS/JS)

## First Time Setup

1. **Start MySQL** from XAMPP Control Panel

2. **Run Migrations & Seed**
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Compile Frontend Assets**
   ```bash
   npm install
   npm run dev        # for development (watch mode)
   npm run build      # for production
   ```

4. **Create Storage Link**
   ```bash
   php artisan storage:link
   ```

5. **Start the Server**
   ```bash
   php artisan serve
   ```

6. **Start Queue Worker** (required for WhatsApp + AI)
   ```bash
   php artisan queue:work --queue=ai,whatsapp,default
   ```

7. **Start Reverb** (for real-time chat)
   ```bash
   php artisan reverb:start
   ```

8. **Start Scheduler** (for AI auto-resume)
   ```bash
   php artisan schedule:work
   ```

## Default Login Credentials

| Role           | Email                   | Password       |
|----------------|-------------------------|----------------|
| Admin          | admin@mtmcrm.com        | Admin@12345    |
| Sales Manager  | manager@mtmcrm.com      | Manager@12345  |
| Sales Agent    | agent@mtmcrm.com        | Agent@12345    |

## Key URLs

| Page       | URL                              |
|------------|----------------------------------|
| Dashboard  | http://localhost:8000/dashboard  |
| Customers  | http://localhost:8000/customers  |
| Chat       | http://localhost:8000/chat       |
| Users      | http://localhost:8000/users      |
| Roles      | http://localhost:8000/roles      |
| Reports    | http://localhost:8000/reports    |
| Settings   | http://localhost:8000/settings   |

## WhatsApp Integration Setup

1. Go to **Settings → WhatsApp** in the CRM
2. Add your WhatsApp Business account:
   - Phone Number ID (from Meta Developer Console)
   - WABA ID (WhatsApp Business Account ID)
   - Access Token (Permanent token from Meta)
3. Copy the **Verify Token** and **Webhook URL** shown
4. In Meta Developer Console, configure the webhook:
   - URL: `https://your-domain.com/api/webhook/whatsapp`
   - Verify Token: (copy from Settings)
   - Subscribe to: `messages`, `message_status`
5. Make sure your server is publicly accessible (use ngrok for local testing)

## AI Setup

1. Add your OpenAI API key to `.env`:
   ```
   OPENAI_API_KEY=sk-...
   ```
2. In **Settings → WhatsApp**, enable AI for each account and set the system prompt
3. In **Settings → AI Settings**, configure the model and parameters

## AI Auto-Reply Logic

- When a customer messages: AI replies immediately
- When an agent replies: AI is paused for **1 hour**
- After 1 hour of agent inactivity: AI resumes automatically
- Per-conversation AI toggle available in the Chat module

## Mobile App (MTMSalesApp)

The Expo React Native app lives at `c:\Projects\MTMSalesApp`.

### Setup

```bash
cd c:\Projects\MTMSalesApp
npm install
```

### Configure API Connection

Edit `src/constants/config.ts` — set your **local network IP** (not localhost, your phone must reach the PC):

```ts
// Find your IP: run `ipconfig`, look for IPv4 Address
export const API_URL = 'http://192.168.x.x:8000/api';
export const WS_HOST = '192.168.x.x';
export const WS_PORT = 8080;
export const WS_KEY = 'irimxlwkt3xkxz042oyb';  // From .env REVERB_APP_KEY
```

Make sure the Laravel server is started with `--host=0.0.0.0` so mobile devices can connect:
```bash
php artisan serve --host=0.0.0.0
```

### Run

```bash
npx expo start
```

Scan the QR code with **Expo Go** on your phone. Phone and PC must be on the same WiFi.

### Mobile API Endpoints

#### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | Login, returns Sanctum token |
| GET | `/api/auth/me` | Get current user |
| POST | `/api/auth/logout` | Logout, clears push token |
| POST | `/api/auth/push-token` | Register Expo push token |

#### Conversations
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/conversations` | List conversations (filterable) |
| GET | `/api/conversations/{id}` | Conversation detail |
| GET | `/api/conversations/{id}/messages` | Paginated messages |
| POST | `/api/conversations/{id}/messages` | Send message |
| PATCH | `/api/conversations/{id}/status` | Update status |
| POST | `/api/conversations/{id}/toggle-ai` | Toggle AI replies |

#### Customers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/customers` | List customers (filterable) |
| GET | `/api/customers/{id}` | Customer detail |
| PATCH | `/api/customers/{id}/status` | Update status with notes |

All endpoints except auth/login and webhook require `Authorization: Bearer {token}` header.

### Mobile App Screens

| Screen | Path | Description |
|--------|------|-------------|
| Login | `(auth)/login` | Email/password sign in |
| Chats | `(tabs)/index` | Conversation list with search & filters |
| Customers | `(tabs)/customers` | Customer list with status filters |
| Profile | `(tabs)/profile` | User info & sign out |
| Chat Detail | `chat/[id]` | Messages, send, status/AI toggle |
| Customer Detail | `customer/[id]` | Info, status picker, linked chats |

## WhatsApp Multi-Number (WABA)

Each WhatsApp Business Account (WABA) can have **multiple phone numbers**. In Settings:

- **Account level**: Name, WABA ID, Access Token, Verify Token
- **Phone Number level** (nested under account): Name, Phone Number ID, AI on/off, AI Prompt, Greeting Message

Teams are assigned to a **phone number**, not an account. Agents only see conversations from their team's phone number.

### Greeting Messages

- Configurable per phone number in Settings
- Auto-sent to first-time messengers before AI reply
- Auto-sent to returning customers after configurable inactivity (default: 30 min)
- Cooldown is a global WhatsApp setting

## Architecture

```
MTMSalesCRM (Laravel)
├── Web UI ─── Livewire + Alpine.js (admin dashboard)
├── API ────── Sanctum-protected REST (mobile app)
├── WhatsApp ─ Webhook → Queue → Greeting → AI → Reply
├── Realtime ─ Laravel Reverb (WebSocket)
└── Push ───── Expo Push Notifications

MTMSalesApp (Expo React Native)
├── (auth) ─── Login screen
├── (tabs) ─── Chats / Customers / Profile
├── chat/[id] ─ Chat detail with send
└── customer/[id] ─ Customer detail with status update

app/
├── Enums/           — CustomerStatus, Priority, ActivityType, etc.
├── Events/          — WhatsAppMessageReceived, AgentRepliedToChat
├── Jobs/            — ProcessIncomingWhatsAppMessage, SendAiReply
├── Listeners/       — PauseAiOnAgentReply, LogCustomerStatusChange
├── Livewire/        — All UI components
│   ├── Chat/
│   ├── Customers/
│   ├── Dashboard/
│   ├── Reports/
│   ├── Roles/
│   ├── Settings/
│   └── Users/
├── Models/          — All Eloquent models
└── Services/
    ├── Ai/          — OpenAI integration, context management
    ├── Chat/        — Conversation & message services
    ├── WhatsApp/    — Meta Cloud API integration, webhook parser
    └── PushNotificationService — Expo push to mobile agents
```
