-- Change the seeded administrator login without overwriting an installation-specific email.
UPDATE users
SET email = 'admin@ict.com'
WHERE email = 'admin@onsus.com';
