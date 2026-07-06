# Maintenance SQL scripts

Ad-hoc SQL queries for Balancirk production databases. These scripts are **not** part of the Joomla extension installer or update flow.

## Usage

1. Open the `.sql` file you need.
2. Replace `#__` with your Joomla table prefix (for example `tc_` on balancirk.be).
3. Run the query in phpMyAdmin, Adminer, or the MySQL CLI against the site database.

## Scripts

| File | Purpose |
|------|---------|
| [orphan-users-without-member-profile.sql](orphan-users-without-member-profile.sql) | Find Joomla users without a row in `#__balancirk_members_additional` |

Add new maintenance scripts to this folder as separate `.sql` files and list them in the table above.
