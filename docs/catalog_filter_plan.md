# Catalog Filter & Clean SEO URL Architecture Plan

This document details the architectural plan for implementing slug-based API filtering and SEO-friendly dynamic routing in the Mohima E-Commerce platform.

---

## 🎯 Objectives

1. **Backend (Laravel API):**
   - Update `/api/customer/products/filter` to accept `category_slug` (or `category`) and `brand_slug` (or `brand`) instead of numeric IDs.
   - Automatically support parent category slug queries (fetching products in category and its descendants).

2. **Frontend (Next.js 16 App Router):**
   - Implement clean, SEO-optimized URL routing while maintaining a single reusable `CatalogFilterClient` component.
   - Seamlessly handle all 8 URL patterns requested.

---

## 📌 Supported URL Patterns

| Pattern | URL Structure | Description |
| :--- | :--- | :--- |
| 1 | `base_url/catalog/?search_text=shirt` | General search or parameter query |
| 2 | `base_url/{category_slug}` | Category landing page |
| 3 | `base_url/{category_slug}/{brand_slug}` | Category filtered by specific Brand |
| 4 | `base_url/{brand_slug}` | Brand landing page |
| 5 | `base_url/{category_slug}/?search_text=shirt` | Category page with search query |
| 6 | `base_url/{category_slug}/{brand_slug}/?search_text=shirt` | Category + Brand + Search query |
| 7 | `base_url/{category_slug}/{brand_slug}/?search_text=shirt&min_price=100` | Full filter combination |
| 8 | `base_url/{brand_slug}/?search_text=shirt` | Brand page with search query |

---

## 🛠️ Implementation Specs

### 1. Backend Changes

#### `app/Http/Requests/Customer/ProductFilterRequest.php`
Accept new parameters:
- `category_slug` / `category`: String (Category slug)
- `brand_slug` / `brand`: String (Brand slug)
- Retain `category_id` and `brand_id` for backwards compatibility.

#### `app/Services/Customer/ProductService.php`
- Modify `filterProducts()`:
  - If `category_slug` is provided, find the category by slug and query products matching category ID or its descendants (`$category->descendantsAndSelf()`).
  - If `brand_slug` is provided, query products belonging to the brand with that slug.

---

### 2. Frontend Changes

#### `lib/api/products.ts`
- Update `ProductFilterParams` type:
  ```ts
  export interface ProductFilterParams {
    search_text?: string;
    category_slug?: string;
    category_id?: number | string;
    brand_slug?: string;
    brand_id?: number | string;
    min_price?: number;
    max_price?: number;
    is_stock?: boolean | number;
    sort_by?: string;
    page?: number;
    per_page?: number;
  }
  ```

#### `components/CatalogFilterClient.tsx`
- Build a reusable client component that handles state for selected category slug, brand slug, search text, price range, etc.
- Synchronize state with URL:
  - Selected category & brand dictate the path (`/catalog`, `/{category_slug}`, `/{category_slug}/{brand_slug}`, `/{brand_slug}`).
  - Other active filters are formatted as URL search parameters (`?search_text=...&min_price=...`).

#### Next.js Route Handlers:
- `app/(customer)/catalog/page.tsx`: Handles `/catalog` route.
- `app/(customer)/[slug1]/page.tsx`: Resolves single slug (`/{category_slug}` or `/{brand_slug}`).
- `app/(customer)/[slug1]/[slug2]/page.tsx`: Resolves double slug (`/{category_slug}/{brand_slug}`).
- `app/(customer)/products/page.tsx`: Backward-compatible redirect or catalog handler.

#### Header Component (`components/Header.tsx`):
- Update header search submission to push to `/catalog?search_text=...` or preserve current slug path.
- Category links update to `/${category.slug}`.
