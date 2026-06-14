# Mobile API Documentation

This document provides the necessary API end-points for integrating the Flutter mobile app with Sydkic.

**Base URL**: `https://sydkic.com/api`

---

## 1. Authentication

### Login
Authenticate a user and retrieve an API token.

- **URL**: `/auth/login`
- **Method**: `POST`
- **Request Body**:
  ```json
  {
    "email": "user@example.com",
    "password": "yourpassword",
    "device_name": "iPhone 15 Pro"
  }
  ```
- **Response**:
  ```json
  {
    "token": "1|abc123token...",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "admin",
      "tenant_id": 1
    },
    "tenants": [
      { "id": 1, "name": "Business A" },
      { "id": 2, "name": "Business B" }
    ]
  }
  ```

### Logout
Revoke the current access token.

- **URL**: `/auth/logout`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <your_token>`

---

## 2. Business (Tenant) Management

### User Profile (Current)
Retrieve information about the authenticated user.

- **URL**: `/auth/user`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <your_token>`
- **Response**:
  ```json
  {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "admin",
    "tenant_id": 1,
    "active_tenant": {
      "id": 1,
      "name": "Business A",
      "logo": "https://..."
    }
  }
  ```

### List Businesses
Get all businesses the authenticated user belongs to.

- **URL**: `/tenants`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <your_token>`
- **Response**:
  ```json
  {
    "tenants": [
      { "id": 1, "name": "Business A", "logo": "https://..." },
      { "id": 2, "name": "Business B", "logo": null }
    ],
    "active_tenant_id": 1
  }
  ```

### Select (Switch) Business
Change the active business context for the user.

- **URL**: `/tenants/{id}/select`
- **Method**: `POST`
- **Headers**: `Authorization: Bearer <your_token>`
- **Response**:
  ```json
  {
    "message": "Business switched successfully",
    "tenant": {
      "id": 2,
      "name": "Business B"
    }
  }
  ```

---

## 3. Dashboard

### Get Dashboard Data
Retrieve KPIs and activity for the active business.

- **URL**: `/dashboard`
- **Method**: `GET`
- **Headers**: `Authorization: Bearer <your_token>`
- **Response**:
  ```json
  {
    "business_pulse": {
      "new_leads_today": 5,
      "leads_trend": 2,
      "awaiting_reply": 3,
      "hot_leads": 12
    },
    "smart_alerts": [
      {
        "type": "urgent",
        "title": "Urgent Follow-up",
        "message": "3 hot leads need attention."
      }
    ],
    "pipeline_snapshot": {
      "name": "Sales Pipeline",
      "stages": [
        { "name": "New", "count": 10, "color": "#3b82f6" },
        { "name": "Contacted", "count": 5, "color": "#10b981" }
      ],
      "total_value": 25000.0
    },
    "recent_activities": [
      {
        "type": "stage_change",
        "message": "Jane Doe moved to Contacted",
        "time": "2 hours ago"
      }
    ],
    "is_chatbot_enabled": true
  }
  ```
