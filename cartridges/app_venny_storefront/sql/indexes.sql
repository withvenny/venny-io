-- app_venny_storefront indexes

CREATE INDEX IF NOT EXISTS idx_catalogs_app_online ON catalogs (created_for_app_id, catalog_online, catalog_public, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_categories_catalog_online ON categories (category_catalog_id, category_online, category_public, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_products_catalog_category_online ON products (product_catalog_id, product_category_id, product_online, product_public, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_products_sku ON products (created_for_app_id, product_sku) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_items_product ON items (item_product_id, item_quantity, time_started DESC) WHERE active = 1;
CREATE INDEX IF NOT EXISTS idx_items_catalog_category_product ON items (item_catalog_id, item_category_id, item_product_id) WHERE active = 1;
