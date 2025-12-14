# Ads API Laravel Assessment

## Overview

This Laravel application implements a **REST API** for managing ads with **dynamic fields** based on categories fetched from an external API. The system supports creating, retrieving, and listing ads with robust **validation for dynamic fields**, using a **MySQL database** for storage. The application is fully functional, meets the assessment requirements, and has been thoroughly tested.

---

## Features

* **Dynamic Data Ingestion:** Fetches categories and category fields dynamically from the external API.
* **Dynamic Validation:** Builds dynamic validation rules for ads based on retrieved category fields, ensuring data integrity.
* **Ad Creation:** Allows creating ads with required and optional dynamic fields.
* **User Ads:** Retrieves user-specific ads with pagination.
* **Authentication:** Uses **Laravel Sanctum** (token-based) for secure API access.
* **Performance:** **Caching** implemented for category data to reduce external API calls and improve performance.
* **Testing:** Fully tested using **PHPUnit** feature tests.

---

## Testing Results

The application was tested with Laravel's test suite, covering all critical dynamic validation and authentication scenarios.

| Test Scenario | Status |
| :--- | :--- |
| Authenticated users can create ads with dynamic fields | ✅ |
| Ad creation fails if mandatory dynamic fields are missing | ✅ |
| Integer fields validated for min/max values and reject strings | ✅ |
| Float fields validated as numeric | ✅ |
| Enum fields validated against allowed options | ✅ |
| String fields validated for min/max length | ✅ |
| Boolean fields support multiple input formats (true/false, yes/no, 1/0) | ✅ |
| Fields from other categories are ignored | ✅ |
| Optional dynamic fields can be omitted | ✅ |
| Unauthenticated users cannot create ads | ✅ |
| Ad creation fails with invalid category | ✅ |

**Test Results:** * **Tests: 12 passed (47 assertions)**
* **Duration: 1.08s**

---

## API Endpoints with Sample Requests and Responses

### 1. Register User

**POST** `/api/v1/register`

| Component | Example |
| :--- | :--- |
| **Request Body** | `{"name": "Test User", "email": "test3515@example.com", "password": "password123", "password_confirmation": "password123"}` |
| **Response (201 Created)** | `{"user": {"name": "Test User", "email": "test3515@example.com", ...}, "token": "7|S5KupbGuRHE0o4MS8PCrDUXL09WrrxCRJvZdBxNB300ed57c", "token_type": "Bearer"}` |

### 2. Login User

**POST** `/api/v1/login`

| Component | Example |
| :--- | :--- |
| **Request Body** | `{"email": "test6@example.com", "password": "password123"}` |
| **Response (200 OK)** | `{"user": {"id": 6, "name": "Test6 User6", "email": "test6@example.com", ...}, "token": "9|eSMed0ZgvxWhCy3dgymwQSo7MFUibd8AMoleqd6rb6240c38", "token_type": "Bearer"}` |

### 3. Create Ad

**POST** `/api/v1/ads` (requires **Bearer token**)

| Component | Example |
| :--- | :--- |
| **Request Body** | `{"category_id": 51, "title": "Toyota Camry 2020", "description": "Excellent condition, low mileage", "price": 15000}` |
| **Response (201 Created)** | `{"data": {"id": 11, "title": "Toyota Camry 2020", "price": 15000, "category": {"id": 51, "name": "Kitchen & Kitchenware", ...}, "dynamic_fields": {"price": {"name": "Price", "value": "15000.00", "type": "float"}}, ...}}` |

### 4. List User Ads

**GET** `/api/v1/my-ads` (requires **Bearer token**)

| Component | Example |
| :--- | :--- |
| **Response (200 OK)** | `{"data": [{"id": 11, "title": "Toyota Camry 2020", ...}], "links": {"first": "...", "next": null}, "meta": {"current_page": 1, "total": 1}}` |

### 5. Get Ad Details

**GET** `/api/v1/ads/{id}` (no token required)

| Component | Example |
| :--- | :--- |
| **Response (200 OK)** | `{"data": {"id": 4, "title": "Toyota Camry 2020", "price": 15000, "status": "published", "category": {"id": 51, "name": "Kitchen & Kitchenware", ...}, "dynamic_fields": {"price": {"name": "Price", "value": "15000.00", "type": "float"}}, ...}}` |

---

## Backend Architecture Notes

### Database

* A **MySQL** database is used for persisting users, categories, category fields, field options, and ads.
* All necessary database migrations are included.

### Caching

* **Caching** is implemented for fetching categories and category fields definitions.
* This approach significantly **reduces external API calls** and improves application performance, particularly for the high-traffic dynamic validation layer.

---

## Conclusion

The application fully matches the assessment requirements. All API responses and **dynamic validations** have been thoroughly tested and verified, ensuring data integrity and correct retrieval. The code follows Laravel best practices for services, seeders, and request validation.
