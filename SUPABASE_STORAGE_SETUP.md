# Supabase Storage Setup for Profile Images

## 1. Create Storage Bucket

1. Go to Supabase Dashboard → Storage
2. Click "New Bucket"
3. Bucket name: `profile_images`
4. ✅ Make it **Public**
5. Click "Create bucket"

## 2. Set Up RLS Policies (IMPORTANT)

Go to Storage → Click on `profile_images` bucket → Click "Policies" tab

### Policy 1: Allow INSERT (upload files)
```sql
-- Policy name: Allow uploads
CREATE POLICY "Allow uploads"
ON storage.objects
FOR INSERT
TO authenticated
WITH CHECK (
  bucket_id = 'profile_images'
);
```

### Policy 2: Allow SELECT (view files)
```sql
-- Policy name: Allow public reads
CREATE POLICY "Allow public reads"
ON storage.objects
FOR SELECT
TO public
USING (
  bucket_id = 'profile_images'
);
```

### Policy 3: Allow UPDATE (update files)
```sql
-- Policy name: Allow updates
CREATE POLICY "Allow updates"
ON storage.objects
FOR UPDATE
TO authenticated
USING (
  bucket_id = 'profile_images'
)
WITH CHECK (
  bucket_id = 'profile_images'
);
```

### Policy 4: Allow DELETE (delete files)
```sql
-- Policy name: Allow deletes
CREATE POLICY "Allow deletes"
ON storage.objects
FOR DELETE
TO authenticated
USING (
  bucket_id = 'profile_images'
);
```

## 3. Add Database Column

Run this SQL in Supabase SQL Editor:
```sql
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image TEXT;
```

## 4. CORS Settings

Go to Settings → API → CORS → Add allowed origin:
- `http://localhost` (for local development)
- Your production domain

## 5. Test Upload

Try uploading a small image file (less than 2MB) to test.

## Troubleshooting

If still failing with HTTP 400:
1. Check browser console for CORS errors
2. Verify RLS policies are enabled
3. Make sure bucket is set to "Public"
4. Check that the `profile_image` column exists in the `users` table
