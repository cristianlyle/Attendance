-- ============================================
-- Token Scans Table Migration
-- Run this SQL in your Supabase SQL Editor
-- ============================================

-- ============================================
-- Create token_scans table (if not exists)
-- ============================================
CREATE TABLE IF NOT EXISTS token_scans (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    user_id UUID NOT NULL
        REFERENCES users(id) ON DELETE CASCADE,

    token_id UUID NOT NULL
        REFERENCES qr_tokens(id) ON DELETE CASCADE,

    attendance_id UUID
        REFERENCES attendance(id) ON DELETE CASCADE,

    action attendance_action NOT NULL,

    scanned_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- Enable Row Level Security (RLS)
-- ============================================
ALTER TABLE token_scans ENABLE ROW LEVEL SECURITY;

-- ============================================
-- Drop existing policies if they exist
-- ============================================
DROP POLICY IF EXISTS "Admin can view all token scans" ON token_scans;
DROP POLICY IF EXISTS "Users can view their own token scans" ON token_scans;
DROP POLICY IF EXISTS "Admin can insert token scans" ON token_scans;
DROP POLICY IF EXISTS "Users can insert their own token scans" ON token_scans;

-- ============================================
-- Create SELECT policies for token_scans
-- ============================================

-- Admin can view all scans
CREATE POLICY "Admin can view all token scans"
    ON token_scans FOR SELECT
    USING (true);

-- Users can view their own scans
CREATE POLICY "Users can view their own token scans"
    ON token_scans FOR SELECT
    USING (auth.uid() = user_id);

-- ============================================
-- Create INSERT policies for token_scans
-- ============================================

-- Admin can insert scans (WITH CHECK allows all)
CREATE POLICY "Admin can insert token scans"
    ON token_scans FOR INSERT
    TO authenticated
    WITH CHECK (true);

-- Users can insert their own scans (WITH CHECK ensures user_id matches auth.uid())
CREATE POLICY "Users can insert their own token scans"
    ON token_scans FOR INSERT
    TO authenticated
    WITH CHECK (auth.uid() = user_id);

-- ============================================
-- Create indexes for faster queries
-- ============================================
CREATE INDEX IF NOT EXISTS idx_token_scans_token_id ON token_scans(token_id);
CREATE INDEX IF NOT EXISTS idx_token_scans_user_id ON token_scans(user_id);
CREATE INDEX IF NOT EXISTS idx_token_scans_scanned_at ON token_scans(scanned_at DESC);
