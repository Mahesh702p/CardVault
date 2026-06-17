# CardVault — Enterprise Card & Contact Intelligence System

CardVault is a premium, enterprise-grade Digital Rolodex CRM system designed to scan, parse, search, and manage business cards using advanced AI-powered vision processing and context-aware search.

---

## 🎨 Design & Premium UX

The application is styled with a modern, responsive layout optimized for desktop and mobile:
*   **Harmonious Theme**: Sleek, luxurious aesthetics tailored with custom CSS custom properties (warm slate, clean borders, dynamic shadows).
*   **Adaptive Theme Mode**: Built-in support for Light and Dark modes.
*   **Micro-Animations**: Hover-triggered translations, smooth transitions on statistics counters, and glassmorphic inputs.
*   **Intelligent Avatars**:
    *   Generates realistic, unique user avatars deterministically based on their emails.
    *   Displays cropped business card thumbnails for recently scanned card entries.
*   **Installable PWA**: Responsive service worker implementation supporting offline asset caching and home-screen app installation on mobile devices.

---

## 🚀 Architectural Flow & Core Features

### 1. Registration & Access Control
*   **Strict Security Role Mapping**: To prevent database compromise, signup forms hardcode all new registrations to the `user` role. The `admin` role is reserved strictly for site administrators.
*   **Dynamic Department Allocation**: Users enter their department as text. The system automatically creates a new department if it doesn't already exist.
*   **Dynamic Employee ID**: If left empty, a unique ID (e.g., `EMP48201`) is generated programmatically.

### 2. AI Card Ingestion Engine (OCR)
*   **Scanning Pipeline**: Users upload front-facing business card photos.
*   **Gemini Vision Integration**: CardVault sends the image to the Google Gemini API (`gemini-3.1-flash-lite`) which parses the image and returns a structured JSON payload containing:
    *   Full Name & Job Designation
    *   Company Name & General Industry
    *   Primary & Secondary Phone and Email details
    *   Physical Address & City location
    *   Website URL
    *   List of Products & Services printed on the card
*   **Duplicate Detection**: Validates against database records (emails/phones) before saving to avoid duplicate records.

### 3. Intent-Aware Hybrid Search
The search architecture balances server performance and AI call costs:
```
[User Search Query]
        │
        ├──► Is it a direct match? (Company or Contact Name in DB)
        │           │
        │           ├──► YES: Return direct, precise database results (bypass AI expansion)
        │
        └──► NO: (Service, Product, or Category query)
                    │
                    └──► Run Gemini Query Expansion ──► Expand terms & search DB
```
*   **Direct Search**: If a user searches for an exact contact or company name, CardVault queries MySQL directly, avoiding LLM roundtrip times.
*   **Semantic Query Expansion**: If the search is for general products or services (e.g. "lunch"), Gemini expands the query to related synonyms/terms (e.g. "catering", "cafeteria", "restaurant") to find cards containing those services.
*   **Search Caching**: AI-expanded terms are stored locally in the database (`search_cache`) to speed up subsequent searches.

### 4. Interactive Shortcuts
*   **Dial / SMS Shortcuts**: Instantly triggers phone calls.
*   **WhatsApp API Link**: Quick click-to-chat WhatsApp messages pre-filled with support text.
*   **Email Launcher**: Mail links containing default support headers.

---

## 🛠️ Tech Stack

*   **Backend**: Clean Native PHP (OOP, MVC Architecture pattern)
*   **Database**: MySQL (Full-Text Search Indexes, InnoDB, PDO)
*   **AI Engine**: Google Gemini API (`gemini-3.1-flash-lite`)
*   **Frontend**: Responsive HTML5, Vanilla CSS3 (custom variables), and ES6 Javascript

---

## 💾 Database Schema

*   **`users`**: Manages credentials, roles (`user`, `admin`), dynamic employee IDs, and password hashes.
*   **`departments`**: Dynamic organizational groups.
*   **`contacts`**: Holds contact details, designation, and relations.
*   **`companies`**: Holds company-level metadata (website, address, city).
*   **`products_services`**: List of unique business catalogs.
*   **`company_products`**: M-to-N map linking companies to their services.
*   **`search_cache`**: Caches AI-expanded search query mappings.
*   **`audit_logs`**: Logs user activities (logins, card uploads) for compliance.

---

## 💻 Local Setup & Installation

### 1. Clone the Files & Configure Web Server
Copy files to your local server root. Configure Apache/Nginx to serve the `public/` directory as document root.

### 2. Configure MySQL Database
Run the schema and seed scripts in MySQL:
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
mysql -u root -p < database/migration_ratings.sql
```

### 3. Set Environment Variables
Create a `.env` file at the root:
```env
DB_HOST=localhost
DB_NAME=cardvault
DB_USER=your_db_user
DB_PASS=your_db_password
GEMINI_API_KEY=your_google_gemini_api_key
```

### 4. Set Permissions
Ensure the upload directory is writable:
```bash
chmod -R 755 public/uploads
```
