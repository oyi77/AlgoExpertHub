-- Script to transfer Exchange Connection ownership from Admin to User
-- Usage: Update the connection name and user email below

-- Step 1: Find the connection ID and user ID
-- Replace 'DemoExness1' with your connection name
-- Replace 'user1@user.com' with the target user email

SET @connection_name = 'DemoExness1';
SET @user_email = 'user1@user.com';

-- Get connection ID
SET @connection_id = (
    SELECT id FROM execution_connections 
    WHERE name = @connection_name 
    LIMIT 1
);

-- Get user ID
SET @user_id = (
    SELECT id FROM users 
    WHERE email = @user_email 
    LIMIT 1
);

-- Verify both exist
SELECT 
    @connection_id AS connection_id,
    @user_id AS user_id,
    CASE 
        WHEN @connection_id IS NULL THEN 'ERROR: Connection not found!'
        WHEN @user_id IS NULL THEN 'ERROR: User not found!'
        ELSE 'OK: Both found, ready to transfer'
    END AS status;

-- Step 2: Transfer ownership (uncomment to execute)
-- UPDATE execution_connections
-- SET 
--     user_id = @user_id,
--     admin_id = NULL,
--     is_admin_owned = 0
-- WHERE id = @connection_id;

-- Step 3: Verify the transfer
-- SELECT 
--     id,
--     name,
--     user_id,
--     admin_id,
--     is_admin_owned,
--     (SELECT email FROM users WHERE id = execution_connections.user_id) AS user_email,
--     (SELECT username FROM admins WHERE id = execution_connections.admin_id) AS admin_username
-- FROM execution_connections
-- WHERE id = @connection_id;

