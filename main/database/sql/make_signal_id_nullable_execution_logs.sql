-- Migration: Make signal_id nullable in execution_logs table
-- This allows manual trades (which don't have a signal_id) to be logged

-- Step 1: Find and drop foreign key constraint
-- First, find the constraint name:
-- SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sp_execution_logs' 
-- AND COLUMN_NAME = 'signal_id' AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Then drop it (replace CONSTRAINT_NAME with actual name from query above):
-- ALTER TABLE `sp_execution_logs` DROP FOREIGN KEY `CONSTRAINT_NAME`;

-- Alternative: Try common constraint names
ALTER TABLE `sp_execution_logs` DROP FOREIGN KEY IF EXISTS `execution_logs_signal_id_foreign`;
ALTER TABLE `sp_execution_logs` DROP FOREIGN KEY IF EXISTS `sp_execution_logs_signal_id_foreign`;

-- Step 2: Modify column to allow NULL
ALTER TABLE `sp_execution_logs` MODIFY `signal_id` BIGINT UNSIGNED NULL;

-- Step 3: Re-add foreign key constraint (with nullable support)
ALTER TABLE `sp_execution_logs` 
ADD CONSTRAINT `execution_logs_signal_id_foreign` 
FOREIGN KEY (`signal_id`) 
REFERENCES `sp_signals` (`id`) 
ON DELETE CASCADE;

