# 🌍 Country Currency & Exchange API

A Laravel RESTful API that fetches country data and exchange rates from external APIs, caches them in a MySQL database, and provides CRUD and reporting endpoints.

---

## 🚀 Features

- Fetches countries from [REST Countries API](https://restcountries.com/v2/all?fields=name,capital,region,population,flag,currencies)
- Fetches exchange rates from [Open Exchange Rate API](https://open.er-api.com/v6/latest/USD)
- Stores and updates country data in MySQL
- Computes **estimated GDP** for each country
- Caches global `last_refreshed_at` timestamp
- Generates a **summary image** (top 5 GDPs + total count)
- Full CRUD and status endpoints
- JSON-only responses with consistent error handling

---

## 🧩 Endpoints Overview

| Method | Endpoint | Description |
|--------|-----------|-------------|
| **POST** | `/countries/refresh` | Fetch countries + exchange rates, compute GDP, update DB, and regenerate summary image |
| **GET** | `/countries` | List countries (supports filters and sorting) |
| **GET** | `/countries/{name}` | Get one country by name |
| **DELETE** | `/countries/{name}` | Delete a country record |
| **GET** | `/status` | Return total countries and last refresh timestamp |
| **GET** | `/countries/image` | Serve generated summary image |

---

## ⚙️ Environment Variables

Create a `.env` file in your project root (copy `.env.example`).

You must configure your database connection.

### 🧱 For Local Development

Example `.env` section:

```env
APP_NAME=CountryExchangeAPI
APP_ENV=local
APP_KEY=base64:GENERATE_THIS_WITH_ARTISAN
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=country_exchange_api
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
```

### ☁️ For Railway Deployment

In your Railway service’s “Variables” section:

```
DATABASE_URL=${{ MySQL.MYSQL_URL }}
```

*(Railway automatically injects host, username, and password using this format.)*

---

## 🛠️ Dependencies

| Package | Purpose |
|----------|----------|
| **laravel/framework** | Core framework |
| **guzzlehttp/guzzle** | For calling external APIs |
| **intervention/image** | To generate summary images |
| **mysql** | Database driver |
| **ext-json**, **ext-fileinfo** | PHP extensions |

### Install dependencies

```bash
composer install
```

If you cloned a repo with `composer.lock`, add `--no-scripts` if needed:
```bash
composer install --no-scripts
```

---

## 💻 Local Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/country-exchange-api.git
   cd country-exchange-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Create environment file**
   ```bash
   cp .env.example .env
   ```

4. **Generate app key**
   ```bash
   php artisan key:generate
   ```

5. **Configure `.env`** with your MySQL credentials.

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Link storage (for summary image)**
   ```bash
   php artisan storage:link
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

   Visit → [http://localhost:8000](http://localhost:8000)

---

## 🧮 API Documentation

### 1️⃣ POST `/countries/refresh`

**Purpose:**  
Fetch and cache all countries and exchange rates.

**Response (200)**
```json
{
  "total": 250,
  "last_refreshed_at": "2025-10-22T18:00:00Z"
}
```

**Error (503)**  
```json
{
  "error": "External data source unavailable",
  "details": "Could not fetch data from Exchange API"
}
```

---

### 2️⃣ GET `/countries?region=Africa&sort=gdp_desc`

**Response:**
```json
[
  {
    "id": 1,
    "name": "Nigeria",
    "capital": "Abuja",
    "region": "Africa",
    "population": 206139589,
    "currency_code": "NGN",
    "exchange_rate": 1600.23,
    "estimated_gdp": 25767448125.2,
    "flag_url": "https://flagcdn.com/ng.svg",
    "last_refreshed_at": "2025-10-22T18:00:00Z"
  }
]
```

Supports query filters:

- `?region=Africa`
- `?currency=NGN`
- `?sort=gdp_desc` or `?sort=gdp_asc`

---

### 3️⃣ GET `/countries/{name}`

Example:
```
GET /countries/Nigeria
```

Response (200):
```json
{
  "name": "Nigeria",
  "capital": "Abuja",
  "region": "Africa",
  "population": 206139589,
  "currency_code": "NGN",
  "exchange_rate": 1600.23,
  "estimated_gdp": 25767448125.2,
  "flag_url": "https://flagcdn.com/ng.svg",
  "last_refreshed_at": "2025-10-22T18:00:00Z"
}
```

Error (404):
```json
{ "error": "Country not found" }
```

---

### 4️⃣ DELETE `/countries/{name}`

Deletes a specific country record.

Response:
```json
{ "message": "Country deleted successfully" }
```

Error (404):
```json
{ "error": "Country not found" }
```

---

### 5️⃣ GET `/status`

Returns overall statistics.

```json
{
  "total_countries": 250,
  "last_refreshed_at": "2025-10-22T18:00:00Z"
}
```

---

### 6️⃣ GET `/countries/image`

Serves the generated image file.

If the image exists:
→ returns `image/png`

If not:
```json
{ "error": "Summary image not found" }
```

---

## ⚠️ Validation Rules

| Field | Rule |
|--------|------|
| `name` | required |
| `population` | required |
| `currency_code` | required |

**Invalid data →**
```json
{
  "error": "Validation failed",
  "details": {
    "currency_code": "is required"
  }
}
```

---

## ❌ Error Handling

| HTTP Code | Example |
|------------|----------|
| **400** | `{ "error": "Validation failed" }` |
| **404** | `{ "error": "Country not found" }` |
| **500** | `{ "error": "Internal server error" }` |
| **503** | `{ "error": "External data source unavailable" }` |

---

## 🧪 Testing Instructions

To run feature tests (if included):

```bash
php artisan test
```

Or manually test using **Postman** or **cURL**:

```bash
curl -X POST http://localhost:8000/countries/refresh
curl http://localhost:8000/countries?region=Africa
```

---

## ☁️ Deployment Notes (Railway)

1. Create **two services**:
   - Laravel API service (from your GitHub repo)
   - MySQL Database

2. In the Laravel service:
   - Add variable:  
     `DATABASE_URL=${{ MySQL.MYSQL_URL }}`

3. Deploy → open Shell:
   ```bash
   php artisan migrate --force
   php artisan storage:link
   ```

4. Test your API at  
   ```
   https://<your-app>.up.railway.app/countries
   ```

---

## 📜 License

This project is open-source and available for educational use.

Author: Eda Cynthia Itsekirimi
Track: Backend