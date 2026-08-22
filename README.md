# Mohimaa API

A headless RESTful API backend built with **Laravel** for managing inventory, sales, customer orders, and powering e-commerce operations for **Mohimaa**.

---

## 📌 Project Overview

**Mohimaa API** serves as the central backend system for **Mohimaa** e-commerce operations and inventory management. It provides role-based access control (RBAC) separated into **Admin** and **Customer** modules.

- **Architecture:** API Only (Headless - No Blade/Views)
- **Authentication:** Laravel Sanctum (Token-based)
- **Response Format:** Standard JSON Helper Format (`responseSuccess` / `responseError`)

---

## ✨ Features

### 👨‍💼 Admin Module

- **Authentication & User Control:** Profile management and administrative user administration.
- **Inventory & Catalog Management:**
  - Full CRUD operations for Products, Categories, Brands, Attributes, and Units.
  - Product variants, specifications, stock adjustments, and stock movement logs.
  - Purchase order / stock entry tracking.
- **Sales & Order Management:**
  - Order processing, status transitions, customizable COD amounts, and history tracking.
  - Customer ledger management with double-entry transaction posting.
  - Integrated customer courier stats & fraud prevention (Pathao, SteadFast, RedX).
- **Store Setup & Marketing:**
  - Store configuration settings, banner management with caching, and SEO metadata configuration.

### 🛍️ Customer Module

- **Account Management:** Registration, login, profile updates, and order history.
- **Product Browsing:**
  - Catalog browsing with multi-level category, brand, and attribute filtering.
  - Product variant selections, pricing, and stock visibility.
- **Order Placement:**
  - Direct checkout API and real-time order tracking.

---

## 🛠️ Tech Stack & Requirements

- **Framework:** Laravel 12.x
- **PHP Version:** `^8.5`
- **Database:** MySQL / PostgreSQL
- **Authentication:** Laravel Sanctum
- **Code Formatter:** Laravel Pint
- **Testing Framework:** Pest PHP v4
