-- Replace the former public store name while preserving any name the owner has customized.
UPDATE settings
SET `value` = 'Shop ICT Gadgets'
WHERE `key` = 'store_name' AND `value` = 'Onsus';
