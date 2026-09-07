# Interview Application Database

To import the database schema:

1. Open your MySQL client or phpMyAdmin.
2. Create a database named `interview` (if it does not already exist):
   ```sql
   CREATE DATABASE interview;
   USE interview;
   ```
3. Run the SQL statements located in [interview_database.md](../interview_database.md).
4. Verify that the tables (`users`, `questions`, `attempts`, `student_answers`, and `settings`) were successfully generated.
5. Configure database configuration inside [database.php](../database.php).
