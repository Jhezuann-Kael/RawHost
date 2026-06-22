-- Add RECOMMENDATIONS category to tickets table
-- Migration: 2026-01-22 - Add recommendations category

ALTER TABLE tickets
MODIFY COLUMN category ENUM(
    'TECHNICAL',
    'BILLING',
    'RECOMMENDATIONS',
    'OTHER'
) NOT NULL DEFAULT 'OTHER';