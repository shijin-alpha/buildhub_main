#!/usr/bin/env python3
"""
Quick test to check if architects exist in the database
"""

import pymysql
import json

def check_architects():
    """Check if architects exist in the database"""
    
    # Database configuration (matches your existing setup)
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'buildhub',
        'charset': 'utf8mb4'
    }
    
    print("🔍 Checking architects in your database...")
    
    try:
        # Connect to database
        conn = pymysql.connect(**db_config)
        print("✅ Database connection successful")
        
        # Check if users table exists
        cursor = conn.cursor()
        cursor.execute("SHOW TABLES LIKE 'users'")
        if not cursor.fetchone():
            print("❌ 'users' table does not exist!")
            return
        
        # Count total users
        cursor.execute("SELECT COUNT(*) FROM users")
        total_users = cursor.fetchone()[0]
        print(f"📊 Total users in database: {total_users}")
        
        # Count architects
        cursor.execute("SELECT COUNT(*) FROM users WHERE role = 'architect'")
        total_architects = cursor.fetchone()[0]
        print(f"🏗️ Total architects: {total_architects}")
        
        # Count approved architects
        cursor.execute("SELECT COUNT(*) FROM users WHERE role = 'architect' AND status = 'approved'")
        approved_architects = cursor.fetchone()[0]
        print(f"✅ Approved architects: {approved_architects}")
        
        if approved_architects == 0:
            print("\n❌ PROBLEM FOUND: No approved architects in database!")
            print("💡 Solutions:")
            print("   1. Add some architects to your users table")
            print("   2. Set their role = 'architect' and status = 'approved'")
            print("   3. Or use the fallback recommendations in the frontend")
            
            # Show sample data structure
            print("\n📋 Sample architect data structure:")
            print("""
            INSERT INTO users (first_name, last_name, email, role, status, specialization, experience_years) 
            VALUES ('John', 'Doe', 'john@example.com', 'architect', 'approved', 'Modern', 5);
            """)
        else:
            print(f"\n✅ SUCCESS: Found {approved_architects} approved architects!")
            
            # Show sample architects
            cursor.execute("""
                SELECT id, CONCAT(first_name, ' ', last_name) as name, specialization, experience_years 
                FROM users 
                WHERE role = 'architect' AND status = 'approved' 
                LIMIT 3
            """)
            
            architects = cursor.fetchall()
            print("\n👥 Sample architects:")
            for arch in architects:
                print(f"   - {arch[1]} (ID: {arch[0]}, Specialty: {arch[2]}, Experience: {arch[3]} years)")
        
        conn.close()
        
    except Exception as e:
        print(f"❌ Database error: {e}")
        print("💡 Make sure MySQL is running and the 'buildhub' database exists")

if __name__ == "__main__":
    print("🏗️ Architect Database Checker")
    print("=" * 40)
    check_architects()
    print("=" * 40)
    print("🏁 Check Complete!")



