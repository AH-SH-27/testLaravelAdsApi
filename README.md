Ads API Laravel Assessment
Overview

This Laravel application implements a REST API for managing ads with dynamic fields based on categories fetched from the API. The system supports creating, retrieving, and listing ads with validation for dynamic fields, using a MySQL database for storage.

The application is fully functional, meets the assessment requirements, and has been thoroughly tested.

Features

Fetches categories and category fields dynamically from the API.

Builds dynamic validation rules for ads based on retrieved category fields.

Allows creating ads with required and optional dynamic fields.

Retrieves user ads with pagination.

Authentication using Laravel Sanctum (token-based).

Caching implemented to reduce API calls and improve performance.

Fully tested using PHPUnit feature tests.

Tests

The application was tested with Laravel’s test suite, covering the following scenarios:

Authenticated users can create ads with dynamic fields. ✅

Ad creation fails if mandatory dynamic fields are missing. ✅

Integer fields are validated for min/max values and reject strings. ✅

Float fields validated as numeric. ✅

Enum fields validated against allowed options. ✅

String fields validated for min/max length. ✅

Boolean fields support multiple input formats (true/false, yes/no, 1/0). ✅

Fields from other categories are ignored. ✅

Optional dynamic fields can be omitted. ✅

Unauthenticated users cannot create ads. ✅

Ad creation fails with invalid category. ✅

Test results:

Tests: 12 passed (47 assertions)
Duration: 1.08s

API Endpoints with Sample Requests and Responses
1. Register User

POST /api/v1/register

Request Body:

{
  "name": "Test User",
  "email": "test3515@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}


Response (201 Created):

{
  "user": {
    "name": "Test User",
    "email": "test3515@example.com",
    "updated_at": "2025-12-14T18:03:49.000000Z",
    "created_at": "2025-12-14T18:03:49.000000Z",
    "id": 9
  },
  "token": "7|S5KupbGuRHE0o4MS8PCrDUXL09WrrxCRJvZdBxNB300ed57c",
  "token_type": "Bearer"
}

2. Login User

POST /api/v1/login

Request Body:

{
  "email": "test6@example.com",
  "password": "password123"
}


Response (200 OK):

{
  "user": {
    "id": 6,
    "name": "Test6 User6",
    "email": "test6@example.com",
    "email_verified_at": null,
    "created_at": "2025-12-14T06:45:12.000000Z",
    "updated_at": "2025-12-14T06:45:12.000000Z"
  },
  "token": "9|eSMed0ZgvxWhCy3dgymwQSo7MFUibd8AMoleqd6rb6240c38",
  "token_type": "Bearer"
}

3. Create Ad

POST /api/v1/ads (requires Bearer token)

Request Body:

{
  "category_id": 51,
  "title": "Toyota Camry 2020",
  "description": "Excellent condition, low mileage",
  "price": 15000
}


Response (201 Created):

{
  "data": {
    "id": 11,
    "title": "Toyota Camry 2020",
    "description": "Excellent condition, low mileage",
    "price": 15000,
    "status": "published",
    "category": {
      "id": 51,
      "name": "Kitchen & Kitchenware",
      "external_id": "239"
    },
    "dynamic_fields": {
      "price": {
        "name": "Price",
        "value": "15000.00",
        "type": "float"
      }
    },
    "created_at": "2025-12-14T18:04:12.000000Z",
    "updated_at": "2025-12-14T18:04:12.000000Z"
  }
}

4. List User Ads

GET /api/v1/my-ads (requires Bearer token)

Response (200 OK):

{
  "data": [
    {
      "id": 11,
      "title": "Toyota Camry 2020",
      "description": "Excellent condition, low mileage",
      "price": 15000,
      "status": "published",
      "category": {
        "id": 51,
        "name": "Kitchen & Kitchenware",
        "external_id": "239"
      },
      "dynamic_fields": {
        "price": {
          "name": "Price",
          "value": "15000.00",
          "type": "float"
        }
      },
      "created_at": "2025-12-14T18:04:12.000000Z",
      "updated_at": "2025-12-14T18:04:12.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/v1/my-ads?page=1",
    "last": "http://localhost:8000/api/v1/my-ads?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost:8000/api/v1/my-ads",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}

5. Get Ad Details

GET /api/v1/ads/{id} (no token required)

Response (200 OK):

{
  "data": {
    "id": 4,
    "title": "Toyota Camry 2020",
    "description": "Excellent condition, low mileage",
    "price": 15000,
    "status": "published",
    "category": {
      "id": 51,
      "name": "Kitchen & Kitchenware",
      "external_id": "239"
    },
    "dynamic_fields": {
      "price": {
        "name": "Price",
        "value": "15000.00",
        "type": "float"
      }
    },
    "created_at": "2025-12-14T17:30:31.000000Z",
    "updated_at": "2025-12-14T17:30:31.000000Z"
  }
}

Database

MySQL database used for persisting users, categories, category fields, field options, and ads.

Migrations included for all tables.

Caching

Implemented

Notes

The application fully matches the assessment requirements.

All API responses and dynamic validations have been tested and verified.

Data is saved correctly in the database and retrieved with proper formatting.

The code follows Laravel best practices for services, seeders, and request validation.
