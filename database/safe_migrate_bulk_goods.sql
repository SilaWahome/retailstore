-- ============================================================
--  BULK GOODS MIGRATION  -  Database: luxeshop
--  Paste this into phpMyAdmin > SQL tab and click [Go]
-- ============================================================

-- ── 1. Add sold_by to products (how the product is measured)
ALTER TABLE `products`
    ADD COLUMN `sold_by` VARCHAR(20) NOT NULL DEFAULT 'unit' AFTER `category`;

-- ── 2. Add base_unit to products (the unit label e.g. kg, ml, pcs)
ALTER TABLE `products`
    ADD COLUMN `base_unit` VARCHAR(20) NOT NULL DEFAULT 'pcs' AFTER `sold_by`;

-- ── 3. Change products.quantity from INT to DECIMAL
--      Existing values are preserved (e.g. 150 becomes 150.000)
ALTER TABLE `products`
    MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0.000;

-- ── 4. Change products.low_stock_threshold from INT to DECIMAL
ALTER TABLE `products`
    MODIFY COLUMN `low_stock_threshold` DECIMAL(15,3) NOT NULL DEFAULT 5.000;

-- ── 5. Change sale_items.quantity from INT to DECIMAL
ALTER TABLE `sale_items`
    MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000;

-- ── 6. Add unit_label to sale_items (e.g. kg, ml, pcs on the receipt)
ALTER TABLE `sale_items`
    ADD COLUMN `unit_label` VARCHAR(20) NOT NULL DEFAULT 'pcs' AFTER `quantity`;

-- ── 7. Add portion_label to sale_items (e.g. "1/2 kg", "250 ml")
ALTER TABLE `sale_items`
    ADD COLUMN `portion_label` VARCHAR(50) NULL DEFAULT NULL AFTER `unit_label`;

-- ── 8. Change inventory_logs.change_amount from INT to DECIMAL
ALTER TABLE `inventory_logs`
    MODIFY COLUMN `change_amount` DECIMAL(15,3) NOT NULL DEFAULT 0.000;

-- ── 9. Change purchase_order_items.quantity from INT to DECIMAL
--      So you can receive e.g. 150.000 kg of wheat on a Purchase Order
ALTER TABLE `purchase_order_items`
    MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000;

-- ── 10. Change quotation_items.quantity from INT to DECIMAL
ALTER TABLE `quotation_items`
    MODIFY COLUMN `quantity` DECIMAL(15,3) NOT NULL DEFAULT 1.000;

-- ── 11. Create the product_portions table
--       Stores preset sale sizes per product
--       e.g. Wheat: "1/2 kg" = 0.500 kg deducted @ 1500 UGX
CREATE TABLE `product_portions` (
    `id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `product_id`  BIGINT UNSIGNED   NOT NULL,
    `label`       VARCHAR(50)       NOT NULL  COMMENT 'e.g. 1/2 kg, 250 ml, 1 Litre',
    `quantity`    DECIMAL(15,3)     NOT NULL  COMMENT 'Stock deducted in base units',
    `price`       DECIMAL(15,2)     NOT NULL  COMMENT 'Fixed selling price for this portion',
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP         NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `product_portions_product_id_index` (`product_id`),
    CONSTRAINT `fk_product_portions_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 12. Back-fill existing rows with sensible defaults
--       All old products stay as sold_by='unit', base_unit='pcs'
--       so nothing in your existing setup breaks
UPDATE `products`  SET `sold_by`    = 'unit' WHERE `sold_by`    IS NULL OR `sold_by`    = '';
UPDATE `products`  SET `base_unit`  = 'pcs'  WHERE `base_unit`  IS NULL OR `base_unit`  = '';
UPDATE `sale_items` SET `unit_label` = 'pcs'  WHERE `unit_label` IS NULL OR `unit_label` = '';

-- ============================================================
--  DONE. No existing data was deleted or modified.
-- ============================================================
