# J&L Construction Supply

PHP + MySQL starter project for XAMPP.

## Setup
1. Copy this folder to `C:\xampp\htdocs\JL_Construction_Supply`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `database/jl_construction_supply.sql`.
4. Check `config/database.php` for your MySQL credentials.
5. Create the first Owner account using the one-time setup page described below.
6. Open `http://localhost/JL_Construction_Supply/`.

## Create the first Owner account
Use the one-time `setup_owner.php` helper in the project package. If it is not present, create the first Owner directly by using PHP `password_hash()` to generate a hash and inserting the owner row. Remove the setup helper after creating the account.

## Important
- Staff accounts can only be created by the Owner from the Staff Accounts page.
- The system records sales and prints receipts but does not process payments.
- The Owner Dashboard loads Chart.js 4 from jsDelivr, so the browser needs internet access to render charts.
- Enter each product's cost per unit to track gross profit. New sales save a cost snapshot; older sales with no recorded cost are excluded from profit comparisons rather than treated as zero-cost sales.
- This is a learning-project starter. Test thoroughly and back up your database before real business use.
